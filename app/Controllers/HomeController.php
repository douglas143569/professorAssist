<?php

namespace App\Controllers;

use App\Models\Atividade;
use App\Models\Conteudo;
use App\Models\Disciplina;
use App\Models\PlanoAula;
use App\Models\Questao;

class HomeController extends AppController
{
    public function index(): void
    {
        $prof = $this->professor();

        $questaoModel = new Questao();

        $this->view('home.index', [
            'title' => 'Início',
            'professor' => $prof,
            'stats' => [
                'disciplinas' => (new Disciplina())->countByUser($prof['id']),
                'planos' => (new PlanoAula())->countByUser($prof['id']),
                'atividades' => (new Atividade())->countByUser($prof['id']),
                'conteudos' => (new Conteudo())->countByUser($prof['id']),
                'questoes' => $questaoModel->countByUser($prof['id']),
                'questoes_aprovadas' => $questaoModel->countByUser($prof['id'], 'aprovado'),
            ],
            'disciplinas' => (new Disciplina())->byUser($prof['id']),
        ]);
    }
}
