<?php

namespace App\Controllers;

use App\Models\Matricula;
use App\Models\Turma;

/**
 * Gestao do roster da turma pelo professor. Por ora: ativar/inativar a
 * matricula de um aluno (base da futura chamada/presenca).
 */
class MatriculaController extends AppController
{
    public function alternarSituacao(string $turmaId, string $matriculaId): void
    {
        $prof = $this->professor();
        $turma = (new Turma())->find((int) $turmaId, $prof['id']);

        if (!$turma) {
            $this->notFound('Turma nao encontrada.');
            return;
        }

        $model = new Matricula();
        $matricula = $model->find((int) $matriculaId, (int) $turmaId);

        if (!$matricula) {
            $this->notFound('Matrícula nao encontrada.');
            return;
        }

        $nova = $matricula['situacao'] === 'ativa' ? 'inativa' : 'ativa';
        $model->definirSituacao((int) $matriculaId, $nova);

        $this->flash($nova === 'ativa' ? 'Aluno reativado na turma.' : 'Aluno inativado na turma.');
        $this->redirect('/turmas/' . (int) $turmaId);
    }
}
