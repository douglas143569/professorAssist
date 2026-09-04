<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Logger;

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

        // Toda escrita passa por aqui: uma rota POST nova ja nasce protegida.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->exigirTokenValido();
        }
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
     * Recusa POSTs sem token valido (CSRF).
     *
     * Falha fechada: sem token valido, nada e gravado. Na pratica isso
     * acontece em dois casos -- um site de terceiros tentando disparar uma
     * acao na sua sessao, ou uma aba que ficou aberta tempo demais.
     */
    private function exigirTokenValido(): void
    {
        if (Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            return;
        }

        Logger::warning('POST recusado por token CSRF invalido', [
            'rota' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => $this->usuario['id'] ?? null,
        ]);

        http_response_code(403);

        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
            . '<title>Pedido nao autorizado</title>'
            . '<link rel="stylesheet" href="/assets/css/app.css"></head><body>'
            . '<div style="max-width:520px;margin:80px auto;padding:0 20px;font-family:system-ui">'
            . '<h1>Pedido não autorizado</h1>'
            . '<p>Este envio não trouxe o código de segurança da sua sessão, então nada foi alterado.</p>'
            . '<p>Isso costuma acontecer quando a página ficou aberta por muito tempo. '
            . 'Volte, recarregue a página e tente de novo.</p>'
            . '<p><a href="/">Voltar ao início</a></p>'
            . '</div></body></html>';

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
