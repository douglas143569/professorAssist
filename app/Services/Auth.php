<?php

namespace App\Services;

use App\Core\Request;
use App\Models\AccessLog;
use App\Models\User;

/**
 * Autenticacao do professor (RF-01).
 *
 * Ponto UNICO de login/logout do projeto. Nenhum controller deve mexer
 * direto em $_SESSION para dizer quem esta logado -- use os metodos daqui.
 *
 * O usuario e relido do banco a cada requisicao (nao fica congelado na
 * sessao), para que desativar uma conta ou trocar seu papel tenha efeito
 * imediato, sem esperar o proximo login.
 */
class Auth
{
    /** Chave da sessao que guarda QUEM esta logado. */
    private const CHAVE = 'auth_user_id';

    /** Bloqueio de forca bruta: N falhas no mesmo IP dentro da janela. */
    public const MAX_FALHAS = 5;
    public const JANELA_MINUTOS = 10;

    /** Cache por requisicao, para nao consultar o banco varias vezes. */
    private static ?array $cache = null;

    public static function iniciarSessao(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }

    /**
     * @return array{ok: bool, erro: ?string} Mensagem pronta para a tela.
     */
    public static function tentarLogin(string $email, string $senha): array
    {
        self::iniciarSessao();

        $email = mb_strtolower(trim($email));
        $ip = Request::ip();

        if ($ip !== null && self::bloqueadoPorTentativas($ip)) {
            Logger::access('login_failed', $email);

            return ['ok' => false, 'erro' => 'Muitas tentativas seguidas. Espere '
                . self::JANELA_MINUTOS . ' minutos e tente de novo.'];
        }

        $user = (new User())->findByEmailComHash($email);

        // Sempre roda o password_verify (mesmo sem usuario) para nao revelar
        // pelo tempo de resposta se o e-mail existe ou nao.
        $hash = $user['password_hash'] ?? '$2y$12$invalidoinvalidoinvalidoinvalidoinvalidoinvalidoinvalidoinv';

        if (!password_verify($senha, $hash) || !$user) {
            Logger::access('login_failed', $email, isset($user['id']) ? (int) $user['id'] : null);

            return ['ok' => false, 'erro' => 'E-mail ou senha incorretos.'];
        }

        if ((int) $user['ativo'] !== 1) {
            Logger::access('login_failed', $email, (int) $user['id']);

            return ['ok' => false, 'erro' => 'Esta conta esta desativada. Fale com o administrador.'];
        }

        $id = (int) $user['id'];

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            (new User())->atualizarSenha($id, $senha);
        }

        // Troca o id da sessao no login (evita fixacao de sessao).
        session_regenerate_id(true);

        $_SESSION[self::CHAVE] = $id;
        $_SESSION['user_id'] = $id; // usado pelo Logger para auditoria
        self::$cache = null;

        (new User())->registrarLogin($id);
        Logger::access('login_success', $email, $id);

        return ['ok' => true, 'erro' => null];
    }

    /**
     * Usuario logado (sem o hash da senha) ou null.
     */
    public static function usuario(): ?array
    {
        self::iniciarSessao();

        $id = isset($_SESSION[self::CHAVE]) ? (int) $_SESSION[self::CHAVE] : 0;

        if ($id <= 0) {
            return null;
        }

        if (self::$cache !== null && (int) self::$cache['id'] === $id) {
            return self::$cache;
        }

        $user = (new User())->find($id);

        // Conta apagada ou desativada no meio da sessao: derruba na hora.
        if ($user === null || (int) $user['ativo'] !== 1) {
            self::encerrarSessao();

            return null;
        }

        $_SESSION['user_id'] = $id;
        self::$cache = $user;

        return $user;
    }

    public static function logado(): bool
    {
        return self::usuario() !== null;
    }

    public static function ehAdmin(): bool
    {
        $user = self::usuario();

        return $user !== null && $user['role'] === User::ROLE_ADMIN;
    }

    public static function logout(): void
    {
        $user = self::usuario();

        if ($user !== null) {
            Logger::access('logout', $user['email'], (int) $user['id']);
        }

        self::encerrarSessao();
    }

    private static function bloqueadoPorTentativas(string $ip): bool
    {
        return (new AccessLog())->failedAttemptsFromIp($ip, self::JANELA_MINUTOS) >= self::MAX_FALHAS;
    }

    private static function encerrarSessao(): void
    {
        self::$cache = null;
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
    }
}
