<?php

namespace App\Controllers;

use App\Models\Disciplina;
use App\Models\Prova;
use App\Models\ProvaQuestao;
use App\Models\ProvaVersao;
use App\Models\Questao;
use App\Services\Logger;
use App\Services\MontadorDeProva;

/**
 * Gerador de provas (RF-15 a RF-18).
 *
 * Fluxo: o professor escolhe a materia e quantas questoes quer de cada
 * dificuldade -> o sistema sorteia do banco (so questoes APROVADAS) -> ele
 * ajusta ordem, pontuacao e trocas na tela da prova -> gera versoes
 * embaralhadas -> imprime prova, gabarito e cartao-resposta.
 *
 * Nao existe cadastro de aluno em lugar nenhum deste modulo: a correcao e
 * feita pelo professor com o gabarito em maos.
 */
class ProvaController extends AppController
{
    /** Rotulos possiveis das versoes, na ordem. */
    private const ROTULOS = ['A', 'B', 'C', 'D'];

    public function index(): void
    {
        $prof = $this->professor();

        $this->view('provas.index', [
            'title' => 'Provas',
            'provas' => (new Prova())->byUser($prof['id']),
            'materias' => (new Disciplina())->byUser($prof['id']),
            // Quanto da para sortear em cada materia: evita o professor montar
            // a prova para so entao descobrir que nao ha questao aprovada.
            'aprovadas' => (new Questao())->aprovadasPorDificuldadeDoUsuario($prof['id']),
        ]);
    }

    /** Cria a prova e ja monta o rascunho sorteando do banco. */
    public function store(): void
    {
        $prof = $this->professor();

        $disciplinaId = (int) ($_POST['disciplina_id'] ?? 0);
        $materia = (new Disciplina())->find($disciplinaId, $prof['id']);

        if (!$materia) {
            $this->flash('Selecione uma matéria válida.');
            $this->redirect('/provas');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo === '') {
            $titulo = 'Avaliação de ' . $materia['nome'];
        }

        $quantidades = [
            'facil' => max(0, (int) ($_POST['facil'] ?? 0)),
            'media' => max(0, (int) ($_POST['media'] ?? 0)),
            'dificil' => max(0, (int) ($_POST['dificil'] ?? 0)),
        ];

        if (array_sum($quantidades) === 0) {
            $this->flash('Informe quantas questões a prova deve ter.');
            $this->redirect('/provas');
            return;
        }

        $disponiveis = (new Questao())->aprovadasParaProva($disciplinaId, [
            'modulo_id' => $_POST['modulo_id'] ?? null,
        ]);

        if ($disponiveis === []) {
            $this->flash('Esta matéria ainda não tem questões aprovadas. Aprove questões no banco antes de montar a prova.');
            $this->redirect('/provas');
            return;
        }

        $provaId = (new Prova())->create([
            'disciplina_id' => $disciplinaId,
            'user_id' => $prof['id'],
            'titulo' => $titulo,
            'instrucoes' => trim($_POST['instrucoes'] ?? ''),
            'config' => ['quantidades' => $quantidades],
        ]);

        $montador = new MontadorDeProva();
        $sorteio = $montador->sortear($disponiveis, $quantidades, $provaId);

        $vinculo = new ProvaQuestao();
        $pontuacao = $this->pontuacaoPadrao(count($sorteio['questoes']));

        foreach ($sorteio['questoes'] as $questao) {
            $vinculo->adicionar($provaId, (int) $questao['id'], $pontuacao);
        }

        // Versao A ja nasce com a prova: e a ordem definida pelo professor.
        (new ProvaVersao())->criar($provaId, 'A', $montador->sementeDaVersao($provaId, 'A'));

        Logger::activity('prova.created', [
            'entity_type' => 'prova',
            'entity_id' => $provaId,
            'description' => "Prova montada: {$titulo}",
            'properties' => ['questoes' => count($sorteio['questoes'])],
        ]);

        $this->flash($sorteio['faltou'] === []
            ? count($sorteio['questoes']) . ' questão(ões) sorteada(s). Ajuste o que quiser.'
            : 'Prova montada, mas o banco não tinha questões aprovadas suficientes de todas as dificuldades — completei com as disponíveis.');

        $this->redirect('/provas/' . $provaId);
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $prova = (new Prova())->find((int) $id, $prof['id']);

        if (!$prova) {
            $this->notFound('Prova nao encontrada.');
            return;
        }

        $vinculo = new ProvaQuestao();

        $this->view('provas.show', [
            'title' => $prova['titulo'],
            'prova' => $prova,
            'questoes' => $vinculo->byProva((int) $id),
            'total_pontos' => $vinculo->totalPontos((int) $id),
            'versoes' => (new ProvaVersao())->byProva((int) $id),
            'disponiveis' => (new Questao())->aprovadasParaProva(
                (int) $prova['disciplina_id'],
                [],
                (int) $id
            ),
            'rotulos' => self::ROTULOS,
        ]);
    }

    /** Salva titulo, instrucoes, ordem e pontuacao de uma vez. */
    public function update(string $id): void
    {
        $prova = $this->provaDoProfessor($id);

        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo === '') {
            $this->flash('A prova precisa de um título.');
            $this->redirect('/provas/' . $id);
            return;
        }

        (new Prova())->update((int) $id, [
            'titulo' => $titulo,
            'instrucoes' => trim($_POST['instrucoes'] ?? ''),
        ]);

        $ordens = $_POST['ordem'] ?? [];
        $pontos = $_POST['pontuacao'] ?? [];
        $valores = [];

