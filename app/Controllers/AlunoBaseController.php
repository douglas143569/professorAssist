<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Logger;

/**
 * Base dos controllers da AREA DO ALUNO.
 *
 * Espelha o AppController do professor, mas exige papel 'aluno' e renderiza
 * com o layout proprio do aluno (sem a barra lateral do professor). Assim o
 * aluno nunca ve nada da area administrativa.
 */
abstract class AlunoBaseController extends Controller
{
    protected array $aluno;

    public function __construct()
    {
        Auth::iniciarSessao();

        $usuario = Auth::usuario();

        if ($usuario === null || $usuario['role'] !== User::ROLE_ALUNO) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                $_SESSION['destino'] = $_SERVER['REQUEST_URI'] ?? '/aluno';
            }
            // Professor logado que caiu aqui volta para a area dele.
            $this->redirect($usuario !== null ? '/' : '/aluno/login');
        }

        $this->aluno = $usuario;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->exigirTokenValido();
        }
    }

    protected function aluno(): array
    {
        return $this->aluno;
    }

    /** Renderiza uma view dentro do layout do aluno. */
    protected function viewAluno(string $view, array $data = []): void
    {
        extract($data);

        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View nao encontrada: {$view}");
        }

        require __DIR__ . '/../Views/layouts/aluno_start.php';
        require $viewPath;
        require __DIR__ . '/../Views/layouts/aluno_end.php';
    }

    protected function flash(string $mensagem): void
    {
        Auth::iniciarSessao();
        $_SESSION['flash'] = $mensagem;
    }

    protected function notFound(string $mensagem = 'Pagina nao encontrada.'): void
    {
        http_response_code(404);
        echo htmlspecialchars($mensagem);
        exit;
    }

    /** Mesma defesa de CSRF do AppController: falha fechada. */
    private function exigirTokenValido(): void
    {
        if (Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            return;
        }

        Logger::warning('POST recusado por token CSRF invalido (aluno)', [
            'rota' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => $this->aluno['id'] ?? null,
        ]);

        http_response_code(403);
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
            . '<title>Pedido nao autorizado</title>'
            . '<link rel="stylesheet" href="/assets/css/app.css"></head><body>'
            . '<div style="max-width:520px;margin:80px auto;padding:0 20px;font-family:system-ui">'
            . '<h1>Pedido não autorizado</h1>'
            . '<p>Este envio não trouxe o código de segurança da sua sessão, então nada foi alterado.</p>'
            . '<p>Volte, recarregue a página e tente de novo.</p>'
            . '<p><a href="/aluno">Voltar ao início</a></p>'
            . '</div></body></html>';
        exit;
    }
}
