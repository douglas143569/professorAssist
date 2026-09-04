<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Services\Auth;

/**
 * Base dos controllers da area logada.
 *
 * A checagem de login mora no CONSTRUTOR de proposito: como todo controller
 * da aplicacao estende esta classe, qualquer acao nova ja nasce protegida,
 * mesmo que o autor esqueca de chamar professor(). Quem NAO deve exigir
 * login (ex: AuthController) estende App\Core\Controller direto.
 */
abstract class AppController extends Controller
{
    protected array $usuario;

    public function __construct()
    {
        Auth::iniciarSessao();

        $usuario = Auth::usuario();

        if ($usuario === null) {
            $this->exigirLogin();
        }

        $this->usuario = $usuario;
    }

    /**
     * Professor dono do conteudo desta requisicao.
     */
    protected function professor(): array
    {
        return $this->usuario;
    }

    protected function ehAdmin(): bool
    {
        return $this->usuario['role'] === User::ROLE_ADMIN;
    }

    /**
     * Barra a acao para quem nao e administrador. Sera a base da futura
     * pagina de controle de acessos.
     */
    protected function exigirAdmin(): void
    {
        if (!$this->ehAdmin()) {
            http_response_code(403);
            echo 'Acesso restrito ao administrador.';
            exit;
        }
    }

    protected function flash(string $mensagem): void
    {
        Auth::iniciarSessao();
        $_SESSION['flash'] = $mensagem;
    }

    protected function notFound(string $mensagem = 'Registro nao encontrado.'): void
    {
        http_response_code(404);
        echo htmlspecialchars($mensagem);
        exit;
    }

    /**
     * Manda para o login guardando o destino, para voltar aqui depois de entrar.
     */
    private function exigirLogin(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $_SESSION['destino'] = $_SERVER['REQUEST_URI'] ?? '/';
        }

        $this->redirect('/login');
    }
}
