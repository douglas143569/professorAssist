<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Logger;

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
    private const MAX_CADASTROS_POR_IP = 3;

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
     |  Por decisao do produto, a conta criada aqui JA NASCE ATIVA: quem se
     |  cadastra entra na hora, sem aprovacao. Por isso as travas abaixo --
     |  papel sempre 'professor' (nunca admin), limite de cadastros por IP e
     |  senha minima. O teto de gasto com IA (AI_TETO_USD) vale por conta,
     |  entao cada cadastro novo tem seu proprio limite.
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

        $users = new User();

        if ($users->findByEmail($dados['email']) !== null) {
            $this->erroCadastro('Já existe uma conta com esse e-mail. Tente entrar.', $dados);
        }

        $ip = Request::ip();

        if ($ip !== null && (new ActivityLog())->countByIp('conta.registered', $ip, 60) >= self::MAX_CADASTROS_POR_IP) {
            Logger::warning('Limite de cadastros por IP atingido', ['ip' => $ip]);
            $this->erroCadastro('Muitos cadastros a partir daqui na última hora. Tente mais tarde.', $dados);
        }

        // role fixo: cadastro proprio nunca cria administrador.
        $id = $users->create($dados['name'], $dados['email'], $senha, User::ROLE_PROFESSOR, $celular);

        Logger::activity('conta.registered', [
            'user_id' => $id,
            'entity_type' => 'user',
            'entity_id' => $id,
            'description' => "Cadastro proprio: {$dados['email']}",
        ]);

        // Entra direto, como definido no produto.
        Auth::tentarLogin($dados['email'], $senha);

        $_SESSION['flash'] = 'Conta criada. Bem-vindo(a)!';
        $this->redirect('/');
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
