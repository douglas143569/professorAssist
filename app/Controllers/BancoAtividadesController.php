<?php

namespace App\Controllers;

use App\Models\Atividade;
use App\Models\CrecheAtividade;

/**
 * Banco de atividades: central das atividades geradas (ou criadas) no sistema,
 * tanto as sugeridas por tema (Ensino Fundamental/Medio) quanto as da creche.
 * So le o que ja esta salvo; criar/editar continua nas telas de origem.
 */
class BancoAtividadesController extends AppController
{
    private const FORMATOS = [
        'individual' => 'Individual', 'grupo' => 'Em grupo', 'discussao' => 'Discussão',
        'pratica' => 'Prática', 'projeto' => 'Projeto', 'jogo' => 'Jogo',
    ];

    public function index(): void
    {
        $prof = $this->professor();
        $uid = (int) $prof['id'];

        $busca = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
        $tipo = in_array($_GET['tipo'] ?? '', ['escola', 'creche'], true) ? $_GET['tipo'] : '';
        $status = in_array($_GET['status'] ?? '', ['rascunho', 'aprovado'], true) ? $_GET['status'] : '';
        $moduloId = max(0, (int) ($_GET['modulo'] ?? 0));

        $itens = [];
        if ($tipo !== 'creche') {
            foreach ((new Atividade())->bancoDoProfessor($uid, $busca, $status, $moduloId) as $a) {
                $itens[] = [
                    'origem_tipo' => 'escola',
                    'titulo' => $a['titulo'],
                    'contexto' => trim(($a['disciplina_nome'] ?? '') . ' · ' . ($a['modulo_titulo'] ?? ''), ' ·'),
                    'formato' => self::FORMATOS[$a['formato'] ?? ''] ?? null,
                    'duracao' => $a['duracao'],
                    'status' => $a['status'],
                    'origem' => $a['origem'],
                    'url' => '/atividades/' . (int) $a['id'],
                    'criado' => $a['created_at'],
                ];
            }
        }
        // Atividades da creche nao pertencem a um tema: somem quando se filtra por tema.
        if ($tipo !== 'escola' && $moduloId === 0) {
            foreach ((new CrecheAtividade())->byUser($uid) as $a) {
                if ($status !== '' && $a['status'] !== $status) {
                    continue;
                }
                if ($busca !== '' && mb_stripos($a['titulo'] . ' ' . $a['faixa_etaria'], $busca) === false) {
                    continue;
                }
                $itens[] = [
                    'origem_tipo' => 'creche',
                    'titulo' => $a['titulo'],
                    'contexto' => 'Creche · ' . $a['faixa_etaria'],
                    'formato' => null,
                    'duracao' => $a['duracao'] ?? null,
                    'status' => $a['status'],
                    'origem' => $a['origem'],
                    'url' => '/creche/atividades/' . (int) $a['id'],
                    'criado' => $a['created_at'] ?? '',
                ];
            }
        }
        usort($itens, fn($x, $y) => strcmp((string) $y['criado'], (string) $x['criado']));

        $this->view('atividades.banco', [
            'title' => 'Banco de atividades',
            'itens' => $itens,
            'busca' => $busca,
            'tipo' => $tipo,
            'status' => $status,
            'moduloId' => $moduloId,
        ]);
    }
}
