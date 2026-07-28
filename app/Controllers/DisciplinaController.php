<?php

namespace App\Controllers;

use App\Models\Disciplina;
use App\Models\Modulo;
use App\Models\Turma;
use App\Services\Logger;

/**
 * Materia (tabela 'disciplinas'): uma disciplina dentro de uma Turma.
 * A Materia e criada sob uma turma e herda a etapa/ano da turma.
 */
class DisciplinaController extends AppController
{
    /** Rota antiga /disciplinas: agora o topo e Turmas. */
    public function index(): void
    {
        $this->professor();
        $this->redirect('/turmas');
    }

    /** Cria uma materia dentro de uma turma. */
    public function store(string $turmaId): void
    {
        $prof = $this->professor();
        $turma = (new Turma())->find((int) $turmaId, $prof['id']);

        if (!$turma) {
            $this->notFound('Turma nao encontrada.');
            return;
        }

        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            $this->flash('Informe o nome da matéria.');
            $this->redirect('/turmas/' . $turmaId);
            return;
        }

        $id = (new Disciplina())->create([
            'user_id' => $prof['id'],
            'turma_id' => (int) $turmaId,
            'nome' => $nome,
            // Materia herda etapa/ano da turma.
            'etapa' => $turma['etapa'] ?? 'EF',
            'ano_serie' => $turma['ano_serie'] ?? '',
        ]);

        Logger::activity('materia.created', [
            'entity_type' => 'disciplina',
            'entity_id' => $id,
            'description' => "Materia criada: {$nome}",
        ]);

        $this->flash('Matéria criada.');
        $this->redirect('/disciplinas/' . $id);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new Disciplina();
        $materia = $model->find((int) $id, $prof['id']);

        if (!$materia) {
            $this->notFound('Matéria nao encontrada.');
            return;
        }

        $model->delete((int) $id);

        Logger::activity('materia.deleted', [
            'entity_type' => 'disciplina',
            'entity_id' => (int) $id,
            'description' => "Materia excluida: {$materia['nome']}",
        ]);

        $this->flash('Matéria excluída.');
        $this->redirect('/turmas/' . (int) $materia['turma_id']);
    }

    /** Pagina da Materia: lista os Temas da aula (modulos). */
    public function show(string $id): void
    {
        $prof = $this->professor();
        $disciplina = (new Disciplina())->find((int) $id, $prof['id']);

        if (!$disciplina) {
            $this->notFound('Matéria nao encontrada.');
            return;
        }

        $this->view('disciplinas.show', [
            'title' => $disciplina['nome'],
            'disciplina' => $disciplina,
            'modulos' => (new Modulo())->byDisciplina((int) $id),
        ]);
    }
}
