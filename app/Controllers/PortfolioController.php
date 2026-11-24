<?php

namespace App\Controllers;

use App\Models\PortfolioAvaliacao;
use App\Models\PortfolioCrianca;
use App\Models\PortfolioProjeto;
use App\Models\PortfolioRegistro;
use App\Services\AI;
use App\Services\AIException;
use App\Services\Logger;
use App\Services\Upload;
use App\Services\UploadException;

/**
 * Portfolio Pedagogico Infantil (aba Creche): perfil da crianca, desenvolvimento por
 * area, registros, linha do tempo, galeria, avaliacoes, projetos e relatorio imprimivel.
 *
 * Dado pessoal de menor: tudo e filtrado pelo professor logado, arquivos ficam fora de
 * public/ e so saem por aqui, e nome/foto/texto da crianca nunca vao para logs nem para a IA.
 */
class PortfolioController extends AppController
{
    private const ABAS = ['visao', 'apresentacao', 'desenvolvimento', 'registro', 'linha', 'galeria', 'avaliacoes', 'perfil'];
    private const PERIODOS = ['Manhã', 'Tarde', 'Integral'];
    private const MAX_ARQUIVOS = 20;

    private PortfolioCrianca $criancas;
    private PortfolioRegistro $registros;
    private PortfolioProjeto $projetos;
    private PortfolioAvaliacao $avaliacoes;

    public function __construct()
    {
        parent::__construct();
        $this->criancas = new PortfolioCrianca();
        $this->registros = new PortfolioRegistro();
        $this->projetos = new PortfolioProjeto();
        $this->avaliacoes = new PortfolioAvaliacao();
    }

    // =================================================================== dashboards

    /** Dashboard da professora + lista de criancas. */
    public function index(): void
    {
        $uid = $this->uid();
        $busca = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
        $turma = mb_substr(trim((string) ($_GET['turma'] ?? '')), 0, 80);

        $todas = $this->criancas->byUser($uid);
        $pendentes = array_filter($todas, fn ($c) => !PortfolioCrianca::progresso($c)['completo']);
        $desatualizadas = array_filter($todas, [PortfolioCrianca::class, 'desatualizada']);
        $semAvaliacao = array_filter($todas, fn ($c) => (int) $c['n_aval_recente'] === 0 && (int) $c['n_registros'] > 0);

        $this->view('portfolio.index', [
            'title' => 'Portfólio',
            'criancas' => $this->criancas->byUser($uid, $busca, $turma),
            'turmas' => $this->criancas->turmas($uid),
            'busca' => $busca,
            'turma' => $turma,
            'totalCriancas' => count($todas),
            'totais' => $this->registros->totais($uid),
            'pendentes' => count($pendentes),
            'desatualizadas' => array_values($desatualizadas),
            'proximasAvaliacoes' => array_slice(array_values($semAvaliacao), 0, 6),
            'recentes' => $this->registros->recentes($uid, 6),
        ]);
    }

    /** Visao geral da coordenacao (todas as criancas/turmas/professoras da conta). */
    public function coordenacao(): void
    {
        $todas = $this->criancas->byUser($this->uid());

        $turmas = [];
        $professoras = [];
        $incompletos = [];
        $completos = 0;
        $somaPct = 0;

        foreach ($todas as $c) {
            $p = PortfolioCrianca::progresso($c);
            $old = PortfolioCrianca::desatualizada($c);
            $somaPct += $p['pct'];
            $completos += $p['completo'] ? 1 : 0;

            $turmas = $this->somarGrupo($turmas, (string) $c['turma'], $c, $p, $old);
            $professoras = $this->somarGrupo($professoras, (string) $c['professora'], $c, $p, $old);

            if (!$p['completo']) {
                $incompletos[] = $c + ['_prog' => $p, '_old' => $old];
            }
        }

        $media = static function (array $grupos): array {
            foreach ($grupos as &$g) {
                $g['pct'] = (int) round($g['pct'] / max(1, $g['criancas']));
            }
            ksort($grupos);

            return array_values($grupos);
        };

        usort($incompletos, fn ($a, $b) => $a['_prog']['pct'] <=> $b['_prog']['pct']);

        $this->view('portfolio.coordenacao', [
            'title' => 'Portfólio · Coordenação',
            'total' => count($todas),
            'completos' => $completos,
            'mediaPct' => $todas ? (int) round($somaPct / count($todas)) : 0,
            'turmas' => $media($turmas),
            'professoras' => $media($professoras),
            'incompletos' => $incompletos,
            'totais' => $this->registros->totais($this->uid()),
        ]);
    }

