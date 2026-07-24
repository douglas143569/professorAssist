<?php

namespace App\Controllers;

use App\Models\Disciplina;
use App\Models\Modulo;
use App\Models\Questao;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

class QuestaoController extends AppController
{
    /** Banco de questoes da disciplina, com filtros (RF-13). */
    public function banco(string $disciplinaId): void
    {
        $prof = $this->professor();
        $disciplina = (new Disciplina())->find((int) $disciplinaId, $prof['id']);

        if (!$disciplina) {
            $this->notFound('Disciplina nao encontrada.');
            return;
        }

        $filtros = [
            'dificuldade' => $_GET['dificuldade'] ?? '',
            'status' => $_GET['status'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'bncc' => trim($_GET['bncc'] ?? ''),
        ];

        $this->view('questoes.banco', [
            'title' => 'Banco de questões — ' . $disciplina['nome'],
            'disciplina' => $disciplina,
            'filtros' => $filtros,
            'questoes' => (new Questao())->byDisciplinaFiltrado((int) $disciplinaId, $filtros),
        ]);
    }

    /** Gera questoes com a IA a partir do modulo (RF-12). */
    public function gerar(string $moduloId): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $moduloId, $prof['id']);

        if (!$modulo) {
            $this->notFound('Modulo nao encontrado.');
            return;
        }

        $quantidade = (int) ($_POST['quantidade'] ?? 5);
        $dificuldade = $_POST['dificuldade'] ?? 'media';
        $tipo = $_POST['tipo'] ?? 'multipla_escolha';
        $bncc = !empty($modulo['codigos_bncc']) ? trim(explode(',', $modulo['codigos_bncc'])[0]) : null;

        try {
            $questoes = (new AI())->gerarQuestoes(
                tema: $modulo['titulo'],
                habilidadeBncc: $bncc,
                dificuldade: $dificuldade,
                quantidade: $quantidade,
                tipo: $tipo,
                userId: (int) $prof['id'],
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel gerar as questoes: ' . $e->getMessage());
            $this->redirect('/modulos/' . $moduloId);
            return;
        }

        $model = new Questao();
        foreach ($questoes as $q) {
            $model->create([
                'disciplina_id' => (int) $modulo['disciplina_id'],
                'modulo_id' => (int) $moduloId,
                'tipo' => $q['tipo'],
                'enunciado' => $q['enunciado'],
                'dificuldade' => $q['dificuldade'],
                'habilidade_bncc' => $q['habilidade_bncc'],
                'tags' => $q['tags'],
                'origem' => 'ia',
                'status' => 'rascunho',
            ], $q['alternativas']);
        }

        $n = count($questoes);
        Logger::activity('questao.generated', [
            'entity_type' => 'modulo',
            'entity_id' => (int) $moduloId,
            'description' => "IA gerou {$n} questoes",
        ]);

        $this->flash("{$n} questão(ões) gerada(s) como rascunho. Revise e aprove.");
        $this->redirect('/modulos/' . $moduloId);
    }

