<?php

namespace App\Controllers;

use App\Models\Escola;
use App\Models\Turma;
use App\Services\Logger;

/**
 * Escola: topo da hierarquia. A turma so existe dentro de uma escola.
 */
class EscolaController extends AppController
{
    public function index(): void
    {
        $prof = $this->professor();
        $this->view('escolas.index', [
            'title' => 'Escolas',
            'professor' => $prof,
            'escolas' => (new Escola())->byUser($prof['id']),
            'redes' => Escola::REDES,
        ]);
    }

    public function store(): void
    {
        $prof = $this->professor();
        $dados = $this->dadosDoForm();

        if ($dados['nome'] === '') {
            $this->flash('Informe o nome da escola.');
            $this->redirect('/escolas');
            return;
        }

        $id = (new Escola())->create($dados + ['user_id' => $prof['id']]);

        Logger::activity('escola.created', [
            'entity_type' => 'escola',
            'entity_id' => $id,
            'description' => "Escola criada: {$dados['nome']}",
        ]);

        $this->flash('Escola cadastrada. Agora crie as turmas dela.');
        $this->redirect('/escolas/' . $id);
    }

    /** Pagina da Escola: lista as turmas e permite criar novas. */
    public function show(string $id): void
    {
        $prof = $this->professor();
        $escola = (new Escola())->find((int) $id, $prof['id']);

        if (!$escola) {
            $this->notFound('Escola nao encontrada.');
            return;
        }

        $this->view('escolas.show', [
            'title' => $escola['nome'],
            'escola' => $escola,
            'turmas' => (new Turma())->byEscola((int) $id),
            'redes' => Escola::REDES,
        ]);
    }

    public function update(string $id): void
    {
        $prof = $this->professor();
        $model = new Escola();

        if (!$model->find((int) $id, $prof['id'])) {
            $this->notFound('Escola nao encontrada.');
            return;
        }

        $dados = $this->dadosDoForm();

        if ($dados['nome'] === '') {
            $this->flash('Informe o nome da escola.');
            $this->redirect('/escolas/' . (int) $id);
            return;
        }

        $model->update((int) $id, $dados);

        Logger::activity('escola.updated', [
            'entity_type' => 'escola',
            'entity_id' => (int) $id,
            'description' => "Escola atualizada: {$dados['nome']}",
        ]);

        $this->flash('Escola atualizada.');
        $this->redirect('/escolas/' . (int) $id);
    }

    public function excluir(string $id): void
    {
        $prof = $this->professor();
        $model = new Escola();
        $escola = $model->find((int) $id, $prof['id']);

        if (!$escola) {
            $this->notFound('Escola nao encontrada.');
            return;
        }

        $model->delete((int) $id);

        Logger::activity('escola.deleted', [
            'entity_type' => 'escola',
            'entity_id' => (int) $id,
            'description' => "Escola excluida: {$escola['nome']}",
        ]);

        $this->flash('Escola excluída.');
        $this->redirect('/escolas');
    }

    /** Le e normaliza o formulario de escola (criar e editar usam os mesmos campos). */
    private function dadosDoForm(): array
    {
        $rede = $_POST['rede'] ?? '';

        return [
            'nome' => trim($_POST['nome'] ?? ''),
            'rede' => isset(Escola::REDES[$rede]) ? $rede : null,
            'cidade' => trim($_POST['cidade'] ?? ''),
            'uf' => strtoupper(substr(trim($_POST['uf'] ?? ''), 0, 2)),
            'endereco' => trim($_POST['endereco'] ?? ''),
            'telefone' => trim($_POST['telefone'] ?? ''),
        ];
    }
}
