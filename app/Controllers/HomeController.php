<?php

namespace App\Controllers;

use App\Models\Atividade;
use App\Models\Conteudo;
use App\Models\Escola;
use App\Models\PlanoAula;
use App\Models\Prova;
use App\Models\Questao;
use App\Models\Turma;

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
                'escolas' => (new Escola())->countByUser($prof['id']),
                'turmas' => (new Turma())->countByUser($prof['id']),
                'planos' => (new PlanoAula())->countByUser($prof['id']),
                'atividades' => (new Atividade())->countByUser($prof['id']),
                'conteudos' => (new Conteudo())->countByUser($prof['id']),
                'questoes' => $questaoModel->countByUser($prof['id']),
                'questoes_aprovadas' => $questaoModel->countByUser($prof['id'], 'aprovado'),
                'provas' => (new Prova())->countByUser($prof['id']),
            ],
            'turmas' => (new Turma())->byUser($prof['id']),
        ]);
    }
}
