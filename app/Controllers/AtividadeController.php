<?php

namespace App\Controllers;

use App\Models\Atividade;
use App\Models\Modulo;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

class AtividadeController extends AppController
{
    /** Sugere atividades com a IA a partir do modulo. */
    public function gerar(string $moduloId): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $moduloId, $prof['id']);

        if (!$modulo) {
            $this->notFound('Modulo nao encontrado.');
            return;
        }

        $quantidade = (int) ($_POST['quantidade'] ?? 4);
        $foco = trim($_POST['foco'] ?? '');
        $bncc = !empty($modulo['codigos_bncc']) ? trim(explode(',', $modulo['codigos_bncc'])[0]) : null;

        try {
            $atividades = (new AI())->sugerirAtividades(
                tema: $modulo['titulo'],
                objetivos: $modulo['objetivos'] ?? null,
                habilidadeBncc: $bncc,
                etapa: $modulo['disciplina_etapa'],
                quantidade: $quantidade,
                foco: $foco,
                userId: (int) $prof['id'],
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel sugerir atividades: ' . $e->getMessage());
            $this->redirect('/modulos/' . $moduloId);
            return;
        }

        $model = new Atividade();
        foreach ($atividades as $a) {
            $model->create([
                'modulo_id' => (int) $moduloId,
                'titulo' => $a['titulo'],
                'descricao' => $a['descricao'],
                'formato' => $a['formato'],
                'duracao' => $a['duracao'],
                'origem' => 'ia',
                'status' => 'rascunho',
            ]);
        }

        $n = count($atividades);
        Logger::activity('atividade.generated', [
            'entity_type' => 'modulo',
            'entity_id' => (int) $moduloId,
            'description' => "IA sugeriu {$n} atividades",
        ]);

        $this->flash("{$n} atividade(s) sugerida(s) como rascunho. Revise e aprove.");
        $this->redirect('/modulos/' . $moduloId);
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $atividade = (new Atividade())->find((int) $id, $prof['id']);

        if (!$atividade) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $this->view('atividades.show', [
            'title' => 'Revisar atividade',
            'atividade' => $atividade,
        ]);
    }

    public function update(string $id): void
    {
        $prof = $this->professor();
        $model = new Atividade();
        $atividade = $model->find((int) $id, $prof['id']);

        if (!$atividade) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo === '') {
            $this->flash('Informe o título da atividade.');
            $this->redirect('/atividades/' . $id);
            return;
        }

        $formatos = ['individual', 'grupo', 'discussao', 'pratica', 'projeto', 'jogo'];
        $formato = in_array($_POST['formato'] ?? '', $formatos, true) ? $_POST['formato'] : '';

        $model->update((int) $id, [
            'titulo' => $titulo,
            'descricao' => $_POST['descricao'] ?? '',
            'formato' => $formato,
            'duracao' => trim($_POST['duracao'] ?? ''),
            'status' => $atividade['status'],
        ]);

        $this->flash('Alteracoes salvas.');
        $this->redirect('/atividades/' . $id);
    }

    public function aprovar(string $id): void
    {
        $prof = $this->professor();
        $model = new Atividade();
        $atividade = $model->find((int) $id, $prof['id']);

        if (!$atividade) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $model->aprovar((int) $id);
        $this->flash('Atividade aprovada.');
        $this->redirect('/atividades/' . $id);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new Atividade();
        $atividade = $model->find((int) $id, $prof['id']);

        if (!$atividade) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $model->delete((int) $id);
        $this->flash('Atividade excluida.');
        $this->redirect('/modulos/' . (int) $atividade['modulo_id']);
    }
}
