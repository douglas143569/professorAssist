<?php

namespace App\Controllers;

use App\Models\Disciplina;
use App\Models\Turma;
use App\Services\Logger;

class TurmaController extends AppController
{
    public function index(): void
    {
        $prof = $this->professor();
        $this->view('turmas.index', [
            'title' => 'Turmas',
            'professor' => $prof,
            'turmas' => (new Turma())->byUser($prof['id']),
        ]);
    }

    public function store(): void
    {
        $prof = $this->professor();

        $nome = trim($_POST['nome'] ?? '');
        $etapa = ($_POST['etapa'] ?? '') === 'EM' ? 'EM' : 'EF';
        $anoSerie = trim($_POST['ano_serie'] ?? '');

        if ($nome === '') {
            $this->flash('Informe o nome da turma.');
            $this->redirect('/turmas');
            return;
        }

        $id = (new Turma())->create([
            'user_id' => $prof['id'],
            'nome' => $nome,
            'etapa' => $etapa,
            'ano_serie' => $anoSerie,
        ]);

        Logger::activity('turma.created', [
            'entity_type' => 'turma',
            'entity_id' => $id,
            'description' => "Turma criada: {$nome}",
        ]);

        $this->flash('Turma criada.');
        $this->redirect('/turmas/' . $id);
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $turma = (new Turma())->find((int) $id, $prof['id']);

        if (!$turma) {
            $this->notFound('Turma nao encontrada.');
            return;
        }

        $this->view('turmas.show', [
            'title' => $turma['nome'],
            'turma' => $turma,
            'materias' => (new Disciplina())->byTurma((int) $id),
        ]);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new Turma();

        if (!$model->find((int) $id, $prof['id'])) {
            $this->notFound('Turma nao encontrada.');
            return;
        }

        $model->delete((int) $id);
        $this->flash('Turma excluída.');
        $this->redirect('/turmas');
    }
}
