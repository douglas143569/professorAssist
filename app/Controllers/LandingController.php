<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Services\Auth;

/**
 * Pagina de apresentacao (rota "/").
 *
 * Estende Controller (publico). Visitante ve a apresentacao com o botao de
 * cadastro; quem ja esta logado continua caindo no dashboard, como antes.
 */
class LandingController extends Controller
{
    public function index(): void
    {
        Auth::iniciarSessao();

        $usuario = Auth::usuario();

        if ($usuario !== null) {
            // Aluno nao usa o dashboard do professor.
            if ($usuario['role'] === User::ROLE_ALUNO) {
                $this->redirect('/aluno');
            }

            // HomeController valida login/assinatura no construtor.
            (new HomeController())->index();
            return;
        }

        $this->viewSemLayout('landing.index', [
            'plans' => (new Plan())->active(),
        ]);
    }
}