    /** Acumula uma crianca nos totais de um grupo (turma ou professora). */
    private function somarGrupo(array $grupos, string $chave, array $c, array $p, bool $desatualizada): array
    {
        $g = $grupos[$chave] ?? ['nome' => $chave, 'criancas' => 0, 'completos' => 0, 'pct' => 0, 'desatualizadas' => 0, 'ultimo' => null];
        $g['criancas']++;
        $g['completos'] += $p['completo'] ? 1 : 0;
        $g['pct'] += $p['pct'];
        $g['desatualizadas'] += $desatualizada ? 1 : 0;
        if ($c['ultimo_registro'] && ($g['ultimo'] === null || $c['ultimo_registro'] > $g['ultimo'])) {
            $g['ultimo'] = $c['ultimo_registro'];
        }
        $grupos[$chave] = $g;

        return $grupos;
    }

    // =================================================================== crianca

    public function nova(): void
    {
        $this->view('portfolio.nova', [
            'title' => 'Nova criança',
            'turmas' => $this->criancas->turmas($this->uid()),
            'periodos' => self::PERIODOS,
            'professoraPadrao' => (string) $this->professor()['name'],
        ]);
    }

    public function criar(): void
    {
        $uid = $this->uid();
        $d = $this->dadosPerfil();

        if ($d['nome'] === '' || $d['turma'] === '') {
            $this->flash('Informe ao menos o nome da criança e a turma.');
            $this->redirect('/creche/portfolio/nova');
        }

        $id = $this->criancas->create($uid, $d);
        $this->salvarFotoPerfil($id);

        Logger::activity('portfolio.crianca_criada', [
            'entity_type' => 'portfolio_crianca',
            'entity_id' => $id,
            'description' => 'Criança cadastrada no portfólio',
        ]);

        $this->flash('Criança cadastrada! Comece escrevendo a apresentação ou criando o primeiro registro.');
        $this->redirect('/creche/portfolio/' . $id);
    }

    public function show(string $id): void
    {
        $uid = $this->uid();
        $c = $this->criancas->findComResumo((int) $id, $uid);
        if ($c === null) {
            $this->notFound('Criança não encontrada.');
            return;
        }

        $aba = in_array($_GET['aba'] ?? 'visao', self::ABAS, true) ? $_GET['aba'] : 'visao';
        $cid = (int) $c['id'];

        $dados = [
            'title' => $c['nome'],
            'c' => $c,
            'prog' => PortfolioCrianca::progresso($c),
            'aba' => $aba,
            'idade' => PortfolioCrianca::idade($c['nascimento']),
            'opcoesProjeto' => $this->projetos->opcoes($uid),
        ];

        $filtros = [
            'mes' => (string) ($_GET['mes'] ?? ''),
            'projeto' => (int) ($_GET['projeto'] ?? 0),
            'categoria' => (string) ($_GET['categoria'] ?? ''),
            'tipo' => (string) ($_GET['tipo'] ?? ''),
        ];

        switch ($aba) {
            case 'visao':
                $dados['recentes'] = array_slice($this->registros->timeline($cid, $uid), 0, 4);
                break;
            case 'desenvolvimento':
                $dados['areas'] = $this->criancas->areas($cid, $uid);
                $dados['midiasAreas'] = $this->registros->midiasDaArea($cid, $uid);
                break;
            case 'linha':
                $dados['filtros'] = $filtros;
                $dados['itens'] = $this->registros->timeline($cid, $uid, $filtros);
                $dados['meses'] = $this->registros->meses($cid, $uid);
                break;
            case 'galeria':
                $dados['filtros'] = $filtros;
                $dados['midias'] = $this->registros->galeria($cid, $uid, $filtros);
                $dados['meses'] = $this->registros->meses($cid, $uid);
                break;
            case 'avaliacoes':
                $dados['lista'] = $this->avaliacoes->byCrianca($cid, $uid);
                break;
        }

        $this->view('portfolio.show', $dados);
    }

