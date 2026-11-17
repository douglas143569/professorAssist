<?php

namespace App\Controllers;

use App\Models\CrecheRelatorio;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

/**
 * Relatorio individual descritivo de aluno da creche + Banco de relatorios.
 *
 * Cada relatorio gerado e salvo no banco do professor que o criou (so ele
 * enxerga) e pode ser editado ou excluido. Nome e conteudo da crianca nunca
 * vao para os logs.
 */
class RelatorioAlunoController extends AppController
{
    /** campo do formulario => rotulo enviado a IA */
    public const OBSERVACOES = [
        'social' => 'Desenvolvimento social',
        'emocional' => 'Desenvolvimento emocional',
        'linguagem' => 'Desenvolvimento da linguagem',
        'motor' => 'Desenvolvimento motor',
        'cognitivo' => 'Desenvolvimento cognitivo',
        'alimentacao' => 'Alimentação',
        'higiene' => 'Higiene e autonomia',
        'participacao' => 'Participação nas atividades',
        'colegas' => 'Relacionamento com colegas',
        'adultos' => 'Relacionamento com adultos',
        'dificuldades' => 'Dificuldades observadas',
        'avancos' => 'Avanços identificados',
    ];

    /** Formulario de novo relatorio. */
    public function index(): void
    {
        $this->tela([]);
    }

    /** Gera com IA, salva no banco e abre o relatorio. */
    public function gerar(): void
    {
        $prof = $this->professor();

        $form = [];
        foreach (['nome', 'idade', 'turma', 'periodo', 'professor'] as $c) {
            $form[$c] = mb_substr(trim((string) ($_POST[$c] ?? '')), 0, 120);
        }
        foreach (array_keys(self::OBSERVACOES) as $c) {
            $form[$c] = mb_substr(trim((string) ($_POST[$c] ?? '')), 0, 2000);
        }

        foreach (['nome' => 'o nome do aluno', 'idade' => 'a idade', 'turma' => 'a turma', 'periodo' => 'o período avaliado'] as $c => $rotulo) {
            if ($form[$c] === '') {
                $this->flash("Informe {$rotulo}.");
                $this->tela($form);
                return;
            }
        }
        if ($form['professor'] === '') {
            $form['professor'] = (string) $prof['name'];
        }

        $observacoes = [];
        foreach (self::OBSERVACOES as $campo => $rotulo) {
            if ($form[$campo] !== '') {
                $observacoes[$rotulo] = $form[$campo];
            }
        }

        try {
            $texto = (new AI())->gerarRelatorioAluno(
                array_intersect_key($form, array_flip(['nome', 'idade', 'turma', 'periodo', 'professor'])),
                $observacoes,
                (int) $prof['id']
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel gerar o relatorio: ' . $e->getMessage());
            $this->tela($form);
            return;
        }

        $id = (new CrecheRelatorio())->create([
            'user_id' => $prof['id'],
            'aluno_nome' => $form['nome'],
            'idade' => $form['idade'],
            'turma' => $form['turma'],
            'periodo' => $form['periodo'],
            'professor' => $form['professor'],
            'texto' => $texto,
        ]);

        // Log sem nome nem conteudo.
        Logger::activity('creche.relatorio.gerado', [
            'entity_type' => 'creche_relatorio',
            'entity_id' => $id,
            'description' => 'IA gerou relatorio individual de aluno',
        ]);

        $this->flash('Relatório gerado e salvo no Banco de relatórios.');
        $this->redirect('/creche/relatorios/' . $id);
    }

    /** Banco de relatorios: lista os relatorios salvos do professor. */
    public function banco(): void
    {
        $prof = $this->professor();
        $busca = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);

        $this->view('creche.relatorios_banco', [
            'title' => 'Banco de relatórios',
            'relatorios' => (new CrecheRelatorio())->byUser((int) $prof['id'], $busca),
            'busca' => $busca,
        ]);
    }

    public function show(string $id): void
    {
        $r = (new CrecheRelatorio())->find((int) $id, (int) $this->professor()['id']);
        if (!$r) {
            $this->notFound('Relatorio nao encontrado.');
            return;
        }

        $this->view('creche.relatorio_show', ['title' => 'Relatório de ' . $r['aluno_nome'], 'r' => $r]);
    }

    public function update(string $id): void
    {
        $prof = $this->professor();
        $model = new CrecheRelatorio();
        $r = $model->find((int) $id, (int) $prof['id']);
        if (!$r) {
            $this->notFound('Relatorio nao encontrado.');
            return;
        }

        $texto = trim((string) ($_POST['texto'] ?? ''));
        if ($texto === '') {
            $this->flash('O relatório não pode ficar vazio.');
        } else {
            $model->updateTexto((int) $id, (int) $prof['id'], $texto);
            $this->flash('Alterações salvas.');
        }
        $this->redirect('/creche/relatorios/' . (int) $id);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new CrecheRelatorio();
        if (!$model->find((int) $id, (int) $prof['id'])) {
            $this->notFound('Relatorio nao encontrado.');
            return;
        }

        $model->delete((int) $id, (int) $prof['id']);
        Logger::activity('creche.relatorio.excluido', [
            'entity_type' => 'creche_relatorio',
            'entity_id' => (int) $id,
            'description' => 'Relatorio individual excluido',
        ]);
        $this->flash('Relatório excluído.');
        $this->redirect('/creche/relatorios/banco');
    }

    private function tela(array $form): void
    {
        $this->view('creche.relatorios', [
            'title' => 'Relatórios dos alunos',
            'form' => $form,
            'observacoes' => self::OBSERVACOES,
            'nomeProfessor' => (string) $this->professor()['name'],
        ]);
    }
}