    /** Formulario de cadastro manual (RF-11). */
    public function novaForm(string $moduloId): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $moduloId, $prof['id']);

        if (!$modulo) {
            $this->notFound('Modulo nao encontrado.');
            return;
        }

        $this->view('questoes.nova', [
            'title' => 'Nova questão',
            'modulo' => $modulo,
        ]);
    }

    /** Salva a questao cadastrada manualmente. */
    public function store(string $moduloId): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $moduloId, $prof['id']);

        if (!$modulo) {
            $this->notFound('Modulo nao encontrado.');
            return;
        }

        $enunciado = trim($_POST['enunciado'] ?? '');
        $tipo = $this->tipoValido($_POST['tipo'] ?? 'multipla_escolha');

        if ($enunciado === '') {
            $this->flash('Informe o enunciado da questao.');
            $this->redirect('/modulos/' . $moduloId . '/questoes/nova');
            return;
        }

        $id = (new Questao())->create([
            'disciplina_id' => (int) $modulo['disciplina_id'],
            'modulo_id' => (int) $moduloId,
            'tipo' => $tipo,
            'enunciado' => $enunciado,
            'dificuldade' => $this->dificuldadeValida($_POST['dificuldade'] ?? 'media'),
            'habilidade_bncc' => trim($_POST['habilidade_bncc'] ?? ''),
            'tags' => trim($_POST['tags'] ?? ''),
            'origem' => 'manual',
            'status' => 'rascunho',
        ], $this->alternativasDoPost($tipo));

        $this->flash('Questao criada.');
        $this->redirect('/questoes/' . $id);
    }

    /** Tela de revisao/edicao de uma questao (RF-14). */
    public function show(string $id): void
    {
        $prof = $this->professor();
        $model = new Questao();
        $questao = $model->find((int) $id, $prof['id']);

        if (!$questao) {
            $this->notFound('Questao nao encontrada.');
            return;
        }

        $this->view('questoes.show', [
            'title' => 'Revisar questão',
            'questao' => $questao,
            'alternativas' => $model->alternativas((int) $id),
        ]);
    }

    public function update(string $id): void
    {
        $prof = $this->professor();
        $model = new Questao();
        $questao = $model->find((int) $id, $prof['id']);

        if (!$questao) {
            $this->notFound('Questao nao encontrada.');
            return;
        }

        $enunciado = trim($_POST['enunciado'] ?? '');
        $tipo = $this->tipoValido($_POST['tipo'] ?? $questao['tipo']);

        if ($enunciado === '') {
            $this->flash('Informe o enunciado da questao.');
            $this->redirect('/questoes/' . $id);
            return;
        }

        $model->update((int) $id, [
            'tipo' => $tipo,
            'enunciado' => $enunciado,
            'dificuldade' => $this->dificuldadeValida($_POST['dificuldade'] ?? $questao['dificuldade']),
            'habilidade_bncc' => trim($_POST['habilidade_bncc'] ?? ''),
            'tags' => trim($_POST['tags'] ?? ''),
            'status' => $questao['status'],
        ]);

        $model->substituirAlternativas((int) $id, $this->alternativasDoPost($tipo));

        $this->flash('Alteracoes salvas.');
        $this->redirect('/questoes/' . $id);
    }

    public function aprovar(string $id): void
    {
        $prof = $this->professor();
        $model = new Questao();
        $questao = $model->find((int) $id, $prof['id']);

        if (!$questao) {
            $this->notFound('Questao nao encontrada.');
            return;
        }

        $model->aprovar((int) $id);

        Logger::activity('questao.approved', [
            'entity_type' => 'questao',
            'entity_id' => (int) $id,
            'description' => 'Questao aprovada pelo professor',
        ]);

        $this->flash('Questao aprovada.');
        $this->redirect('/questoes/' . $id);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new Questao();
        $questao = $model->find((int) $id, $prof['id']);

        if (!$questao) {
            $this->notFound('Questao nao encontrada.');
            return;
        }

        $model->delete((int) $id);

        $this->flash('Questao excluida.');
        $this->redirect('/modulos/' . (int) $questao['modulo_id']);
    }

    /* ---------------------------------------------------------------------
     |  Helpers
     * -------------------------------------------------------------------*/

    private function tipoValido(string $tipo): string
    {
        return in_array($tipo, ['multipla_escolha', 'verdadeiro_falso', 'dissertativa'], true)
            ? $tipo : 'multipla_escolha';
    }

    private function dificuldadeValida(string $dif): string
    {
        return in_array($dif, ['facil', 'media', 'dificil'], true) ? $dif : 'media';
    }

    /**
     * Monta as alternativas a partir do POST (alt_texto[] + radio "correta").
     * Dissertativas nao tem alternativas.
     */
    private function alternativasDoPost(string $tipo): array
    {
        if ($tipo === 'dissertativa') {
            return [];
        }

        $textos = $_POST['alt_texto'] ?? [];
        $corretaIdx = isset($_POST['correta']) ? (int) $_POST['correta'] : -1;

        $alternativas = [];
        foreach ($textos as $i => $texto) {
            $texto = trim($texto);
            if ($texto === '') {
                continue;
            }
            $alternativas[] = ['texto' => $texto, 'correta' => ((int) $i === $corretaIdx)];
        }

        return $alternativas;
    }
}
