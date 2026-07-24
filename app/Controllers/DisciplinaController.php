<?php

namespace App\Controllers;

use App\Models\Disciplina;
use App\Models\Modulo;
use App\Services\Logger;

class DisciplinaController extends AppController
{
    public function index(): void
    {
        $prof = $this->professor();
        $this->view('disciplinas.index', [
            'title' => 'Disciplinas',
            'professor' => $prof,
            'disciplinas' => (new Disciplina())->byUser($prof['id']),
        ]);
    }

    public function store(): void
    {
        $prof = $this->professor();

        $nome = trim($_POST['nome'] ?? '');
        $etapa = ($_POST['etapa'] ?? '') === 'EM' ? 'EM' : 'EF';
        $anoSerie = trim($_POST['ano_serie'] ?? '');

        if ($nome === '') {
            $this->flash('Informe o nome da disciplina.');
            $this->redirect('/disciplinas');
            return;
        }

        $id = (new Disciplina())->create([
            'user_id' => $prof['id'],
            'nome' => $nome,
            'etapa' => $etapa,
            'ano_serie' => $anoSerie,
        ]);

        Logger::activity('disciplina.created', [
            'entity_type' => 'disciplina',
            'entity_id' => $id,
            'description' => "Disciplina criada: {$nome}",
        ]);

        $this->flash('Disciplina criada.');
        $this->redirect('/disciplinas/' . $id);
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $disciplina = (new Disciplina())->find((int) $id, $prof['id']);

        if (!$disciplina) {
            $this->notFound('Disciplina nao encontrada.');
            return;
        }

        $this->view('disciplinas.show', [
            'title' => $disciplina['nome'],
            'disciplina' => $disciplina,
            'modulos' => (new Modulo())->byDisciplina((int) $id),
        ]);
    }
}
