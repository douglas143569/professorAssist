<?php

namespace App\Controllers;

use App\Models\Disciplina;
use App\Models\Evento;
use App\Services\Logger;

class CalendarioController extends AppController
{
    public function index(): void
    {
        $prof = $this->professor();

        $ano = (int) ($_GET['ano'] ?? date('Y'));
        $mes = (int) ($_GET['mes'] ?? date('n'));
        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('n');
        }

        $eventos = (new Evento())->byMonth($prof['id'], $ano, $mes);

        // Agrupa por dia e conta por tipo (resumo do mes).
        $porDia = [];
        $resumo = ['prova' => 0, 'trabalho' => 0, 'lembrete' => 0, 'aula' => 0];
        foreach ($eventos as $e) {
            $dia = (int) date('j', strtotime($e['data_evento']));
            $porDia[$dia][] = $e;
            if (isset($resumo[$e['tipo']])) {
                $resumo[$e['tipo']]++;
            }
        }

        $this->view('calendario.index', [
            'title' => 'Calendário',
            'ano' => $ano,
            'mes' => $mes,
            'porDia' => $porDia,
            'resumo' => $resumo,
            'proximos' => (new Evento())->proximos($prof['id']),
            'disciplinas' => (new Disciplina())->byUser($prof['id']),
        ]);
    }

    public function store(): void
    {
        $prof = $this->professor();

        $titulo = trim($_POST['titulo'] ?? '');
        $data = trim($_POST['data_evento'] ?? '');
        $tipo = in_array($_POST['tipo'] ?? '', ['prova', 'trabalho', 'lembrete', 'aula'], true)
            ? $_POST['tipo'] : 'lembrete';

        if ($titulo === '' || $data === '') {
            $this->flash('Informe o título e a data do evento.');
            $this->redirect('/calendario');
            return;
        }

        (new Evento())->create([
            'user_id' => $prof['id'],
            'disciplina_id' => $_POST['disciplina_id'] ?? null,
            'titulo' => $titulo,
            'tipo' => $tipo,
            'descricao' => trim($_POST['descricao'] ?? ''),
            'data_evento' => $data,
            'hora' => $_POST['hora'] ?? null,
        ]);

        Logger::activity('evento.created', [
            'entity_type' => 'evento',
            'description' => "Evento criado: {$titulo}",
        ]);

        // Volta para o mes do evento criado.
        $ts = strtotime($data);
        $this->flash('Evento adicionado.');
        $this->redirect('/calendario?ano=' . date('Y', $ts) . '&mes=' . date('n', $ts));
    }

    public function concluir(string $id): void
    {
        $prof = $this->professor();
        $model = new Evento();

        if (!$model->find((int) $id, $prof['id'])) {
            $this->notFound('Evento nao encontrado.');
            return;
        }

        $model->toggleConcluido((int) $id);
        $this->redirect($this->voltarPara());
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new Evento();

        if (!$model->find((int) $id, $prof['id'])) {
            $this->notFound('Evento nao encontrado.');
            return;
        }

        $model->delete((int) $id);
        $this->flash('Evento excluído.');
        $this->redirect($this->voltarPara());
    }

    /** Preserva o mes que estava sendo visto (enviado num campo oculto). */
    private function voltarPara(): string
    {
        $ano = (int) ($_POST['ano'] ?? date('Y'));
        $mes = (int) ($_POST['mes'] ?? date('n'));
        return '/calendario?ano=' . $ano . '&mes=' . $mes;
    }
}
