<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

/**
 * Base dos controllers da aplicacao. Resolve o professor logado e oferece
 * mensagens flash.
 *
 * MVP: ainda nao ha tela de login (RF-01). Enquanto isso, usamos um
 * "Professor Demo" fixo para satisfazer o vinculo user_id. Quando o login
 * existir, troque professor() por quem estiver na sessao autenticada.
 */
abstract class AppController extends Controller
{
    protected function professor(): array
    {
        $this->bootSession();
        $prof = (new User())->professorDemo();
        $_SESSION['user_id'] = $prof['id']; // usado pelo Logger para auditoria
        return $prof;
    }

    protected function flash(string $mensagem): void
    {
        $this->bootSession();
        $_SESSION['flash'] = $mensagem;
    }

    protected function notFound(string $mensagem = 'Registro nao encontrado.'): void
    {
        http_response_code(404);
        echo htmlspecialchars($mensagem);
        exit;
    }

    private function bootSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
