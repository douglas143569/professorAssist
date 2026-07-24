<?php

namespace App\Controllers;

use App\Models\CrecheAtividade;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

class CrecheController extends AppController
{
    /** Faixas etarias e campos de experiencia oferecidos nas telas. */
    public const FAIXAS = [
        'Berçário (0-1 ano)',
        'Berçário II (1-2 anos)',
        'Maternal (2-3 anos)',
        'Maternal II (3-4 anos)',
        'Pré-escola (4-5 anos)',
    ];

    public const CAMPOS = [
        'O eu, o outro e o nós',
        'Corpo, gestos e movimentos',
        'Traços, sons, cores e formas',
        'Escuta, fala, pensamento e imaginação',
        'Espaços, tempos, quantidades, relações e transformações',
    ];

    public function index(): void
    {
        $prof = $this->professor();
        $faixa = trim($_GET['faixa'] ?? '');

        $this->view('creche.index', [
            'title' => 'Creche',
            'faixas' => self::FAIXAS,
            'campos' => self::CAMPOS,
            'filtroFaixa' => $faixa,
            'atividades' => (new CrecheAtividade())->byUser($prof['id'], $faixa),
        ]);
    }

    public function gerar(): void
    {
        $prof = $this->professor();

        $faixa = trim($_POST['faixa_etaria'] ?? '');
        if (!in_array($faixa, self::FAIXAS, true)) {
            $this->flash('Selecione uma faixa etária válida.');
            $this->redirect('/creche');
            return;
        }

        $campo = in_array($_POST['campo_experiencia'] ?? '', self::CAMPOS, true)
            ? $_POST['campo_experiencia'] : '';
        $tema = trim($_POST['tema'] ?? '');
        $quantidade = (int) ($_POST['quantidade'] ?? 4);

        try {
            $atividades = (new AI())->sugerirAtividadesLudicas(
                faixaEtaria: $faixa,
                campoExperiencia: $campo,
                tema: $tema,
                quantidade: $quantidade,
                userId: (int) $prof['id'],
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel sugerir atividades: ' . $e->getMessage());
            $this->redirect('/creche');
            return;
        }

        $model = new CrecheAtividade();
        foreach ($atividades as $a) {
            $model->create([
                'user_id' => $prof['id'],
                'faixa_etaria' => $faixa,
                'campo_experiencia' => $campo,
                'titulo' => $a['titulo'],
                'descricao' => $a['descricao'],
                'materiais' => $a['materiais'],
                'duracao' => $a['duracao'],
                'origem' => 'ia',
                'status' => 'rascunho',
            ]);
        }

        $n = count($atividades);
        Logger::activity('creche.atividade.generated', [
            'description' => "IA sugeriu {$n} atividades ludicas ({$faixa})",
        ]);

        $this->flash("{$n} atividade(s) lúdica(s) sugerida(s). Revise e aprove.");
        $this->redirect('/creche');
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $atividade = (new CrecheAtividade())->find((int) $id, $prof['id']);

        if (!$atividade) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $this->view('creche.show', [
            'title' => 'Revisar atividade',
            'atividade' => $atividade,
            'faixas' => self::FAIXAS,
            'campos' => self::CAMPOS,
        ]);
    }

    public function update(string $id): void
    {
        $prof = $this->professor();
        $model = new CrecheAtividade();
        $atividade = $model->find((int) $id, $prof['id']);

        if (!$atividade) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $faixa = in_array($_POST['faixa_etaria'] ?? '', self::FAIXAS, true)
            ? $_POST['faixa_etaria'] : $atividade['faixa_etaria'];
        $campo = in_array($_POST['campo_experiencia'] ?? '', self::CAMPOS, true)
            ? $_POST['campo_experiencia'] : '';

        if ($titulo === '') {
            $this->flash('Informe o título da atividade.');
            $this->redirect('/creche/atividades/' . $id);
            return;
        }

        $model->update((int) $id, [
            'faixa_etaria' => $faixa,
            'campo_experiencia' => $campo,
            'titulo' => $titulo,
            'descricao' => $_POST['descricao'] ?? '',
            'materiais' => $_POST['materiais'] ?? '',
            'duracao' => trim($_POST['duracao'] ?? ''),
            'status' => $atividade['status'],
        ]);

        $this->flash('Alteracoes salvas.');
        $this->redirect('/creche/atividades/' . $id);
    }

    public function aprovar(string $id): void
    {
        $prof = $this->professor();
        $model = new CrecheAtividade();

        if (!$model->find((int) $id, $prof['id'])) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $model->aprovar((int) $id);
        $this->flash('Atividade aprovada.');
        $this->redirect('/creche/atividades/' . $id);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new CrecheAtividade();

        if (!$model->find((int) $id, $prof['id'])) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $model->delete((int) $id);
        $this->flash('Atividade excluída.');
        $this->redirect('/creche');
    }
}
