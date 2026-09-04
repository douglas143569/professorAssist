<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\Auth;

/**
 * Login e logout.
 *
 * Estende Controller (e nao AppController) porque estas telas precisam
 * funcionar justamente para quem AINDA nao esta logado.
 */
class AuthController extends Controller
{
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

    public function sair(): void
    {
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
