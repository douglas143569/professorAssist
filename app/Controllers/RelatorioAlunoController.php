<?php

namespace App\Controllers;

use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

/**
 * Relatorio individual descritivo de aluno da creche.
 *
 * Por principio do projeto, nenhum dado pessoal de crianca e armazenado: o
 * formulario e o relatorio existem apenas na resposta da requisicao. O
 * professor edita e imprime/salva em PDF na propria tela.
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

    public function index(): void
    {
        $this->tela([], null);
    }

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

        foreach (['nome' => 'o nome do aluno', 'idade' => 'a idade', 'turma' => 'a turma', 'periodo' => 'o período avaliado'] as $c => $nome) {
            if ($form[$c] === '') {
                $this->flash("Informe {$nome}.");
                $this->tela($form, null);
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
            $relatorio = (new AI())->gerarRelatorioAluno(
                array_intersect_key($form, array_flip(['nome', 'idade', 'turma', 'periodo', 'professor'])),
                $observacoes,
                (int) $prof['id']
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel gerar o relatorio: ' . $e->getMessage());
            $this->tela($form, null);
            return;
        }

        // Log sem nome nem conteudo: so o fato de ter gerado.
        Logger::activity('creche.relatorio.gerado', [
            'description' => 'IA gerou relatorio individual de aluno (nao armazenado)',
        ]);

        $this->tela($form, $relatorio);
    }

    private function tela(array $form, ?string $relatorio): void
    {
        $this->view('creche.relatorios', [
            'title' => 'Relatórios dos alunos',
            'form' => $form,
            'relatorio' => $relatorio,
            'observacoes' => self::OBSERVACOES,
            'nomeProfessor' => (string) $this->professor()['name'],
        ]);
    }
}