    public function atualizar(string $id): void
    {
        $c = $this->minha($id);
        $d = $this->dadosPerfil();

        if ($d['nome'] === '' || $d['turma'] === '') {
            $this->flash('Nome e turma são obrigatórios.');
            $this->voltar($c['id'], 'perfil');
        }

        $this->criancas->updatePerfil((int) $c['id'], $this->uid(), $d);
        $this->salvarFotoPerfil((int) $c['id'], $c['foto_path']);
        if (!empty($_POST['remover_foto']) && $c['foto_path'] && empty(Upload::arquivosEnviados('foto'))) {
            Upload::remover($c['foto_path']);
            $this->criancas->setFoto((int) $c['id'], $this->uid(), null);
        }

        $this->flash('Perfil atualizado.');
        $this->voltar($c['id'], 'perfil');
    }

    public function excluir(string $id): void
    {
        $c = $this->minha($id);
        $this->criancas->delete((int) $c['id'], $this->uid());
        Upload::removerPastaPortfolio((int) $c['id']);

        Logger::activity('portfolio.crianca_excluida', [
            'entity_type' => 'portfolio_crianca',
            'entity_id' => (int) $c['id'],
            'description' => 'Portfólio de criança excluído',
        ]);

        $this->flash('Portfólio excluído, com todos os registros e arquivos.');
        $this->redirect('/creche/portfolio');
    }

    public function foto(string $id): void
    {
        $c = $this->criancas->find((int) $id, $this->uid());
        if ($c === null || !$c['foto_path']) {
            $this->notFound('Foto não encontrada.');
            return;
        }
        $this->transmitir($c['foto_path'], basename($c['foto_path']), true);
    }

    // =================================================================== apresentacao e areas

    public function apresentacao(string $id): void
    {
        $c = $this->minha($id);
        $this->criancas->updateApresentacao(
            (int) $c['id'],
            $this->uid(),
            $this->texto('apresentacao', 4000),
            $this->texto('adaptacao', 3000),
            $this->texto('caracteristicas', 3000)
        );

        $this->flash('Apresentação salva.');
        $this->voltar($c['id'], 'apresentacao');
    }

    /** Salva o texto de UMA area e anexa evidencias (fotos, videos, arquivos). */
    public function areas(string $id): void
    {
        $c = $this->minha($id);
        $area = (string) ($_POST['area'] ?? '');
        if (!isset(PortfolioCrianca::AREAS[$area])) {
            $this->flash('Área inválida.');
            $this->voltar($c['id'], 'desenvolvimento');
        }

        $this->criancas->salvarArea((int) $c['id'], $this->uid(), $area, $this->texto('texto', 4000));
        [$salvos, $erros] = $this->salvarArquivos((int) $c['id'], null, $area);

        $msg = 'Área "' . PortfolioCrianca::AREAS[$area][0] . '" salva' . ($salvos ? " ({$salvos} evidência(s) anexada(s))" : '') . '.';
        $this->flash($erros ? $msg . ' Atenção: ' . implode(' ', $erros) : $msg);
        $this->redirect('/creche/portfolio/' . (int) $c['id'] . '?aba=desenvolvimento#area-' . $area);
    }

