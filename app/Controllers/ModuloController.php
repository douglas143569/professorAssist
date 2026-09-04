<?php

namespace App\Controllers;

use App\Models\Conteudo;
use App\Models\Disciplina;
use App\Models\Modulo;
use App\Models\Questao;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;

class ModuloController extends AppController
{
    /** Cria um modulo a partir da tela da disciplina. */
    public function store(string $disciplinaId): void
    {
        $prof = $this->professor();
        $disciplina = (new Disciplina())->find((int) $disciplinaId, $prof['id']);

        if (!$disciplina) {
            $this->notFound('Disciplina nao encontrada.');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo === '') {
            $this->flash('Informe o titulo do modulo.');
            $this->redirect('/disciplinas/' . $disciplinaId);
            return;
        }

        $id = (new Modulo())->create([
            'disciplina_id' => (int) $disciplinaId,
            'titulo' => $titulo,
            'ordem' => (int) ($_POST['ordem'] ?? 0),
            'objetivos' => trim($_POST['objetivos'] ?? ''),
            'codigos_bncc' => trim($_POST['codigos_bncc'] ?? ''),
        ]);

        Logger::activity('modulo.created', [
            'entity_type' => 'modulo',
            'entity_id' => $id,
            'description' => "Modulo criado: {$titulo}",
        ]);

        $this->flash('Modulo criado.');
        $this->redirect('/modulos/' . $id);
    }

    public function show(string $id): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $id, $prof['id']);

        if (!$modulo) {
            $this->notFound('Modulo nao encontrado.');
            return;
        }

        $this->view('modulos.show', [
            'title' => $modulo['titulo'],
            'modulo' => $modulo,
            'planos' => (new \App\Models\PlanoAula())->byModulo((int) $id),
            'atividades' => (new \App\Models\Atividade())->byModulo((int) $id),
            'conteudos' => (new Conteudo())->byModulo((int) $id),
            'questoes' => (new \App\Models\Questao())->byModulo((int) $id),
        ]);
    }

    /**
     * Folha de atividade para impressao: todas as questoes do tema numa pagina,
     * com alternativas. ?gabarito=1 marca as respostas corretas.
     */
    public function atividade(string $id): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $id, $prof['id']);

        if (!$modulo) {
            $this->notFound('Tema nao encontrado.');
            return;
        }

        $questaoModel = new Questao();
        $questoes = $questaoModel->byModulo((int) $id);
        foreach ($questoes as &$q) {
            $q['alternativas'] = $q['tipo'] === 'dissertativa'
                ? []
                : $questaoModel->alternativas((int) $q['id']);
        }
        unset($q);

        $this->view('modulos.atividade', [
            'title' => 'Atividade — ' . $modulo['titulo'],
            'modulo' => $modulo,
            'questoes' => $questoes,
            'gabarito' => isset($_GET['gabarito']),
        ]);
    }

    /** Gera o rascunho do conteudo da aula com a IA (RF-08). */
    public function gerarConteudo(string $id): void
    {
        $prof = $this->professor();
        $modulo = (new Modulo())->find((int) $id, $prof['id']);

        if (!$modulo) {
            $this->notFound('Modulo nao encontrado.');
            return;
        }

        // Primeira habilidade BNCC do modulo, se houver (ex: "EF06MA07,EF06MA08").
        $bncc = null;
        if (!empty($modulo['codigos_bncc'])) {
            $bncc = trim(explode(',', $modulo['codigos_bncc'])[0]);
        }

        // O que ja existe para este tema. Se houver algo, a IA recebe o resumo
        // e produz um material COMPLEMENTAR em vez de repetir o mesmo.
        $existentes = (new Conteudo())->byModulo((int) $id);
        $jaAbordado = $this->resumoDosConteudos($existentes);

        try {
            $texto = (new AI())->gerarConteudoAula(
                tema: $modulo['titulo'],
                habilidadeBncc: $bncc,
                etapa: $modulo['disciplina_etapa'],
                userId: (int) $prof['id'],
                jaAbordado: $jaAbordado,
            );
        } catch (AIException $e) {
            $this->flash('Nao foi possivel gerar com a IA: ' . $e->getMessage());
            $this->redirect('/modulos/' . $id);
            return;
        }

        $numero = count($existentes) + 1;

        $conteudoId = (new Conteudo())->create([
            'modulo_id' => (int) $id,
            'titulo' => $numero > 1
                ? "Conteudo {$numero}: " . $modulo['titulo']
                : 'Conteudo: ' . $modulo['titulo'],
            'corpo' => $texto,
            'origem' => 'ia',
            'status' => 'rascunho',
        ]);

        $this->flash($numero > 1
            ? "Novo material criado sobre o tema (material {$numero}). Revise e aprove."
            : 'Rascunho gerado pela IA. Revise e aprove.');
        $this->redirect('/conteudos/' . $conteudoId);
    }

    /**
     * Resume cada conteudo existente pelos seus titulos de secao (cabecalhos
     * markdown). E compacto -- gasta poucos tokens de entrada -- e diz a IA
     * exatamente qual recorte do tema ja foi coberto.
     *
     * @return string[]
     */
    private function resumoDosConteudos(array $conteudos): array
    {
        $resumos = [];

        // No maximo 5: o suficiente para orientar sem inchar o prompt.
        foreach (array_slice($conteudos, 0, 5) as $conteudo) {
            $titulos = [];

            foreach (preg_split('/\r?\n/', (string) ($conteudo['corpo'] ?? '')) as $linha) {
                if (preg_match('/^#{1,3}\s+(.+?)\s*$/', trim($linha), $m)) {
                    $titulos[] = $m[1];
                }
            }

            if ($titulos !== []) {
                $resumos[] = mb_substr(implode(' / ', array_slice($titulos, 0, 8)), 0, 300);
            }
        }

        return $resumos;
    }
}
