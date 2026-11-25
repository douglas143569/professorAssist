<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\PendingSignup;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Logger;
use App\Services\Mailer;

/**
 * Login e logout.
 *
 * Estende Controller (e nao AppController) porque estas telas precisam
 * funcionar justamente para quem AINDA nao esta logado.
 */
class AuthController extends Controller
{
    private const SENHA_MINIMA = 8;

    /** Cadastros permitidos por IP por hora. */
    private const MAX_CADASTROS_POR_IP = 10;

    public function formulario(): void
    {
        Auth::iniciarSessao();

        if (Auth::logado()) {
            $this->redirect('/');
        }

        // Padrao POST-Redirect-GET: o erro vem da tentativa anterior.
        $erro = $_SESSION['login_erro'] ?? null;
        $email = $_SESSION['login_email'] ?? '';
        unset($_SESSION['login_erro'], $_SESSION['login_email']);

        $this->viewSemLayout('auth.login', [
            'erro' => $erro,
            'email' => $email,
        ]);
    }

    public function entrar(): void
    {
        Auth::iniciarSessao();

        // Impede que outro site poste um login na sessao do visitante
        // (login-CSRF: te loga numa conta do atacante sem voce perceber).
        if (!Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            $this->voltarComErro(
                'Sua sessão expirou enquanto a página estava aberta. Tente entrar de novo.',
                trim((string) ($_POST['email'] ?? ''))
            );
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');

        if ($email === '' || $senha === '') {
            $this->voltarComErro('Preencha o e-mail e a senha.', $email);
        }

        $resultado = Auth::tentarLogin($email, $senha);

        if (!$resultado['ok']) {
            $this->voltarComErro($resultado['erro'], $email);
        }

        // Volta para a pagina que o professor tentou abrir antes do login.
        $destino = $_SESSION['destino'] ?? '/';
        unset($_SESSION['destino']);

        $this->redirect($this->destinoSeguro($destino));
    }

    /* ------------------------------------------------------------------
     |  Cadastro do proprio professor
     |
     |  A conta so e criada depois da confirmacao do e-mail. O papel e sempre
     |  'professor' (nunca admin), com limite por IP e senha minima.
     |  O teto de gasto com IA (AI_TETO_USD) vale por conta.
     * ------------------------------------------------------------------*/

    public function formularioCadastro(): void
    {
        Auth::iniciarSessao();

        if (Auth::logado()) {
            $this->redirect('/');
        }

        $erro = $_SESSION['cadastro_erro'] ?? null;
        $dados = $_SESSION['cadastro_dados'] ?? [];
        unset($_SESSION['cadastro_erro'], $_SESSION['cadastro_dados']);

        $this->viewSemLayout('auth.cadastro', [
            'erro' => $erro,
            'dados' => $dados,
        ]);
    }

    public function cadastrar(): void
    {
        Auth::iniciarSessao();

        $dados = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
            'celular' => trim((string) ($_POST['celular'] ?? '')),
        ];

        if (!Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            $this->erroCadastro('A página ficou aberta tempo demais. Tente enviar de novo.', $dados);
        }

        $senha = (string) ($_POST['senha'] ?? '');
        $confirmacao = (string) ($_POST['senha_confirmacao'] ?? '');

        if ($dados['name'] === '' || $dados['email'] === '') {
            $this->erroCadastro('Preencha o nome e o e-mail.', $dados);
        }

        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $this->erroCadastro('E-mail inválido.', $dados);
        }

        $celular = User::normalizarCelular($dados['celular']);