    public function mensagem(string $id): void
    {
        $c = $this->minha($id);
        $this->criancas->updateMensagemFinal((int) $c['id'], $this->uid(), $this->texto('mensagem_final', 3000));
        $this->flash('Mensagem final salva (aparece no fim do relatório).');
        $this->voltar($c['id'], 'visao');
    }

    // =================================================================== registros

    public function registroCriar(string $id): void
    {
        $c = $this->minha($id);
        $uid = $this->uid();
        $d = $this->dadosRegistro();

        if ($d['titulo'] === '' || $d['descricao'] === '') {
            $this->flash('Dê um título e escreva a observação pedagógica para salvar o registro.');
            $this->voltar($c['id'], 'registro');
        }

        $rid = $this->registros->create($uid, (int) $c['id'], $d);
        [$salvos, $erros] = $this->salvarArquivos((int) $c['id'], $rid, $d['area'] ?: null, !empty($_POST['producao']));

        $msg = 'Registro salvo' . ($salvos ? " com {$salvos} arquivo(s)" : '') . '. Já está na linha do tempo e no portfólio.';
        $this->flash($erros ? $msg . ' Atenção: ' . implode(' ', $erros) : $msg);
        $this->voltar($c['id'], 'linha');
    }

    public function registroShow(string $id): void
    {
        $r = $this->registros->find((int) $id, $this->uid());
        if ($r === null) {
            $this->notFound('Registro não encontrado.');
            return;
        }
        $c = $this->criancas->find((int) $r['crianca_id'], $this->uid());

        $this->view('portfolio.registro', [
            'title' => $r['titulo'],
            'r' => $r,
            'c' => $c,
            'idade' => PortfolioCrianca::idade($c['nascimento']),
            'midias' => $this->registros->midiasDoRegistro((int) $r['id'], $this->uid()),
            'opcoesProjeto' => $this->projetos->opcoes($this->uid()),
        ]);
    }

    public function registroUpdate(string $id): void
    {
        $r = $this->registros->find((int) $id, $this->uid());
        if ($r === null) {
            $this->notFound('Registro não encontrado.');
            return;
        }

        $d = $this->dadosRegistro();
        if ($d['titulo'] === '' || $d['descricao'] === '') {
            $this->flash('Título e descrição são obrigatórios.');
            $this->redirect('/creche/portfolio/registros/' . (int) $r['id']);
        }

        $this->registros->update((int) $r['id'], $this->uid(), $d);
        [$salvos, $erros] = $this->salvarArquivos((int) $r['crianca_id'], (int) $r['id'], $d['area'] ?: null, !empty($_POST['producao']));

        $msg = 'Registro atualizado' . ($salvos ? " (+{$salvos} arquivo(s))" : '') . '.';
        $this->flash($erros ? $msg . ' Atenção: ' . implode(' ', $erros) : $msg);
        $this->redirect('/creche/portfolio/registros/' . (int) $r['id']);
    }

    public function registroExcluir(string $id): void
    {
        $r = $this->registros->find((int) $id, $this->uid());
        if ($r === null) {
            $this->notFound('Registro não encontrado.');
            return;
        }

        foreach ($this->registros->pathsDoRegistro((int) $r['id'], $this->uid()) as $path) {
            Upload::remover($path);
        }
        $this->registros->delete((int) $r['id'], $this->uid());

        $this->flash('Registro excluído.');
        $this->voltar($r['crianca_id'], 'linha');
    }

    // =================================================================== midias

    /** Entrega uma foto/video/arquivo (so o dono) -- nada fica publico em /public. */
    public function midia(string $id): void
    {
        $m = $this->registros->findMidia((int) $id, $this->uid());
        if ($m === null) {
            $this->notFound('Arquivo não encontrado.');
            return;
        }
        $this->transmitir($m['path'], $m['nome_original'], $m['tipo'] !== 'arquivo');
    }

