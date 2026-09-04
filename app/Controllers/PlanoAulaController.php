<?php

namespace App\Controllers;

use App\Models\Modulo;
use App\Models\PlanoAula;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

class PlanoAulaController extends AppController
{
    /** Gera o rascunho do plano de aula com a IA. */
    public function gerar(string $moduloId): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $moduloId, $prof['id']);

        if (!$modulo) {
            $this->notFound('Modulo nao encontrado.');
            return;
        }

        $duracao = trim($_POST['duracao'] ?? '') ?: '1 aula de 50 min';
        $bncc = !empty($modulo['codigos_bncc']) ? $modulo['codigos_bncc'] : null;

        // So os planos da MESMA duracao contam como "ja existente": pedir a
        // versao de 100 min de um tema nao e pedir mais do mesmo, e sim outro
        // formato de aula -- ali a IA deve trabalhar sem essa amarra.
        $mesmaDuracao = array_values(array_filter(
            (new PlanoAula())->byModulo((int) $moduloId),
            fn(array $p) => ($p['duracao'] ?? '') === $duracao
        ));

        try {
            $texto = (new AI())->gerarPlanoAula(
                tema: $modulo['titulo'],
                objetivos: $modulo['objetivos'] ?? null,
                habilidadeBncc: $bncc,
                etapa: $modulo['disciplina_etapa'],
                duracao: $duracao,
                userId: (int) $prof['id'],
                jaAbordado: AI::resumirMateriais($mesmaDuracao),
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel gerar o plano: ' . $e->getMessage());
            $this->redirect('/modulos/' . $moduloId);
            return;
        }

        $numero = count($mesmaDuracao) + 1;

        $id = (new PlanoAula())->create([
            'modulo_id' => (int) $moduloId,
            'titulo' => $numero > 1
                ? "Plano de aula {$numero}: " . $modulo['titulo']
                : 'Plano de aula: ' . $modulo['titulo'],
            'duracao' => $duracao,
            'corpo' => $texto,
            'origem' => 'ia',
            'status' => 'rascunho',
        ]);

        Logger::activity('plano.generated', [
            'entity_type' => 'plano_aula',
            'entity_id' => $id,
            'description' => 'IA gerou um plano de aula',
        ]);

        $this->flash($numero > 1
            ? "Novo plano criado para este tema (plano {$numero}). Revise e aprove."
            : 'Plano de aula gerado como rascunho. Revise e aprove.');
        $this->redirect('/planos/' . $id);
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $plano = (new PlanoAula())->find((int) $id, $prof['id']);

        if (!$plano) {
            $this->notFound('Plano de aula nao encontrado.');
            return;
        }

        $this->view('planos.show', [
            'title' => $plano['titulo'],
            'plano' => $plano,
        ]);
    }

    public function update(string $id): void
    {
        $prof = $this->professor();
        $model = new PlanoAula();
        $plano = $model->find((int) $id, $prof['id']);

        if (!$plano) {
            $this->notFound('Plano de aula nao encontrado.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo === '') {
            $this->flash('Informe o titulo do plano.');
            $this->redirect('/planos/' . $id);
            return;
        }

        $model->update((int) $id, [
            'titulo' => $titulo,
            'duracao' => trim($_POST['duracao'] ?? ''),
            'corpo' => $_POST['corpo'] ?? '',
            'status' => $plano['status'],
        ]);

        $this->flash('Alteracoes salvas.');
        $this->redirect('/planos/' . $id);
    }

    public function aprovar(string $id): void
    {
        $prof = $this->professor();
        $model = new PlanoAula();
        $plano = $model->find((int) $id, $prof['id']);

        if (!$plano) {
            $this->notFound('Plano de aula nao encontrado.');
            return;
        }

        $model->update((int) $id, [
            'titulo' => $plano['titulo'],
            'duracao' => $plano['duracao'],
            'corpo' => $plano['corpo'],
            'status' => 'aprovado',
        ]);

        Logger::activity('plano.approved', [
            'entity_type' => 'plano_aula',
            'entity_id' => (int) $id,
            'description' => 'Plano de aula aprovado pelo professor',
        ]);

        $this->flash('Plano de aula aprovado.');
        $this->redirect('/planos/' . $id);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new PlanoAula();
        $plano = $model->find((int) $id, $prof['id']);

        if (!$plano) {
            $this->notFound('Plano de aula nao encontrado.');
            return;
        }

        $model->delete((int) $id);

        $this->flash('Plano de aula excluido.');
        $this->redirect('/modulos/' . (int) $plano['modulo_id']);
    }
}