        if ($celular === null) {
            $this->erroCadastro('Informe um celular válido com DDD, por exemplo (11) 98765-4321.', $dados);
        }

        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            $this->erroCadastro('A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.', $dados);
        }

        if ($senha !== $confirmacao) {
            $this->erroCadastro('As duas senhas não são iguais.', $dados);
        }

        if (($_POST['aceite'] ?? '') !== '1') {
            $this->erroCadastro('Para criar a conta é preciso aceitar a Política de Privacidade e a Política de Cookies.', $dados);
        }

        $users = new User();

        if ($users->findByEmail($dados['email']) !== null) {
            $this->erroCadastro('Já existe uma conta com esse e-mail. Tente entrar.', $dados);
        }

        $ip = Request::ip();

        if ($ip !== null && (new ActivityLog())->countByIp('conta.verification_requested', $ip, 60) >= self::MAX_CADASTROS_POR_IP) {
            Logger::warning('Limite de verificacoes de cadastro por IP atingido', ['ip' => $ip]);
            $this->erroCadastro('Muitos cadastros a partir daqui na última hora. Tente mais tarde.', $dados);
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pendentes = new PendingSignup();
        $pendentes->create(
            array_merge($dados, ['celular' => $celular]),
            password_hash($senha, PASSWORD_DEFAULT),
            password_hash($codigo, PASSWORD_DEFAULT),
            \App\Services\Legal::VERSAO,
            $ip
        );

        Logger::activity('conta.verification_requested', [
            'description' => 'Código de verificação de cadastro solicitado',
        ]);

        if (!$this->enviarCodigoVerificacao($dados['email'], $dados['name'], $codigo)) {
            $pendentes->delete($dados['email']);
            $this->erroCadastro('Não foi possível enviar o código. Confira a configuração de e-mail e tente novamente.', $dados);
        }

        $_SESSION['cadastro_email_pendente'] = $dados['email'];
        $_SESSION['cadastro_nome_pendente'] = $dados['name'];
        $this->redirect('/verificar-email');
    }

    public function formularioVerificacaoEmail(): void
    {
        Auth::iniciarSessao();

        if (Auth::logado()) {
            $this->redirect('/');
        }

        $email = (string) ($_SESSION['cadastro_email_pendente'] ?? '');
        if ($email === '') {
            $_SESSION['cadastro_erro'] = 'Comece preenchendo seus dados para receber o código de verificação.';
            $this->redirect('/cadastro');
        }

        $erro = $_SESSION['cadastro_verificacao_erro'] ?? null;
        $mensagem = $_SESSION['cadastro_verificacao_mensagem'] ?? null;
        unset($_SESSION['cadastro_verificacao_erro'], $_SESSION['cadastro_verificacao_mensagem']);

        $this->viewSemLayout('auth.verificar_email', [
            'email' => $email,
            'erro' => $erro,
            'mensagem' => $mensagem,
            'emailConfigurado' => Mailer::configurado(),
            'arquivoLogEmail' => 'storage/logs/mail-' . date('Y-m-d') . '.log',
        ]);
    }

    public function confirmarEmail(): void
    {
        Auth::iniciarSessao();
        $email = (string) ($_SESSION['cadastro_email_pendente'] ?? '');

        if ($email === '') {
            $_SESSION['cadastro_erro'] = 'Comece preenchendo seus dados para receber o código de verificação.';
            $this->redirect('/cadastro');
        }

        if (!Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            $_SESSION['cadastro_verificacao_erro'] = 'A página ficou aberta tempo demais. Tente enviar de novo.';
            $this->redirect('/verificar-email');
        }

        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        if (!preg_match('/^\d{6}$/D', $codigo)) {
            $_SESSION['cadastro_verificacao_erro'] = 'Digite o código de 6 dígitos enviado por e-mail.';
            $this->redirect('/verificar-email');
        }

        $db = Database::getConnection();
        $pendentes = new PendingSignup();
        $db->beginTransaction();

        try {
            $pendente = $pendentes->findForConfirmation($email);

            if ($pendente === null || (int) $pendente['nao_expirado'] !== 1) {
                $db->commit();
                $_SESSION['cadastro_verificacao_erro'] = 'O código expirou ou não está mais disponível. Você pode solicitar outro abaixo.';
                $this->redirect('/verificar-email');
            }

            if ((int) $pendente['attempts'] >= PendingSignup::MAX_ATTEMPTS) {
                $db->commit();
                $_SESSION['cadastro_verificacao_erro'] = 'Limite de tentativas atingido. Solicite um novo código para continuar.';
                $this->redirect('/verificar-email');
            }

            if (!password_verify($codigo, (string) $pendente['code_hash'])) {
                $pendentes->registerFailedAttempt($email);
                $db->commit();
                $_SESSION['cadastro_verificacao_erro'] = 'Código incorreto. Você tem '
                    . max(0, PendingSignup::MAX_ATTEMPTS - ((int) $pendente['attempts'] + 1))
                    . ' tentativa(s) restante(s).';
                $this->redirect('/verificar-email');
            }

            $users = new User();
            if ($users->findByEmail($email) !== null) {
                $pendentes->consume($email);
                $db->commit();
                unset($_SESSION['cadastro_email_pendente'], $_SESSION['cadastro_nome_pendente']);
                $this->erroCadastro('Já existe uma conta com esse e-mail. Tente entrar.', ['email' => $email]);
            }

            // A conta e o código pendente são consumidos na mesma transação.
            $id = $users->criarProfessorComHash(
                (string) $pendente['name'],
                (string) $pendente['email'],
                (string) $pendente['password_hash'],
                (string) $pendente['celular']
            );
            $users->registrarAceite($id, (string) $pendente['aceite_versao']);
            $pendentes->consume($email);
            $db->commit();
            $dados = ['email' => (string) $pendente['email']];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        unset($_SESSION['cadastro_email_pendente'], $_SESSION['cadastro_nome_pendente']);

        Logger::activity('conta.registered', [
            'user_id' => $id,
            'entity_type' => 'user',
            'entity_id' => $id,
            'description' => "Cadastro proprio: {$dados['email']}",
        ]);

        // Teste gratis de 3 dias: cria uma assinatura ativa com vencimento em
        // +3 dias. Depois disso o AppController bloqueia e pede a renovacao.
        $teste = (new Subscription())->startTrial($id);

        if ($teste) {
            Logger::activity('subscription.trial_started', [
                'user_id' => $id,
                'entity_type' => 'user',
                'entity_id' => $id,
                'description' => 'Teste gratis de 3 dias iniciado no cadastro',
            ]);
        }

        $entrou = Auth::entrarAposVerificacaoEmail($id);
        if (!$entrou) {
            $_SESSION['login_email'] = $dados['email'];
        }
        $_SESSION['flash'] = $teste
            ? 'E-mail confirmado! Seu cadastro foi concluído e o teste grátis de 3 dias começou. Entre com sua senha.'
            : 'E-mail confirmado! Seu cadastro foi concluído.';
        $this->redirect($entrou ? '/' : '/login');
    }

    public function reenviarCodigoVerificacao(): void
    {
        Auth::iniciarSessao();
        $email = (string) ($_SESSION['cadastro_email_pendente'] ?? '');

        if ($email === '') {
            $_SESSION['cadastro_erro'] = 'Comece preenchendo seus dados para receber o código de verificação.';
            $this->redirect('/cadastro');
        }

        if (!Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            $_SESSION['cadastro_verificacao_erro'] = 'A página ficou aberta tempo demais. Tente enviar de novo.';
            $this->redirect('/verificar-email');
        }

        $ip = Request::ip();
        if ($ip !== null && (new ActivityLog())->countByIp('conta.verification_requested', $ip, 60) >= self::MAX_CADASTROS_POR_IP) {
            $_SESSION['cadastro_verificacao_erro'] = 'Muitas solicitações a partir daqui. Tente novamente mais tarde.';
            $this->redirect('/verificar-email');
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pendentes = new PendingSignup();
        Logger::activity('conta.verification_requested', [
            'description' => 'Novo código de verificação de cadastro solicitado',
        ]);
        $resultado = $pendentes->resend($email, $codigo);

        if ($resultado['status'] === 'missing') {
            unset($_SESSION['cadastro_email_pendente']);
            $_SESSION['cadastro_erro'] = 'O cadastro temporário não foi encontrado. Preencha seus dados novamente.';
            $this->redirect('/cadastro');
        }

        if ($resultado['status'] === 'cooldown') {
            $_SESSION['cadastro_verificacao_erro'] = 'Aguarde ' . $resultado['seconds'] . ' segundos antes de pedir outro código.';
            $this->redirect('/verificar-email');
        }

        $name = (string) ($_SESSION['cadastro_nome_pendente'] ?? '');
        if (!$this->enviarCodigoVerificacao($email, $name, $codigo)) {
            $pendentes->delete($email);
            unset($_SESSION['cadastro_email_pendente'], $_SESSION['cadastro_nome_pendente']);
            $_SESSION['cadastro_erro'] = 'Não foi possível enviar o código. Preencha seus dados novamente ou confira a configuração de e-mail.';
            $this->redirect('/cadastro');
        }

        $this->redirect('/verificar-email');
    }

    private function enviarCodigoVerificacao(string $email, string $nome, string $codigo): bool
    {
        $nomeHtml = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $html = "<p>Olá, {$nomeHtml}.</p>"
            . '<p>Use este código para confirmar seu e-mail e concluir o cadastro no <strong>Assistente do Professor</strong>:</p>'
            . '<p style="font-size:28px;font-weight:bold;letter-spacing:6px">' . $codigo . '</p>'
            . '<p>O código vale por 15 minutos. Se você não iniciou esse cadastro, ignore esta mensagem.</p>';
        $texto = "Olá, {$nome}.\n\nUse este código para confirmar seu e-mail e concluir o cadastro no Assistente do Professor:\n\n"
            . "{$codigo}\n\nO código vale por 15 minutos. Se você não iniciou esse cadastro, ignore esta mensagem.";

        return Mailer::enviar($email, 'Confirme seu e-mail — Assistente do Professor', $html, $texto);
    }

    private function erroCadastro(string $erro, array $dados): void
    {
        unset($dados['senha']); // a senha nunca volta preenchida na tela
        $_SESSION['cadastro_erro'] = $erro;
        $_SESSION['cadastro_dados'] = $dados;

        $this->redirect('/cadastro');
    }

    public function sair(): void
    {
        Auth::iniciarSessao();

        // Sem token, outro site conseguiria deslogar voce no meio do trabalho.
        if (!Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            $this->redirect('/');
        }

        Auth::logout();
        $this->redirect('/login');
    }

    private function voltarComErro(string $erro, string $email): void
    {
        $_SESSION['login_erro'] = $erro;
        $_SESSION['login_email'] = $email;

        $this->redirect('/login');
    }

    /**
     * So aceita caminho interno: evita open redirect via /login?destino=http://...
     */
    private function destinoSeguro(string $destino): string
    {
        if ($destino === '' || $destino[0] !== '/' || str_starts_with($destino, '//')) {
            return '/';
        }

        return $destino;
    }
}
