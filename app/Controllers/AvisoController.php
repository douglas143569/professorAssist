<?php

namespace App\Controllers;

use App\Models\Aviso;
use App\Models\Turma;
use App\Services\Logger;

/**
 * Mural de avisos da turma (lado do professor). O aluno so le.
 */
class AvisoController extends AppController
{
    public function store(string $turmaId): void
    {
        $prof = $this->professor();
        $turma = (new Turma())->find((int) $turmaId, $prof['id']);

        if (!$turma) {
            $this->notFound('Turma nao encontrada.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $corpo = trim($_POST['corpo'] ?? '');

        if ($titulo === '' || $corpo === '') {
            $this->flash('Preencha o título e o texto do aviso.');
            $this->redirect('/turmas/' . (int) $turmaId);
            return;
        }

        $id = (new Aviso())->criar([
            'turma_id' => (int) $turmaId,
            'user_id' => (int) $prof['id'],
            'titulo' => $titulo,
            'corpo' => $corpo,
            'fixado' => !empty($_POST['fixado']),
        ]);

        Logger::activity('aviso.created', [
            'entity_type' => 'aviso',
            'entity_id' => $id,
            'description' => "Aviso publicado na turma {$turmaId}: {$titulo}",
        ]);

        $this->flash('Aviso publicado.');
        $this->redirect('/turmas/' . (int) $turmaId);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new Aviso();
        $aviso = $model->findDoProfessor((int) $id, $prof['id']);

        if (!$aviso) {
            $this->notFound('Aviso nao encontrado.');
            return;
        }

        $model->excluir((int) $id);

        $this->flash('Aviso excluído.');
        $this->redirect('/turmas/' . (int) $aviso['turma_id']);
    }
}