    public function midiaExcluir(string $id): void
    {
        $m = $this->registros->findMidia((int) $id, $this->uid());
        if ($m === null) {
            $this->notFound('Arquivo não encontrado.');
            return;
        }

        Upload::remover($m['path']);
        $this->registros->deleteMidia((int) $m['id'], $this->uid());
        $this->flash('Arquivo removido.');

        $volta = $m['registro_id']
            ? '/creche/portfolio/registros/' . (int) $m['registro_id']
            : '/creche/portfolio/' . (int) $m['crianca_id'] . '?aba=' . ($m['area'] ? 'desenvolvimento' : 'galeria');
        $this->redirect($volta);
    }

    // =================================================================== avaliacoes

    public function avaliacaoCriar(string $id): void
    {
        $c = $this->minha($id);
        $per = (string) ($_POST['periodicidade'] ?? '');
        $periodo = (string) ($_POST['periodo'] ?? '');
        $ano = (int) ($_POST['ano'] ?? date('Y'));

        if (!isset(PortfolioAvaliacao::PERIODICIDADES[$per]) || !in_array($periodo, PortfolioAvaliacao::PERIODICIDADES[$per][1], true)) {
            $this->flash('Escolha o tipo e o período da avaliação.');
            $this->voltar($c['id'], 'avaliacoes');
        }

        $aid = $this->avaliacoes->create($this->uid(), (int) $c['id'], $per, $periodo, max(2000, min(2100, $ano)));
        $this->redirect('/creche/portfolio/avaliacoes/' . $aid);
    }

    public function avaliacaoShow(string $id): void
    {
        $a = $this->avaliacoes->find((int) $id, $this->uid());
        if ($a === null) {
            $this->notFound('Avaliação não encontrada.');
            return;
        }
        $c = $this->criancas->find((int) $a['crianca_id'], $this->uid());

        $this->view('portfolio.avaliacao', [
            'title' => 'Avaliação · ' . $a['periodo'],
            'a' => $a,
            'c' => $c,
        ]);
    }

    public function avaliacaoUpdate(string $id): void
    {
        $a = $this->avaliacoes->find((int) $id, $this->uid());
        if ($a === null) {
            $this->notFound('Avaliação não encontrada.');
            return;
        }

        $dados = [];
        foreach (array_keys(PortfolioAvaliacao::SECOES) as $campo) {
            $dados[$campo] = $this->texto($campo, 5000);
        }
        $this->avaliacoes->update((int) $a['id'], $this->uid(), $dados);

        $this->flash('Avaliação salva.');
        $this->redirect('/creche/portfolio/avaliacoes/' . (int) $a['id']);
    }

    public function avaliacaoExcluir(string $id): void
    {
        $a = $this->avaliacoes->find((int) $id, $this->uid());
        if ($a === null) {
            $this->notFound('Avaliação não encontrada.');
            return;
        }
        $this->avaliacoes->delete((int) $a['id'], $this->uid());
        $this->flash('Avaliação excluída.');
        $this->voltar($a['crianca_id'], 'avaliacoes');
    }

    // =================================================================== projetos

    public function projetos(): void
    {
        $this->view('portfolio.projetos', [
            'title' => 'Projetos pedagógicos',
            'projetos' => $this->projetos->byUser($this->uid()),
        ]);
    }

    public function projetoCriar(): void
    {
        $d = $this->dadosProjeto();
        if ($d['titulo'] === '') {
            $this->flash('Dê um nome ao projeto.');
            $this->redirect('/creche/portfolio/projetos');
        }
        $id = $this->projetos->create($this->uid(), $d);
        $this->flash('Projeto criado. Agora você pode ligar registros a ele.');
        $this->redirect('/creche/portfolio/projetos/' . $id);
    }

