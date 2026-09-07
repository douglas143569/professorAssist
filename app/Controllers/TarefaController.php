<?php

namespace App\Controllers;

use App\Models\Tarefa;
use App\Models\TarefaEntrega;
use App\Models\Turma;
use App\Services\Logger;
use App\Services\Upload;

/**
 * Lado do PROFESSOR das tarefas: criar, publicar, ver entregas e corrigir.
 * Toda acao confere que a turma da tarefa pertence ao professor logado.
 */
class TarefaController extends AppController
{
    /** Cria uma tarefa (rascunho) dentro de uma turma. */
    public function store(string $turmaId): void
    {
        $prof = $this->professor();
        $turma = (new Turma())->find((int) $turmaId, $prof['id']);

        if (!$turma) {
            $this->notFound('Turma nao encontrada.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');

        if ($titulo === '') {
            $this->flash('Informe o título da tarefa.');
            $this->redirect('/turmas/' . (int) $turmaId);
            return;
        }

        $id = (new Tarefa())->criar([
            'turma_id' => (int) $turmaId,
            'user_id' => (int) $prof['id'],
            'disciplina_id' => (int) ($_POST['disciplina_id'] ?? 0) ?: null,
            'titulo' => $titulo,
            'descricao' => trim($_POST['descricao'] ?? ''),
            'tipo_entrega' => $_POST['tipo_entrega'] ?? 'ambos',
            'entrega_ate' => $this->normalizarPrazo($_POST['entrega_ate'] ?? ''),
        ]);

        Logger::activity('tarefa.created', [
            'entity_type' => 'tarefa',
            'entity_id' => $id,
            'description' => "Tarefa criada: {$titulo}",
        ]);

        $this->flash('Tarefa criada. Revise e publique para os alunos verem.');
        $this->redirect('/tarefas/' . $id);
    }

    /** Tela da tarefa: dados + grade de entregas dos alunos. */
    public function show(string $id): void
    {
        $prof = $this->professor();
        $tarefa = (new Tarefa())->findDoProfessor((int) $id, $prof['id']);

        if (!$tarefa) {
            $this->notFound('Tarefa nao encontrada.');
            return;
        }

        $this->view('tarefas.show', [
            'title' => $tarefa['titulo'],
            'tarefa' => $tarefa,
            'entregas' => (new TarefaEntrega())->daTarefa((int) $id),
            'tiposEntrega' => Tarefa::TIPOS_ENTREGA,
        ]);
    }

    public function update(string $id): void
    {
        $prof = $this->professor();
        $model = new Tarefa();
        $tarefa = $model->findDoProfessor((int) $id, $prof['id']);

        if (!$tarefa) {
            $this->notFound('Tarefa nao encontrada.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');

        if ($titulo === '') {
            $this->flash('Informe o título da tarefa.');
            $this->redirect('/tarefas/' . (int) $id);
            return;
        }

        $model->atualizar((int) $id, [
            'titulo' => $titulo,
            'descricao' => trim($_POST['descricao'] ?? ''),
            'tipo_entrega' => $_POST['tipo_entrega'] ?? 'ambos',
            'entrega_ate' => $this->normalizarPrazo($_POST['entrega_ate'] ?? ''),
        ]);

        $this->flash('Tarefa atualizada.');
        $this->redirect('/tarefas/' . (int) $id);
    }

    public function publicar(string $id): void
    {
        $prof = $this->professor();
        $model = new Tarefa();
        $tarefa = $model->findDoProfessor((int) $id, $prof['id']);

        if (!$tarefa) {
            $this->notFound('Tarefa nao encontrada.');
            return;
        }

        $model->publicar((int) $id);

        Logger::activity('tarefa.publicada', [
            'entity_type' => 'tarefa',
            'entity_id' => (int) $id,
            'description' => "Tarefa publicada: {$tarefa['titulo']}",
        ]);

        $this->flash('Tarefa publicada. Os alunos da turma já podem ver.');
        $this->redirect('/tarefas/' . (int) $id);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new Tarefa();
        $tarefa = $model->findDoProfessor((int) $id, $prof['id']);

        if (!$tarefa) {
            $this->notFound('Tarefa nao encontrada.');
            return;
        }

        // Remove os arquivos das entregas antes de apagar (o banco cai por CASCADE).
        foreach ((new TarefaEntrega())->daTarefa((int) $id) as $e) {
            if (!empty($e['arquivo_path'])) {
                Upload::remover($e['arquivo_path']);
            }
        }

        $model->excluir((int) $id);

        $this->flash('Tarefa excluída.');
        $this->redirect('/turmas/' . (int) $tarefa['turma_id']);
    }

    /** Lança nota + feedback para a entrega de um aluno. */
    public function corrigir(string $id, string $alunoId): void
    {
        $prof = $this->professor();
        $tarefa = (new Tarefa())->findDoProfessor((int) $id, $prof['id']);

        if (!$tarefa) {
            $this->notFound('Tarefa nao encontrada.');
            return;
        }

        $entregaModel = new TarefaEntrega();
        $entrega = $entregaModel->doAluno((int) $id, (int) $alunoId);

        if (!$entrega) {
            $this->flash('Este aluno ainda não entregou.');
            $this->redirect('/tarefas/' . (int) $id);
            return;
        }

        $notaBruta = str_replace(',', '.', trim((string) ($_POST['nota'] ?? '')));

        if ($notaBruta === '' || !is_numeric($notaBruta)) {
            $this->flash('Informe uma nota numérica.');
            $this->redirect('/tarefas/' . (int) $id);
            return;
        }

        $nota = max(0, min(10, (float) $notaBruta));

        $entregaModel->corrigir((int) $entrega['id'], $nota, trim((string) ($_POST['feedback'] ?? '')));

        Logger::activity('tarefa.corrigida', [
            'entity_type' => 'tarefa',
            'entity_id' => (int) $id,
            'description' => "Corrigiu a entrega do aluno {$alunoId} na tarefa {$id} (nota {$nota})",
        ]);

        $this->flash('Nota lançada.');
        $this->redirect('/tarefas/' . (int) $id);
    }

    /** Download do arquivo entregue por um aluno. */
    public function arquivoEntrega(string $id, string $alunoId): void
    {
        $prof = $this->professor();
        $tarefa = (new Tarefa())->findDoProfessor((int) $id, $prof['id']);

        if (!$tarefa) {
            $this->notFound('Tarefa nao encontrada.');
            return;
        }

        $entrega = (new TarefaEntrega())->doAluno((int) $id, (int) $alunoId);

        if (!$entrega || empty($entrega['arquivo_path'])) {
            $this->notFound('Arquivo nao encontrado.');
            return;
        }

        $abs = Upload::caminhoAbsoluto($entrega['arquivo_path']);

        if (!is_file($abs)) {
            $this->notFound('Arquivo nao encontrado.');
            return;
        }

        $nome = str_replace(['"', "\r", "\n"], '', (string) ($entrega['arquivo_nome'] ?: basename($abs)));

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($abs));
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
    }

    /** "2026-09-10T14:30" (datetime-local) -> "2026-09-10 14:30:00" ou '' */
    private function normalizarPrazo(string $valor): string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return '';
        }

        $ts = strtotime($valor);

        return $ts !== false ? date('Y-m-d H:i:s', $ts) : '';
    }
}
