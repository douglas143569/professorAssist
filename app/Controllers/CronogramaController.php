<?php

namespace App\Controllers;

use App\Models\CrecheCronograma;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

class CronogramaController extends AppController
{
    private const MESES = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho',
        'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    private const DIAS = ['Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira'];

    /** Mes exibido (AAAA-MM), vindo de ?mes ou do POST; padrao: mes atual. */
    private function mesRef(): string
    {
        $m = $_GET['mes'] ?? ($_POST['mes'] ?? '');
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $m) ? $m : date('Y-m');
    }

    public static function nomeDoMes(string $mesRef): string
    {
        return self::MESES[(int) substr($mesRef, 5, 2)] . ' de ' . substr($mesRef, 0, 4);
    }

    /**
     * Dias uteis (seg-sex) do mes agrupados por semana (comecando na segunda).
     * @return array<int,string[]> lista de semanas, cada uma com datas Y-m-d
     */
    private function semanasDoMes(string $mesRef, ?int $limiteDias = null): array
    {
        $semanas = [];
        $contagem = 0;
        $ultimo = (int) date('t', strtotime($mesRef . '-01'));
        for ($d = 1; $d <= $ultimo; $d++) {
            $ts = strtotime(sprintf('%s-%02d', $mesRef, $d));
            if ((int) date('N', $ts) > 5) {
                continue;
            }
            $semanas[date('o-W', $ts)][] = date('Y-m-d', $ts);
            $contagem++;
            if ($limiteDias !== null && $contagem >= $limiteDias) {
                break;
            }
        }
        return array_values($semanas);
    }

    public function index(): void
    {
        $prof = $this->professor();
        $mes = $this->mesRef();
        $model = new CrecheCronograma();

        $itens = $model->byMonth($prof['id'], $mes);
        $semanas = [];
        foreach ($this->semanasDoMes($mes) as $i => $datas) {
            $semanas[$i + 1] = array_fill_keys($datas, []);
        }
        foreach ($itens as $it) {
            foreach ($semanas as $n => $dias) {
                if (array_key_exists($it['data'], $dias)) {
                    $semanas[$n][$it['data']][] = $it;
                }
            }
        }

        $this->view('creche.cronograma', [
            'title' => 'Cronograma mensal de atividades',
            'faixas' => CrecheController::FAIXAS,
            'campos' => CrecheController::CAMPOS,
            'mes' => $mes,
            'diasUteisMes' => count(array_merge(...array_values($this->semanasDoMes($mes)))),
            'mesNome' => self::nomeDoMes($mes),
            'mesAnterior' => date('Y-m', strtotime($mes . '-01 -1 month')),
            'mesSeguinte' => date('Y-m', strtotime($mes . '-01 +1 month')),
            'semanas' => $semanas,
            'nomesDias' => self::DIAS,
            'resumo' => $model->resumoDoMes($prof['id'], $mes),
            'temItens' => count($itens) > 0,
        ]);
    }

    /** Cria atividades para os primeiros dias uteis escolhidos, uma chamada por semana + fechamento. */
    public function criar(): void
    {
        $prof = $this->professor();
        $mes = $this->mesRef();
        $volta = '/creche/cronograma?mes=' . $mes;

        $faixa = trim($_POST['faixa_etaria'] ?? '');
        if (!in_array($faixa, CrecheController::FAIXAS, true)) {
            $this->flash('Selecione uma faixa etária válida.');
            $this->redirect($volta);
            return;
        }
        $periodo = in_array($_POST['periodo'] ?? '', ['Integral', 'Parcial'], true) ? $_POST['periodo'] : 'Integral';
        $numCriancas = max(0, min(60, (int) ($_POST['num_criancas'] ?? 0)));
        $tema = mb_substr(trim($_POST['tema'] ?? ''), 0, 200);
        $diasUteisMes = array_merge(...array_values($this->semanasDoMes($mes)));
        $quantidadeDias = filter_var($_POST['dias_quantidade'] ?? null, FILTER_VALIDATE_INT);
        if ($quantidadeDias === false || $quantidadeDias < 1 || $quantidadeDias > count($diasUteisMes)) {
            $this->flash('Informe uma quantidade de dias valida para o mes selecionado.');
            $this->redirect($volta);
            return;
        }
        $datasSelecionadas = array_slice($diasUteisMes, 0, $quantidadeDias);
        $camposPost = is_array($_POST['campo_dia'] ?? null) ? $_POST['campo_dia'] : [];
        $camposPorData = [];
        foreach ($datasSelecionadas as $data) {
            $campo = trim((string) ($camposPost[$data] ?? ''));
            if (!in_array($campo, CrecheController::CAMPOS, true)) {
                $this->flash('Escolha um campo da BNCC para cada dia selecionado.');
                $this->redirect($volta);
                return;
            }
            $camposPorData[$data] = $campo;
        }

        $contexto = [
            'faixa' => $faixa,
            'mes_nome' => self::nomeDoMes($mes),
            'num_criancas' => $numCriancas > 0 ? (string) $numCriancas : 'nao informado',
            'periodo' => $periodo,
            'tema' => $tema,
        ];

        // Uma chamada por semana: respostas menores e sem estourar o limite de tokens.
        @set_time_limit(0);
        $ai = new AI();
        $gerados = [];
        $titulos = [];
        try {
            foreach ($this->semanasDoMes($mes, $quantidadeDias) as $i => $datas) {
                $rotulos = array_map(
                    fn($d) => self::DIAS[(int) date('N', strtotime($d)) - 1] . ' (' . date('d/m', strtotime($d)) . ')',
                    $datas
                );
                $camposDaSemana = array_map(fn($data) => $camposPorData[$data], $datas);
                $itens = $ai->gerarSemanaCronograma($contexto, $i + 1, $rotulos, $titulos, (int) $prof['id'], $camposDaSemana);
                foreach ($datas as $k => $data) {
                    if (!isset($itens[$k])) {
                        break;
                    }
                    $gerados[] = $itens[$k] + ['data' => $data, 'semana' => $i + 1];
                    $titulos[] = $itens[$k]['titulo'];
                }
            }
            $fechamento = $ai->gerarFechamentoCronograma($contexto, $titulos, (int) $prof['id']);
        } catch (AIException $e) {
            $this->flash('Nao foi possivel criar o cronograma: ' . $e->getMessage());
            $this->redirect($volta);
            return;
        }

        // So apaga o mes anterior depois que TUDO foi gerado com sucesso.
        $model = new CrecheCronograma();
        $model->deleteMonth((int) $prof['id'], $mes);
        foreach ($gerados as $g) {
            $model->create([
                'user_id' => $prof['id'],
                'faixa_etaria' => $faixa,
                'campo_experiencia' => $g['campo_experiencia'],
                'data' => $g['data'],
                'titulo' => $g['titulo'],
                'descricao' => $g['descricao'],
                'materiais' => $g['materiais'],
                'duracao' => $g['duracao'],
                'objetivos' => $g['objetivos'],
                'habilidades' => $g['habilidades'],
                'avaliacao' => $g['avaliacao'],
                'tipo_atividade' => $g['tipo_atividade'],
                'semana' => $g['semana'],
                'mes_ref' => $mes,
                'origem' => 'ia',
            ]);
        }
        $model->salvarResumoDoMes($fechamento + [
            'user_id' => $prof['id'], 'mes_ref' => $mes, 'faixa_etaria' => $faixa,
            'num_criancas' => $numCriancas, 'periodo' => $periodo, 'tema' => $tema,
        ]);

        Logger::activity('creche.cronograma.created', [
            'description' => "IA criou cronograma mensal {$mes} ({$faixa})",
        ]);

        $this->flash('Cronograma de ' . self::nomeDoMes($mes) . ' criado. Ajuste o que quiser.');
        $this->redirect($volta);
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $item = (new CrecheCronograma())->find((int) $id, $prof['id']);

        if (!$item) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $this->view('creche.cronograma_show', [
            'title' => 'Editar atividade do cronograma',
            'item' => $item,
            'faixas' => CrecheController::FAIXAS,
            'campos' => CrecheController::CAMPOS,
        ]);
    }

    public function update(string $id): void
    {
        $prof = $this->professor();
        $model = new CrecheCronograma();
        $item = $model->find((int) $id, $prof['id']);

        if (!$item) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $data = trim($_POST['data'] ?? '');
        if ($titulo === '' || !strtotime($data)) {
            $this->flash('Informe o título e uma data válida.');
            $this->redirect('/creche/cronograma/' . $id);
            return;
        }

        $faixa = in_array($_POST['faixa_etaria'] ?? '', CrecheController::FAIXAS, true)
            ? $_POST['faixa_etaria'] : $item['faixa_etaria'];
        $campo = in_array($_POST['campo_experiencia'] ?? '', CrecheController::CAMPOS, true)
            ? $_POST['campo_experiencia'] : '';

        $model->update((int) $id, [
            'faixa_etaria' => $faixa,
            'campo_experiencia' => $campo,
            'data' => date('Y-m-d', strtotime($data)),
            'titulo' => $titulo,
            'descricao' => $_POST['descricao'] ?? '',
            'materiais' => $_POST['materiais'] ?? '',
            'duracao' => trim($_POST['duracao'] ?? ''),
            'objetivos' => $_POST['objetivos'] ?? '',
            'habilidades' => $_POST['habilidades'] ?? '',
            'avaliacao' => $_POST['avaliacao'] ?? '',
        ]);

        $this->flash('Atividade atualizada.');
        $this->redirect('/creche/cronograma?mes=' . date('Y-m', strtotime($data)));
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new CrecheCronograma();
        $item = $model->find((int) $id, $prof['id']);

        if (!$item) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $model->delete((int) $id);
        $this->flash('Atividade removida do cronograma.');
        $this->redirect('/creche/cronograma?mes=' . date('Y-m', strtotime($item['data'])));
    }

    /** Limpa o cronograma do mes exibido (atividades + resumo). */
    public function limpar(): void
    {
        $prof = $this->professor();
        $mes = $this->mesRef();

        (new CrecheCronograma())->deleteMonth((int) $prof['id'], $mes);

        $this->flash('Cronograma de ' . self::nomeDoMes($mes) . ' limpo.');
        $this->redirect('/creche/cronograma?mes=' . $mes);
    }
}
