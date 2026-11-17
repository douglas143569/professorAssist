<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Logger;
use App\Models\Subscription;
use App\Models\UserAccessOverride;

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

        // Aluno nunca entra na area do professor: um unico ponto de bloqueio
        // para toda rota que herda deste controller.
        if ($usuario['role'] === User::ROLE_ALUNO) {
            $this->redirect('/aluno');
        }

        $this->usuario = $usuario;

        // Somente professores pagam; overrides do admin têm precedência sobre pagamentos.
        $rota = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        // /meus-dados (LGPD) nunca pode ser barrado por assinatura vencida.
        $rotasDeAssinatura = str_starts_with($rota, '/assinatura') || str_starts_with($rota, '/meus-dados');
        if ($usuario['role'] === User::ROLE_PROFESSOR && !$rotasDeAssinatura) {
            $override = (new UserAccessOverride())->forUser((int) $usuario['id']);
            $bloqueado = ($override['mode'] ?? null) === 'blocked';
            $liberado = ($override['mode'] ?? null) === 'granted';
            $assinaturaAtiva = (new Subscription())->currentForUser((int) $usuario['id']) !== null;

            if ($bloqueado || (!$liberado && !$assinaturaAtiva)) {
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                    $_SESSION['destino'] = $_SERVER['REQUEST_URI'] ?? '/';
                }
                $this->redirect($bloqueado ? '/assinatura?bloqueado=1' : '/assinatura');
            }
        }

        // Toda escrita passa por aqui: uma rota POST nova ja nasce protegida.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->recusarEnvioGrandeDemais();
            $this->exigirTokenValido();
        }
    }

    /**
     * Quando o envio passa de post_max_size, o PHP entrega $_POST e $_FILES
     * VAZIOS e a acao "nao faz nada". Aqui isso vira uma mensagem clara.
     */
    private function recusarEnvioGrandeDemais(): void
    {
        $tamanho = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

        if ($tamanho > 0 && $_POST === [] && $_FILES === []) {
            $limite = ini_get('post_max_size') ?: '?';
            http_response_code(413);
            $this->flash(
                'O envio ficou grande demais para o servidor (limite atual: ' . $limite . '). '
                . 'Aumente post_max_size e upload_max_filesize no php.ini e reinicie o servidor.'
            );
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
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
