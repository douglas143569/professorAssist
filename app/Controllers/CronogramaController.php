<?php

namespace App\Controllers;

use App\Models\CrecheCronograma;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

class CronogramaController extends AppController
{
    /** Segunda-feira da semana a exibir (a partir de ?inicio ou hoje). */
    private function segundaDaSemana(): int
    {
        $ref = $_GET['inicio'] ?? ($_POST['inicio'] ?? '');
        $base = ($ref !== '' && strtotime($ref)) ? strtotime($ref) : time();
        return strtotime('monday this week', $base);
    }

    public function index(): void
    {
        $prof = $this->professor();
        $seg = $this->segundaDaSemana();
        $sex = strtotime('+4 days', $seg);

        $inicio = date('Y-m-d', $seg);
        $fim = date('Y-m-d', $sex);

        // Agrupa itens por dia (Y-m-d).
        $itens = (new CrecheCronograma())->byWeek($prof['id'], $inicio, $fim);
        $porDia = [];
        foreach ($itens as $it) {
            $porDia[$it['data']][] = $it;
        }

        $this->view('creche.cronograma', [
            'title' => 'Cronograma de atividades',
            'faixas' => CrecheController::FAIXAS,
            'campos' => CrecheController::CAMPOS,
            'seg' => $seg,
            'inicio' => $inicio,
            'fim' => $fim,
            'porDia' => $porDia,
            'temItens' => count($itens) > 0,
        ]);
    }

    /** Cria as atividades da semana inteira com a IA. */
    public function criar(): void
    {
        $prof = $this->professor();
        $seg = $this->segundaDaSemana();

        $faixa = trim($_POST['faixa_etaria'] ?? '');
        if (!in_array($faixa, CrecheController::FAIXAS, true)) {
            $this->flash('Selecione uma faixa etária válida.');
            $this->redirect('/creche/cronograma?inicio=' . date('Y-m-d', $seg));
            return;
        }
        $campo = in_array($_POST['campo_experiencia'] ?? '', CrecheController::CAMPOS, true)
            ? $_POST['campo_experiencia'] : '';
        $tema = trim($_POST['tema'] ?? '');

        try {
            $atividades = (new AI())->gerarCronogramaSemanal(
                faixaEtaria: $faixa,
                campoExperiencia: $campo,
                tema: $tema,
                dias: 5,
                userId: (int) $prof['id'],
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel criar o cronograma: ' . $e->getMessage());
            $this->redirect('/creche/cronograma?inicio=' . date('Y-m-d', $seg));
            return;
        }

        $model = new CrecheCronograma();
        // Cada atividade vai para um dia da semana (segunda a sexta), em ordem.
        $i = 0;
        foreach ($atividades as $a) {
            if ($i > 4) {
                break;
            }
            $dia = date('Y-m-d', strtotime("+{$i} days", $seg));
            $model->create([
                'user_id' => $prof['id'],
                'faixa_etaria' => $faixa,
                'campo_experiencia' => $campo,
                'data' => $dia,
                'titulo' => $a['titulo'],
                'descricao' => $a['descricao'],
                'materiais' => $a['materiais'],
                'duracao' => $a['duracao'],
                'origem' => 'ia',
            ]);
            $i++;
        }

        Logger::activity('creche.cronograma.created', [
            'description' => "IA criou cronograma da semana ({$faixa})",
        ]);

        $this->flash('Cronograma da semana criado. Ajuste o que quiser.');
        $this->redirect('/creche/cronograma?inicio=' . date('Y-m-d', $seg));
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
        ]);

        $this->flash('Atividade atualizada.');
        $this->redirect('/creche/cronograma?inicio=' . date('Y-m-d', strtotime('monday this week', strtotime($data))));
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
        $this->redirect('/creche/cronograma?inicio=' . date('Y-m-d', strtotime('monday this week', strtotime($item['data']))));
    }

    /** Limpa todas as atividades da semana exibida. */
    public function limpar(): void
    {
        $prof = $this->professor();
        $seg = $this->segundaDaSemana();
        $sex = strtotime('+4 days', $seg);

        (new CrecheCronograma())->deleteWeek(
            (int) $prof['id'],
            date('Y-m-d', $seg),
            date('Y-m-d', $sex)
        );

        $this->flash('Semana limpa.');
        $this->redirect('/creche/cronograma?inicio=' . date('Y-m-d', $seg));
    }
}