    public function projetoShow(string $id): void
    {
        $p = $this->projetos->find((int) $id, $this->uid());
        if ($p === null) {
            $this->notFound('Projeto não encontrado.');
            return;
        }

        $porCrianca = [];
        foreach ($this->registros->doProjeto((int) $p['id'], $this->uid()) as $r) {
            $porCrianca[$r['crianca_id']]['nome'] = $r['nome'];
            $porCrianca[$r['crianca_id']]['registros'][] = $r;
        }

        $this->view('portfolio.projeto', ['title' => $p['titulo'], 'p' => $p, 'porCrianca' => $porCrianca]);
    }

    public function projetoUpdate(string $id): void
    {
        $p = $this->projetos->find((int) $id, $this->uid());
        if ($p === null) {
            $this->notFound('Projeto não encontrado.');
            return;
        }
        $d = $this->dadosProjeto();
        if ($d['titulo'] === '') {
            $this->flash('O projeto precisa de um nome.');
            $this->redirect('/creche/portfolio/projetos/' . (int) $p['id']);
        }
        $this->projetos->update((int) $p['id'], $this->uid(), $d);
        $this->flash('Projeto atualizado.');
        $this->redirect('/creche/portfolio/projetos/' . (int) $p['id']);
    }

    public function projetoExcluir(string $id): void
    {
        $p = $this->projetos->find((int) $id, $this->uid());
        if ($p !== null) {
            $this->projetos->delete((int) $p['id'], $this->uid());
            $this->flash('Projeto excluído. Os registros continuam nos portfólios, só sem projeto.');
        }
        $this->redirect('/creche/portfolio/projetos');
    }

    // =================================================================== relatorio

    /** Documento imprimivel (Ctrl+P / "Salvar como PDF"), no estilo de portfolio de escola particular. */
    public function relatorio(string $id): void
    {
        $uid = $this->uid();
        $c = $this->criancas->find((int) $id, $uid);
        if ($c === null) {
            $this->notFound('Criança não encontrada.');
            return;
        }
        $cid = (int) $c['id'];

        $registros = array_reverse($this->registros->timeline($cid, $uid)); // cronologico
        $midiasPorRegistro = [];
        foreach ($this->registros->galeria($cid, $uid) as $m) {
            $midiasPorRegistro[$m['registro_id'] ?? 0][] = $m;
        }

        $this->viewSemLayout('portfolio.relatorio', [
            'c' => $c,
            'idade' => PortfolioCrianca::idade($c['nascimento']),
            'areas' => $this->criancas->areas($cid, $uid),
            'registros' => $registros,
            'midiasPorRegistro' => $midiasPorRegistro,
            'avaliacoes' => array_reverse($this->avaliacoes->byCrianca($cid, $uid)),
            'galeria' => array_values(array_filter(
                $this->registros->galeria($cid, $uid),
                fn ($m) => in_array($m['tipo'], ['foto', 'producao'], true)
            )),
            'escola' => (string) $this->professor()['name'],
        ]);
    }

    // =================================================================== IA

    /** POST /creche/portfolio/ia -> JSON {ok, texto}. Nunca envia nome nem foto. */
    public function ia(): void
    {
        $acao = (string) ($_POST['acao'] ?? '');
        $texto = mb_substr(trim((string) ($_POST['texto'] ?? '')), 0, 6000);
        $foco = mb_substr(trim((string) ($_POST['foco'] ?? '')), 0, 160);
        $idade = '';

        if (!empty($_POST['crianca_id'])) {
            $c = $this->criancas->find((int) $_POST['crianca_id'], $this->uid());
            $idade = $c ? PortfolioCrianca::idade($c['nascimento']) : '';
            $idade = $idade === '—' ? '' : $idade;

            // Avaliacao: a IA se baseia nos registros reais da crianca (lidos aqui, no servidor).
            if ($acao === 'avaliacao' && $c) {
                $texto = $this->resumoDosRegistros((int) $c['id']);
            }
        }

        if ($texto === '') {
            $this->json(['ok' => false, 'mensagem' => $acao === 'avaliacao'
                ? 'Crie alguns registros antes: a IA se baseia neles.'
                : 'Escreva algo no campo primeiro.'], 422);
            return;
        }

        try {
            $resposta = (new AI())->assistirPortfolio($acao, $texto, $foco, $idade, $this->uid());
            $this->json(['ok' => true, 'texto' => $resposta]);
        } catch (AIException $e) {
            $this->json(['ok' => false, 'mensagem' => $e->getMessage()], 502);
        }
    }

