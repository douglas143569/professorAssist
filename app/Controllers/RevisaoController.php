<?php

namespace App\Controllers;

use App\Models\Revisao;
use App\Services\Logger;

/**
 * Fila de revisao: uma tela para aprovar em lote tudo que a IA rascunhou.
 *
 * Antes, aprovar era item a item: abrir, aprovar, voltar -- cerca de tres
 * acoes por item, e a tela de aprovacao ainda devolvia para o proprio item,
 * sem caminho de volta para a lista.
 */
class RevisaoController extends AppController
{
    public function index(): void
    {
        $prof = $this->professor();
        $revisao = new Revisao();

        $tipo = $_GET['tipo'] ?? null;
        if ($tipo !== null && !isset(Revisao::TIPOS[$tipo])) {
            $tipo = null;
        }

        $this->view('revisao.index', [
            'title' => 'Revisar',
            'contagem' => $revisao->contar($prof['id']),
            'itens' => $revisao->pendentes($prof['id'], $tipo),
            'tipoAtivo' => $tipo,
            'tipos' => Revisao::TIPOS,
        ]);
    }

    /**
     * Aprova de uma vez tudo que foi marcado. Cada item vem no POST como
     * "tipo:id" -- a checagem de dono e refeita item a item no model.
     */
    public function aprovarLote(): void
    {
        $prof = $this->professor();
        $revisao = new Revisao();

        $marcados = (array) ($_POST['itens'] ?? []);
        $aprovados = 0;
        $recusados = 0;

        foreach ($marcados as $marcado) {
            [$tipo, $id] = array_pad(explode(':', (string) $marcado, 2), 2, null);

            if ($tipo === null || !ctype_digit((string) $id)) {
                continue;
            }

            if ($revisao->aprovar($tipo, (int) $id, (int) $prof['id'])) {
                $aprovados++;
            } else {
                $recusados++;
            }
        }

        if ($aprovados > 0) {
            Logger::activity('revisao.aprovou_lote', [
                'description' => "Aprovou {$aprovados} item(ns) na fila de revisao",
                'properties' => ['aprovados' => $aprovados, 'recusados' => $recusados],
            ]);
        }

        $this->flash(match (true) {
            $aprovados === 0 => 'Nenhum item marcado.',
            $aprovados === 1 => '1 item aprovado.',
            default => "{$aprovados} itens aprovados.",
        });

        $this->redirect($this->voltarPara());
    }

    /**
     * Volta para a tela de onde o professor veio (a fila ou o banco de
     * questoes), em vez de deixa-lo preso no item aprovado.
     * So aceita caminho interno.
     */
    private function voltarPara(): string
    {
        $destino = trim((string) ($_POST['voltar'] ?? ''));

        if ($destino === '' || $destino[0] !== '/' || str_starts_with($destino, '//')) {
            return '/revisar';
        }

        return $destino;
    }
}
