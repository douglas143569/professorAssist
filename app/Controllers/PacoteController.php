<?php

namespace App\Controllers;

use App\Models\CrechePacote;
use App\Models\CrechePacoteItem;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

class PacoteController extends AppController
{
    public function index(): void
    {
        $prof = $this->professor();

        $this->view('creche.pacotes', [
            'title' => 'Pacotes de atividades',
            'faixas' => CrecheController::FAIXAS,
            'pacotes' => (new CrechePacote())->byUser($prof['id']),
        ]);
    }

    /** Cria um pacote completo com base no tema pedido. */
    public function criar(): void
    {
        $prof = $this->professor();

        $tema = trim($_POST['tema'] ?? '');
        $faixa = trim($_POST['faixa_etaria'] ?? '');
        $quantidade = (int) ($_POST['quantidade'] ?? 6);

        if ($tema === '') {
            $this->flash('Informe o tema do pacote.');
            $this->redirect('/creche/pacotes');
            return;
        }
        if (!in_array($faixa, CrecheController::FAIXAS, true)) {
            $this->flash('Selecione uma faixa etária válida.');
            $this->redirect('/creche/pacotes');
            return;
        }

        try {
            $itens = (new AI())->gerarPacoteAtividades(
                tema: $tema,
                faixaEtaria: $faixa,
                quantidade: $quantidade,
                userId: (int) $prof['id'],
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel criar o pacote: ' . $e->getMessage());
            $this->redirect('/creche/pacotes');
            return;
        }

        $id = (new CrechePacote())->createComItens([
            'user_id' => $prof['id'],
            'faixa_etaria' => $faixa,
            'tema' => $tema,
            'titulo' => 'Pacote: ' . $tema,
        ], $itens);

        Logger::activity('creche.pacote.created', [
            'entity_type' => 'creche_pacote',
            'entity_id' => $id,
            'description' => "IA criou pacote de atividades: {$tema}",
        ]);

        $this->flash(count($itens) . ' atividade(s) criada(s) no pacote. Ajuste o que quiser.');
        $this->redirect('/creche/pacotes/' . $id);
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $pacote = (new CrechePacote())->find((int) $id, $prof['id']);

        if (!$pacote) {
            $this->notFound('Pacote nao encontrado.');
            return;
        }

        $this->view('creche.pacote_show', [
            'title' => $pacote['titulo'],
            'pacote' => $pacote,
            'itens' => (new CrechePacoteItem())->byPacote((int) $id),
        ]);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new CrechePacote();

        if (!$model->find((int) $id, $prof['id'])) {
            $this->notFound('Pacote nao encontrado.');
            return;
        }

        $model->delete((int) $id);
        $this->flash('Pacote excluído.');
        $this->redirect('/creche/pacotes');
    }

    /* ---- Itens do pacote ---- */

    public function itemShow(string $id): void
    {
        $prof = $this->professor();
        $item = (new CrechePacoteItem())->find((int) $id, $prof['id']);

        if (!$item) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $this->view('creche.pacote_item', [
            'title' => 'Editar atividade',
            'item' => $item,
        ]);
    }

    public function itemUpdate(string $id): void
    {
        $prof = $this->professor();
        $model = new CrechePacoteItem();
        $item = $model->find((int) $id, $prof['id']);

        if (!$item) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo === '') {
            $this->flash('Informe o título da atividade.');
            $this->redirect('/creche/pacote-itens/' . $id);
            return;
        }

        $model->update((int) $id, [
            'tipo' => trim($_POST['tipo'] ?? ''),
            'titulo' => $titulo,
            'descricao' => $_POST['descricao'] ?? '',
            'materiais' => $_POST['materiais'] ?? '',
            'duracao' => trim($_POST['duracao'] ?? ''),
        ]);

        $this->flash('Atividade atualizada.');
        $this->redirect('/creche/pacotes/' . (int) $item['pacote_id']);
    }

    public function itemExcluir(string $id): void
    {
        $prof = $this->professor();
        $model = new CrechePacoteItem();
        $item = $model->find((int) $id, $prof['id']);

        if (!$item) {
            $this->notFound('Atividade nao encontrada.');
            return;
        }

        $model->delete((int) $id);
        $this->flash('Atividade removida do pacote.');
        $this->redirect('/creche/pacotes/' . (int) $item['pacote_id']);
    }
}
