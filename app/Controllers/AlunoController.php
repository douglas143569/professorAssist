<?php

namespace App\Controllers;

use App\Models\Aviso;
use App\Models\Material;
use App\Models\Matricula;
use App\Models\Tarefa;
use App\Models\TarefaEntrega;
use App\Services\Logger;
use App\Services\Upload;
use App\Services\UploadException;

/**
 * Area do aluno: painel, tarefas, entregas, notas e mural de avisos.
 * Tudo escopado ao proprio aluno e as turmas em que ele esta matriculado.
 */
class AlunoController extends AlunoBaseController
{
    public function index(): void
    {
        $aluno = $this->aluno();

        $tarefas = (new Tarefa())->publicadasParaAluno((int) $aluno['id']);
        $pendentes = array_values(array_filter($tarefas, fn ($t) => $t['status'] === 'pendente'));

        $this->viewAluno('aluno.index', [
            'title' => 'Início',
            'aluno' => $aluno,
            'turmas' => (new Matricula())->turmasDoAluno((int) $aluno['id']),
            'pendentes' => array_slice($pendentes, 0, 5),
            'proximas' => array_slice($tarefas, 0, 6),
            'resumo' => (new TarefaEntrega())->resumoDoAluno((int) $aluno['id']),
            'avisos' => (new Aviso())->paraAluno((int) $aluno['id'], true),
        ]);
    }

    public function tarefas(): void
    {
        $aluno = $this->aluno();

        $this->viewAluno('aluno.tarefas', [
            'title' => 'Minhas tarefas',
            'aluno' => $aluno,
            'tarefas' => (new Tarefa())->publicadasParaAluno((int) $aluno['id']),
        ]);
    }

    public function tarefa(string $id): void
    {
        $aluno = $this->aluno();
        $tarefa = (new Tarefa())->findParaAluno((int) $id, (int) $aluno['id']);

        if ($tarefa === null) {
            $this->notFound('Tarefa não encontrada.');
            return;
        }

        $this->viewAluno('aluno.tarefa', [
            'title' => $tarefa['titulo'],
            'aluno' => $aluno,
            'tarefa' => $tarefa,
            'entrega' => (new TarefaEntrega())->doAluno((int) $id, (int) $aluno['id']),
        ]);
    }

    public function entregar(string $id): void
    {
        $aluno = $this->aluno();
        $tarefaModel = new Tarefa();
        $tarefa = $tarefaModel->findParaAluno((int) $id, (int) $aluno['id']);

        if ($tarefa === null) {
            $this->notFound('Tarefa não encontrada.');
            return;
        }

        $entregaModel = new TarefaEntrega();
        $atual = $entregaModel->doAluno((int) $id, (int) $aluno['id']);

        if ($atual !== null && $atual['status'] === 'corrigida') {
            $this->flash('Esta tarefa já foi corrigida e não aceita novo envio.');
            $this->redirect('/aluno/tarefas/' . (int) $id);
            return;
        }

        $texto = trim((string) ($_POST['resposta_texto'] ?? ''));
        $arquivo = null;

        $temArquivo = isset($_FILES['arquivo']) && ($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($temArquivo) {
            try {
                $arquivo = Upload::salvarEntrega($_FILES['arquivo'], (int) $id, (int) $aluno['id']);
            } catch (UploadException $e) {
                $this->flash($e->getMessage());
                $this->redirect('/aluno/tarefas/' . (int) $id);
                return;
            }
        }

        if ($texto === '' && $arquivo === null) {
            $this->flash('Escreva uma resposta ou anexe um arquivo.');
            $this->redirect('/aluno/tarefas/' . (int) $id);
            return;
        }

        // Troca de arquivo: remove o antigo para nao deixar lixo em disco.
        if ($arquivo !== null && $atual !== null && !empty($atual['arquivo_path'])) {
            Upload::remover($atual['arquivo_path']);
        }

        $entregaModel->enviar((int) $id, (int) $aluno['id'], $texto, $arquivo);

        Logger::activity('tarefa.entregue', [
            'user_id' => (int) $aluno['id'],
            'entity_type' => 'tarefa',
            'entity_id' => (int) $id,
            'description' => "Aluno entregou a tarefa {$id}",
        ]);

        $this->flash('Entrega enviada.');
        $this->redirect('/aluno/tarefas/' . (int) $id);
    }

    public function notas(): void
    {
        $aluno = $this->aluno();

        $this->viewAluno('aluno.notas', [
            'title' => 'Minhas notas',
            'aluno' => $aluno,
            'notas' => (new TarefaEntrega())->notasDoAluno((int) $aluno['id']),
            'resumo' => (new TarefaEntrega())->resumoDoAluno((int) $aluno['id']),
        ]);
    }

    public function avisos(): void
    {
        $aluno = $this->aluno();

        $this->viewAluno('aluno.avisos', [
            'title' => 'Avisos',
            'aluno' => $aluno,
            'avisos' => (new Aviso())->paraAluno((int) $aluno['id']),
        ]);
    }

    public function materiais(): void
    {
        $aluno = $this->aluno();

        $this->viewAluno('aluno.materiais', [
            'title' => 'Materiais',
            'aluno' => $aluno,
            'materiais' => (new Material())->paraAluno((int) $aluno['id']),
        ]);
    }

    public function arquivoEntrega(string $id): void
    {
        $aluno = $this->aluno();
        $entrega = (new TarefaEntrega())->find((int) $id);

        if ($entrega === null || (int) $entrega['aluno_id'] !== (int) $aluno['id'] || empty($entrega['arquivo_path'])) {
            $this->notFound('Arquivo não encontrado.');
            return;
        }

        $this->enviarArquivo($entrega['arquivo_path'], $entrega['arquivo_nome']);
    }

    /** Stream de um arquivo de storage/uploads com os cabeçalhos de download. */
    protected function enviarArquivo(string $path, ?string $nomeOriginal): void
    {
        $abs = Upload::caminhoAbsoluto($path);

        if (!is_file($abs)) {
            $this->notFound('Arquivo não encontrado.');
            return;
        }

        $nome = $nomeOriginal !== null && $nomeOriginal !== '' ? $nomeOriginal : basename($abs);
        $nome = str_replace(['"', "\r", "\n"], '', $nome);

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($abs));
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
    }
}
