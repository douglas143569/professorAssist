<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Logger;
use App\Services\Mailer;

/**
 * Recuperacao de senha do professor por e-mail ("Esqueci minha senha").
 * Publico (nao exige login). Alunos nao tem e-mail: o professor/admin redefine.
 *
 * Seguranca: resposta identica exista ou nao o e-mail (sem enumerar contas),
 * token aleatorio de 256 bits guardado so como hash, validade de 60 min, uso
 * unico, limite de pedidos por conta e por IP, link montado a partir do
 * APP_URL (nunca do cabecalho Host).
 */
class SenhaController extends Controller
{
    private const SENHA_MINIMA = 8;

    public function esqueci(): void
    {
        Auth::iniciarSessao();
        if (Auth::logado()) {
            $this->redirect('/');
        }

        $this->tela('auth.esqueci', [
            'erro' => $this->pegar('senha_erro'),
            'enviado' => $this->pegar('senha_enviado') !== null,
            'email' => (string) ($this->pegar('senha_email') ?? ''),
        ]);
    }

    public function solicitar(): void
    {
        Auth::iniciarSessao();

        if (!Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            $_SESSION['senha_erro'] = 'A página ficou aberta tempo demais. Tente enviar de novo.';
            $this->redirect('/esqueci-senha');
        }

        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $_SESSION['senha_email'] = $email;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['senha_erro'] = 'Informe um e-mail válido.';
            $this->redirect('/esqueci-senha');
        }

        $resets = new PasswordReset();
        $ip = Request::ip();

        if ($resets->pedidosRecentesDoIp($ip) >= PasswordReset::MAX_POR_IP_HORA) {
            Logger::warning('Recuperacao de senha: limite por IP atingido', ['ip' => $ip]);
            $_SESSION['senha_erro'] = 'Muitas solicitações a partir daqui. Tente de novo em cerca de 1 hora.';
            $this->redirect('/esqueci-senha');
        }

        $user = (new User())->findByEmailComHash($email);
        $elegivel = $user !== null
            && (int) $user['ativo'] === 1
            && in_array($user['role'], [User::ROLE_PROFESSOR, User::ROLE_ADMIN], true);

        if ($elegivel && $resets->pedidosRecentesDoUsuario((int) $user['id']) < PasswordReset::MAX_POR_USUARIO_HORA) {
            $base = $this->urlBase();
            if ($base === null) {
                Logger::error('Recuperacao de senha: configure APP_URL (link nao gerado).');
            } else {
                $token = $resets->criar((int) $user['id'], $ip);
                $link = $base . '/redefinir-senha/' . $token;
                $this->enviarLink($user, $link);

                Logger::activity('senha.reset_solicitado', [
                    'user_id' => (int) $user['id'],
                    'entity_type' => 'user',
                    'entity_id' => (int) $user['id'],
                    'description' => 'Pedido de redefinicao de senha',
                ]);
            }
        } elseif ($user !== null) {
            Logger::access('password_reset', $email, (int) $user['id']);
        }