        foreach ($ordens as $questaoId => $ordem) {
            $valores[(int) $questaoId] = [
                'ordem' => (int) $ordem,
                'pontuacao' => max(0, (float) str_replace(',', '.', (string) ($pontos[$questaoId] ?? 1))),
            ];
        }

        if ($valores !== []) {
            (new ProvaQuestao())->salvarOrdemEPontuacao((int) $id, $valores);
        }

        $this->flash('Prova atualizada.');
        $this->redirect('/provas/' . $id);
    }

    public function adicionarQuestao(string $id): void
    {
        $prova = $this->provaDoProfessor($id);

        $questaoId = (int) ($_POST['questao_id'] ?? 0);
        $questao = (new Questao())->find($questaoId, $this->professor()['id']);

        // Confere dono E materia: nao da para trazer questao de outra materia.
        if (!$questao || (int) $questao['disciplina_id'] !== (int) $prova['disciplina_id']) {
            $this->flash('Questão inválida para esta prova.');
            $this->redirect('/provas/' . $id);
            return;
        }

        if ($questao['status'] !== 'aprovado') {
            $this->flash('Só questões aprovadas podem entrar numa prova.');
            $this->redirect('/provas/' . $id);
            return;
        }

        (new ProvaQuestao())->adicionar((int) $id, $questaoId, 1.0);

        $this->flash('Questão adicionada.');
        $this->redirect('/provas/' . $id);
    }

    public function removerQuestao(string $id, string $questaoId): void
    {
        $this->provaDoProfessor($id);

        (new ProvaQuestao())->remover((int) $id, (int) $questaoId);

        $this->flash('Questão removida da prova.');
        $this->redirect('/provas/' . $id);
    }

    /**
     * (Re)gera as versoes embaralhadas. A versao A sempre existe e mantem a
     * ordem do professor; B, C e D embaralham questoes e alternativas.
     */
    public function gerarVersoes(string $id): void
    {
        $this->provaDoProfessor($id);

        $quantas = max(1, min(count(self::ROTULOS), (int) ($_POST['quantidade'] ?? 2)));

        $versoes = new ProvaVersao();
        $montador = new MontadorDeProva();

        $versoes->excluirTodas((int) $id);

        foreach (array_slice(self::ROTULOS, 0, $quantas) as $rotulo) {
            $versoes->criar((int) $id, $rotulo, $montador->sementeDaVersao((int) $id, $rotulo));
        }

        $this->flash($quantas === 1
            ? 'Prova com uma única versão.'
            : "{$quantas} versões geradas. As alternativas e a ordem das questões mudam entre elas.");

        $this->redirect('/provas/' . $id);
    }

    /** Prova pronta para impressao. ?gabarito=1 marca as respostas certas. */
    public function imprimir(string $id): void
    {
        $prova = $this->provaDoProfessor($id);
        $rotulo = $this->rotuloPedido($id);

        $montagem = (new MontadorDeProva())->montarVersao(
            (new ProvaQuestao())->byProva((int) $id),
            $this->sementeDe((int) $id, $rotulo)
        );

        $this->view('provas.imprimir', [
            'title' => $prova['titulo'] . ' — versão ' . $rotulo,
            'prova' => $prova,
            'questoes' => $montagem['questoes'],
            'gabarito' => $montagem['gabarito'],
            'mostrarGabarito' => isset($_GET['gabarito']),
            'rotulo' => $rotulo,
            'total_pontos' => (new ProvaQuestao())->totalPontos((int) $id),
        ]);
    }

    /**
     * Cartao-resposta imprimivel. O aluno preenche a mao e o professor corrige
     * com o gabarito -- nenhum dado de aluno entra no sistema.
     */
    public function cartaoResposta(string $id): void
    {
        $prova = $this->provaDoProfessor($id);
        $rotulo = $this->rotuloPedido($id);

        $montagem = (new MontadorDeProva())->montarVersao(
            (new ProvaQuestao())->byProva((int) $id),
            $this->sementeDe((int) $id, $rotulo)
        );

        $this->view('provas.cartao', [
            'title' => 'Cartão-resposta — ' . $prova['titulo'],
            'prova' => $prova,
            'questoes' => $montagem['questoes'],
            'gabarito' => $montagem['gabarito'],
            'rotulo' => $rotulo,
        ]);
    }

    public function excluir(string $id): void
    {
        $this->provaDoProfessor($id);

        (new Prova())->delete((int) $id);

        $this->flash('Prova excluída.');
        $this->redirect('/provas');
    }

    /* ------------------------------------------------------------------ */

    /** Carrega a prova conferindo o dono, ou encerra a requisicao. */
    private function provaDoProfessor(string $id): array
    {
        $prova = (new Prova())->find((int) $id, $this->professor()['id']);

        if (!$prova) {
            $this->notFound('Prova nao encontrada.');
            exit;
        }

        return $prova;
    }

    private function rotuloPedido(string $id): string
    {
        $pedido = strtoupper(trim((string) ($_GET['versao'] ?? 'A')));

        return in_array($pedido, self::ROTULOS, true) ? $pedido : 'A';
    }

    /** Semente gravada da versao; se ela nao existe, cai na ordem original. */
    private function sementeDe(int $provaId, string $rotulo): int
    {
        $versao = (new ProvaVersao())->find($provaId, $rotulo);

        return $versao !== null ? (int) $versao['seed_embaralhamento'] : 0;
    }

    /**
     * Distribui 10 pontos entre as questoes, arredondando para 2 casas.
     * E so um ponto de partida -- o professor ajusta na tela.
     */
    private function pontuacaoPadrao(int $quantidade): float
    {
        return $quantidade > 0 ? round(10 / $quantidade, 2) : 1.0;
    }
}
