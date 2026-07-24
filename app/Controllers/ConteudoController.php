<?php

namespace App\Controllers;

use App\Models\Conteudo;
use App\Services\Logger;

class ConteudoController extends AppController
{
    public function show(string $id): void
    {
        $prof = $this->professor();
        $conteudo = (new Conteudo())->find((int) $id, $prof['id']);

        if (!$conteudo) {
            $this->notFound('Conteudo nao encontrado.');
            return;
        }

        $this->view('conteudos.show', [
            'title' => $conteudo['titulo'],
            'conteudo' => $conteudo,
        ]);
    }

    /** Salva a edicao do professor (titulo/corpo) mantendo o status. */
    public function update(string $id): void
    {
        $prof = $this->professor();
        $conteudo = (new Conteudo())->find((int) $id, $prof['id']);

        if (!$conteudo) {
            $this->notFound('Conteudo nao encontrado.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $corpo = $_POST['corpo'] ?? '';

        if ($titulo === '') {
            $this->flash('Informe o titulo do conteudo.');
            $this->redirect('/conteudos/' . $id);
            return;
        }

        (new Conteudo())->update((int) $id, [
            'titulo' => $titulo,
            'corpo' => $corpo,
            'status' => $conteudo['status'],
        ]);

        $this->flash('Alteracoes salvas.');
        $this->redirect('/conteudos/' . $id);
    }

    /** Aprova o conteudo (human-in-the-loop: rascunho -> aprovado). */
    public function aprovar(string $id): void
    {
        $prof = $this->professor();
        $conteudo = (new Conteudo())->find((int) $id, $prof['id']);

        if (!$conteudo) {
            $this->notFound('Conteudo nao encontrado.');
            return;
        }

        (new Conteudo())->update((int) $id, [
            'titulo' => $conteudo['titulo'],
            'corpo' => $conteudo['corpo'],
            'status' => 'aprovado',
        ]);

        Logger::activity('conteudo.approved', [
            'entity_type' => 'conteudo',
            'entity_id' => (int) $id,
            'description' => 'Conteudo aprovado pelo professor',
        ]);

        $this->flash('Conteudo aprovado.');
        $this->redirect('/conteudos/' . $id);
    }
}