        // Mesma resposta em todos os casos: nao revela se o e-mail tem conta.
        $_SESSION['senha_enviado'] = '1';
        $this->redirect('/esqueci-senha');
    }

    public function redefinirForm(string $token): void
    {
        Auth::iniciarSessao();
        $this->cabecalhosSensiveis();

        $valido = (new PasswordReset())->validoPorToken($token) !== null;

        $this->tela('auth.redefinir', [
            'token' => $token,
            'valido' => $valido,
            'erro' => $this->pegar('senha_erro'),
        ]);
    }

    public function redefinir(): void
    {
        Auth::iniciarSessao();
        $token = (string) ($_POST['token'] ?? '');
        $volta = '/redefinir-senha/' . rawurlencode($token);

        if (!Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            $_SESSION['senha_erro'] = 'A página ficou aberta tempo demais. Tente de novo.';
            $this->redirect($volta);
        }

        $resets = new PasswordReset();
        $pedido = $resets->validoPorToken($token);

        if ($pedido === null) {
            $this->redirect($volta); // a tela mostra "link inválido ou expirado"
        }

        $senha = (string) ($_POST['senha'] ?? '');
        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            $_SESSION['senha_erro'] = 'A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.';
            $this->redirect($volta);
        }
        if ($senha !== (string) ($_POST['senha_confirmacao'] ?? '')) {
            $_SESSION['senha_erro'] = 'As duas senhas não são iguais.';
            $this->redirect($volta);
        }

        (new User())->atualizarSenha((int) $pedido['user_id'], $senha);
        $resets->consumir((int) $pedido['id'], (int) $pedido['user_id']);

        Logger::activity('senha.redefinida', [
            'user_id' => (int) $pedido['user_id'],
            'entity_type' => 'user',
            'entity_id' => (int) $pedido['user_id'],
            'description' => 'Senha redefinida pelo link de recuperacao',
        ]);

        // Aviso de seguranca: se nao foi a pessoa, ela fica sabendo.
        Mailer::enviar(
            (string) $pedido['email'],
            'Sua senha foi alterada',
            '<p>Olá, ' . htmlspecialchars((string) $pedido['name']) . '.</p><p>A senha da sua conta no <strong>Assistente do Professor</strong> acabou de ser alterada. '
            . 'Se não foi você, use "Esqueci minha senha" na tela de login imediatamente e fale com o suporte.</p>',
            "Olá, {$pedido['name']}.\n\nA senha da sua conta no Assistente do Professor acabou de ser alterada. "
            . 'Se não foi você, use "Esqueci minha senha" na tela de login imediatamente e fale com o suporte.'
        );

        $_SESSION['flash'] = 'Senha alterada! Entre com a nova senha.';
        $this->redirect('/login');
    }

    // ------------------------------------------------------------------

    private function enviarLink(array $user, string $link): void
    {
        $minutos = PasswordReset::VALIDADE_MINUTOS;
        $nome = htmlspecialchars((string) $user['name']);
        $url = htmlspecialchars($link);

        $html = "<p>Olá, {$nome}.</p>"
            . '<p>Recebemos um pedido para redefinir a senha da sua conta no <strong>Assistente do Professor</strong>.</p>'
            . "<p><a href=\"{$url}\" style=\"display:inline-block;padding:12px 22px;background:#0f7a54;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold\">Criar nova senha</a></p>"
            . "<p>Ou copie este endereço no navegador:<br>{$url}</p>"
            . "<p>O link vale por {$minutos} minutos e só pode ser usado uma vez. Se você não pediu, ignore este e-mail: sua senha continua a mesma.</p>";
        $texto = "Olá, {$user['name']}.\n\nRecebemos um pedido para redefinir a senha da sua conta no Assistente do Professor.\n\n"
            . "Crie uma nova senha: {$link}\n\nO link vale por {$minutos} minutos e só pode ser usado uma vez. "
            . 'Se você não pediu, ignore este e-mail: sua senha continua a mesma.';

        Mailer::enviar((string) $user['email'], 'Redefinir sua senha — Assistente do Professor', $html, $texto);
    }

    /** Base do link: APP_URL; so em desenvolvimento cai para o host da requisicao. */
    private function urlBase(): ?string
    {
        $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        if ($base !== '') {
            return $base;
        }
        if (($_ENV['APP_ENV'] ?? 'production') !== 'production' && !empty($_SERVER['HTTP_HOST'])) {
            $esq = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
            return $esq . '://' . $_SERVER['HTTP_HOST'];
        }
        return null;
    }

    private function cabecalhosSensiveis(): void
    {
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: no-store');
    }

    private function pegar(string $chave): ?string
    {
        $v = $_SESSION[$chave] ?? null;
        unset($_SESSION[$chave]);
        return $v;
    }

    private function tela(string $view, array $dados): void
    {
        $this->viewSemLayout($view, $dados);
    }
}
