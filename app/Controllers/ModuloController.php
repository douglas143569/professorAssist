<?php

namespace App\Controllers;

use App\Models\Conteudo;
use App\Models\Disciplina;
use App\Models\Modulo;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

class ModuloController extends AppController
{
    /** Cria um modulo a partir da tela da disciplina. */
    public function store(string $disciplinaId): void
    {
        $prof = $this->professor();
        $disciplina = (new Disciplina())->find((int) $disciplinaId, $prof['id']);

        if (!$disciplina) {
            $this->notFound('Disciplina nao encontrada.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo === '') {
            $this->flash('Informe o titulo do modulo.');
            $this->redirect('/disciplinas/' . $disciplinaId);
            return;
        }

        $id = (new Modulo())->create([
            'disciplina_id' => (int) $disciplinaId,
            'titulo' => $titulo,
            'ordem' => (int) ($_POST['ordem'] ?? 0),
            'objetivos' => trim($_POST['objetivos'] ?? ''),
            'codigos_bncc' => trim($_POST['codigos_bncc'] ?? ''),
        ]);

        Logger::activity('modulo.created', [
            'entity_type' => 'modulo',
            'entity_id' => $id,
            'description' => "Modulo criado: {$titulo}",
        ]);

        $this->flash('Modulo criado.');
        $this->redirect('/modulos/' . $id);
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $id, $prof['id']);

        if (!$modulo) {
            $this->notFound('Modulo nao encontrado.');
            return;
        }

        $this->view('modulos.show', [
            'title' => $modulo['titulo'],
            'modulo' => $modulo,
            'planos' => (new \App\Models\PlanoAula())->byModulo((int) $id),
            'atividades' => (new \App\Models\Atividade())->byModulo((int) $id),
            'conteudos' => (new Conteudo())->byModulo((int) $id),
            'questoes' => (new \App\Models\Questao())->byModulo((int) $id),
        ]);
    }

    /** Gera o rascunho do conteudo da aula com a IA (RF-08). */
    public function gerarConteudo(string $id): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $id, $prof['id']);

        if (!$modulo) {
            $this->notFound('Modulo nao encontrado.');
            return;
        }

        // Primeira habilidade BNCC do modulo, se houver (ex: "EF06MA07,EF06MA08").
        $bncc = null;
        if (!empty($modulo['codigos_bncc'])) {
            $bncc = trim(explode(',', $modulo['codigos_bncc'])[0]);
        }

        try {
            $texto = (new AI())->gerarConteudoAula(
                tema: $modulo['titulo'],
                habilidadeBncc: $bncc,
                etapa: $modulo['disciplina_etapa'],
                userId: (int) $prof['id'],
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel gerar com a IA: ' . $e->getMessage());
            $this->redirect('/modulos/' . $id);
            return;
        }

        $conteudoId = (new Conteudo())->create([
            'modulo_id' => (int) $id,
            'titulo' => 'Conteudo: ' . $modulo['titulo'],
            'corpo' => $texto,
            'origem' => 'ia',
            'status' => 'rascunho',
        ]);

        $this->flash('Rascunho gerado pela IA. Revise e aprove.');
        $this->redirect('/conteudos/' . $conteudoId);
    }
}