    private function resumoDosRegistros(int $criancaId): string
    {
        $linhas = [];
        foreach (array_slice($this->registros->timeline($criancaId, $this->uid()), 0, 25) as $r) {
            $linhas[] = '- (' . date('d/m/Y', strtotime($r['data_registro'])) . ') ' . $r['titulo'] . ': ' . mb_substr($r['descricao'], 0, 400);
        }

        return mb_substr(implode("\n", $linhas), 0, 6000);
    }

    // =================================================================== auxiliares

    private function uid(): int
    {
        return (int) $this->professor()['id'];
    }

    /** Crianca do professor logado ou 404 (encerra a requisicao). */
    private function minha(string $id): array
    {
        $c = $this->criancas->find((int) $id, $this->uid());
        if ($c === null) {
            $this->notFound('Criança não encontrada.');
            exit;
        }

        return $c;
    }

    private function voltar(int|string $criancaId, string $aba): never
    {
        $this->redirect('/creche/portfolio/' . (int) $criancaId . '?aba=' . $aba);
    }

    private function texto(string $campo, int $max): string
    {
        return mb_substr(trim((string) ($_POST[$campo] ?? '')), 0, $max);
    }

    private function data(string $campo, ?string $padrao = null): ?string
    {
        $v = trim((string) ($_POST[$campo] ?? ''));
        $d = \DateTime::createFromFormat('Y-m-d', $v);

        return $d && $d->format('Y-m-d') === $v ? $v : $padrao;
    }

    private function dadosPerfil(): array
    {
        $periodo = (string) ($_POST['periodo'] ?? 'Integral');

        return [
            'nome' => $this->texto('nome', 120),
            'nascimento' => $this->data('nascimento'),
            'turma' => $this->texto('turma', 80),
            'periodo' => in_array($periodo, self::PERIODOS, true) ? $periodo : 'Integral',
            'professora' => $this->texto('professora', 120) ?: (string) $this->professor()['name'],
            'data_matricula' => $this->data('data_matricula'),
            'familia' => $this->texto('familia', 3000),
            'autoriza_imagem' => !empty($_POST['autoriza_imagem']),
        ];
    }

    private function dadosRegistro(): array
    {
        $categoria = (string) ($_POST['categoria'] ?? '');
        $area = (string) ($_POST['area'] ?? '');
        $projeto = (int) ($_POST['projeto_id'] ?? 0);

        return [
            'titulo' => $this->texto('titulo', 160),
            'descricao' => $this->texto('descricao', 6000),
            'data_registro' => $this->data('data_registro', date('Y-m-d')),
            'categoria' => in_array($categoria, PortfolioRegistro::CATEGORIAS, true) ? $categoria : 'Rotina',
            'area' => isset(PortfolioCrianca::AREAS[$area]) ? $area : '',
            'objetivos' => $this->texto('objetivos', 2000),
            'habilidades' => $this->texto('habilidades', 2000),
            // so aceita projeto que e do proprio professor
            'projeto_id' => $projeto && $this->projetos->find($projeto, $this->uid()) ? $projeto : null,
        ];
    }

    private function dadosProjeto(): array
    {
        return [
            'titulo' => $this->texto('titulo', 160),
            'objetivos' => $this->texto('objetivos', 3000),
            'atividades' => $this->texto('atividades', 3000),
            'data_inicio' => $this->data('data_inicio'),
            'data_fim' => $this->data('data_fim'),
        ];
    }

    /**
     * Salva os arquivos do campo "arquivos[]".
     *
     * @return array{0:int, 1:list<string>} [quantidade salva, mensagens de erro]
     */
    private function salvarArquivos(int $criancaId, ?int $registroId, ?string $area, bool $comoProducao = false): array
    {
        $salvos = 0;
        $erros = [];
        $arquivos = array_slice(Upload::arquivosEnviados('arquivos'), 0, self::MAX_ARQUIVOS);

        foreach ($arquivos as $file) {
            try {
                $r = Upload::salvarPortfolio($file, $criancaId);
                $tipo = ($comoProducao && $r['tipo'] === 'foto') ? 'producao' : $r['tipo'];
                $this->registros->addMidia($this->uid(), $criancaId, $registroId, $area, $tipo, $r['path'], $r['nome']);
                $salvos++;
            } catch (UploadException $e) {
                $erros[] = $e->getMessage();
            }
        }

        return [$salvos, $erros];
    }

    /** Foto do perfil (campo "foto"): troca a anterior, se houver. */
    private function salvarFotoPerfil(int $criancaId, ?string $anterior = null): void
    {
        $arquivos = Upload::arquivosEnviados('foto');
        if (!$arquivos) {
            return;
        }

        try {
            $r = Upload::salvarPortfolio($arquivos[0], $criancaId);
            if ($r['tipo'] !== 'foto') {
                Upload::remover($r['path']);
                throw new UploadException('A foto do perfil precisa ser uma imagem (jpg, png ou webp).');
            }
            $this->criancas->setFoto($criancaId, $this->uid(), $r['path']);
            if ($anterior) {
                Upload::remover($anterior);
            }
        } catch (UploadException $e) {
            $this->flash('Perfil salvo, mas a foto não: ' . $e->getMessage());
        }
    }

    /** Stream de arquivo guardado em storage/uploads (com suporte a Range, p/ videos). */
    private function transmitir(string $relativo, string $nomeOriginal, bool $inline): never
    {
        $abs = Upload::caminhoAbsoluto($relativo);
        if (!is_file($abs)) {
            $this->notFound('Arquivo não encontrado.');
            exit;
        }

        $mimes = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
            'mp4' => 'video/mp4', 'webm' => 'video/webm', 'pdf' => 'application/pdf',
        ];
        $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $tamanho = filesize($abs);
        $inicio = 0;
        $fim = $tamanho - 1;

        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        header('Accept-Ranges: bytes');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="'
            . rawurlencode($nomeOriginal) . '"; filename*=UTF-8\'\'' . rawurlencode($nomeOriginal));

        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
            if ($m[1] !== '') {
                $inicio = (int) $m[1];
            }
            if ($m[2] !== '') {
                $fim = min((int) $m[2], $tamanho - 1);
            }
            if ($m[1] === '' && $m[2] !== '') { // "-N" = ultimos N bytes
                $inicio = max(0, $tamanho - (int) $m[2]);
                $fim = $tamanho - 1;
            }
            if ($inicio > $fim || $inicio >= $tamanho) {
                http_response_code(416);
                header("Content-Range: bytes */{$tamanho}");
                exit;
            }
            http_response_code(206);
            header("Content-Range: bytes {$inicio}-{$fim}/{$tamanho}");
        }

        header('Content-Length: ' . ($fim - $inicio + 1));

        $h = fopen($abs, 'rb');
        fseek($h, $inicio);
        $resta = $fim - $inicio + 1;
        while ($resta > 0 && !feof($h)) {
            $bloco = fread($h, min(8192, $resta));
            echo $bloco;
            $resta -= strlen($bloco);
        }
        fclose($h);
        exit;
    }
}
