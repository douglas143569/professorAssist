<?php
/**
 * Dashboard do professor.
 *
 * Variaveis: $professor, $stats, $mes (criados no mes), $turmasResumo,
 * $recentes (atividade recente), $revisao.
 */
$h = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$primeiroNome = explode(' ', trim($professor['name']))[0];

$svg = static fn (string $d) => '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
$seta = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

$ic = [
    'escola' => $svg('<path d="M12 3 2 8h20L12 3Z"/><path d="M4 8v11h16V8"/><path d="M9 19v-5h6v5"/>'),
    'turma' => $svg('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.870M16 3.130a4 4 0 0 1 0 7.750"/>'),
    'questao' => $svg('<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>'),
    'plano' => $svg('<path d="M2 5.500C4.500 4 8 4 12 6c4-2 7.500-2 10-.5V19c-2.500-1.500-6-1.500-10 .5-4-2-7.500-2-10-.5z"/><path d="M12 6v13.500"/>'),
    'atividade' => $svg('<rect x="5" y="3" width="14" height="18" rx="2"/><path d="m9 12 2 2 4-4"/>'),
    'prova' => $svg('<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>'),
    'cal' => $svg('<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/>'),
    'creche' => $svg('<path d="M12 3a3 3 0 0 1 3 3c0 1.500-1 2-1 3h-4c0-1-1-1.500-1-3a3 3 0 0 1 3-3Z"/><path d="M5 21v-3a7 7 0 0 1 14 0v3"/>'),
];

// Cartoes de numeros: [chave em $stats, chave em $mes, rotulo, icone, cor]
$cartoes = [
    ['escolas', null, 'Escolas', 'escola', 'blue'],
    ['turmas', 'turmas', 'Minhas turmas', 'turma', 'blue'],
    ['questoes', 'questoes', 'Questões no banco', 'questao', 'green'],
    ['planos', 'planos', 'Planos de aula', 'plano', 'blue'],
    ['atividades', 'atividades', 'Atividades criadas', 'atividade', 'green'],
    ['provas', 'provas', 'Provas criadas', 'prova', 'violet'],
];

// "ha 2 dias", "ha 1 semana"...
$quando = static function (string $data): string {
    $seg = max(0, time() - strtotime($data));
    return match (true) {
        $seg < 3600 => 'há ' . max(1, intdiv($seg, 60)) . ' min',
        $seg < 86400 => 'há ' . intdiv($seg, 3600) . ' h',
        $seg < 172800 => 'ontem',
        $seg < 604800 => 'há ' . intdiv($seg, 86400) . ' dias',
        $seg < 1209600 => 'há 1 semana',
        default => 'há ' . intdiv($seg, 604800) . ' semanas',
    };
};

// Grafico de producao do mes (barras em CSS puro).
$producao = [
    ['Planos', (int) $mes['planos'], '#12a874'],
    ['Apresent.', (int) $mes['apresentacoes'], '#9aa8ff'],
    ['Atividades', (int) $mes['atividades'], '#7c6cf0'],
    ['Conteúdos', (int) $mes['conteudos'], '#ffa94d'],
    ['Provas', (int) $mes['provas'], '#ff7a7a'],
];
$maxProducao = max(1, ...array_column($producao, 1));

// Donut do banco de questoes.
$totQ = (int) $stats['questoes'];
$aprov = (int) $stats['questoes_aprovadas'];
$rasc = max(0, $totQ - $aprov);
$pctAprov = $totQ > 0 ? round($aprov / $totQ * 100) : 0;
$grau = $totQ > 0 ? $aprov / $totQ * 360 : 0;
$donut = $totQ > 0
    ? "conic-gradient(#12a874 0 {$grau}deg, #f0c14b {$grau}deg 360deg)"
    : 'conic-gradient(#e3ebe7 0 360deg)';

// Dica do dia: muda a cada dia, em rodizio.
$dicas = [
    'Peça à IA para adaptar uma atividade ao nível da sua turma: basta descrever o perfil dos alunos no tema da aula.',
    'Revise e aprove os rascunhos na fila de revisão: só questões aprovadas entram na geração de provas.',
    'Gere versões embaralhadas da mesma prova para reduzir a cola em sala.',
    'Cadastre eventos no calendário e veja provas e entregas da semana de relance.',
    'Use o plano de aula gerado como ponto de partida e edite com o seu jeito de ensinar.',
];
$dica = $dicas[(int) date('z') % count($dicas)];

$cores = ['EM' => ['#d6f5e6', '#0b7a54'], 0 => ['#d9e6ff', '#2a5bd7'], 1 => ['#e5dcff', '#5b3df5'], 2 => ['#ffe6cf', '#b45309']];
?>
<link rel="stylesheet" href="/assets/css/home.css">

<div class="hm">

    <header class="hm-greet">
        <h2>Olá, <?= $h($primeiroNome) ?>! 👋</h2>
        <p>Que tal criar algo incrível hoje?</p>
    </header>

    <?php if ($revisao['total'] > 0): ?>
        <a class="chamada-revisao" href="/revisar">
            <span class="chamada-revisao__num"><?= (int) $revisao['total'] ?></span>
            <span class="chamada-revisao__texto">
                <strong><?= $revisao['total'] === 1 ? 'item espera' : 'itens esperam' ?> sua revisão</strong>
                <small>
                    <?php
                    $partes = [];
                    foreach ($revisao['por_tipo'] as $chave => $n) {
                        if ($n > 0) {
                            $partes[] = $n . ' ' . mb_strtolower(\App\Models\Revisao::TIPOS[$chave]['rotulo']);
                        }
                    }
                    echo $h(implode(' · ', $partes));
                    ?>
                </small>
            </span>
            <span class="chamada-revisao__cta">Revisar e aprovar →</span>
        </a>
    <?php endif; ?>

    <!-- Banner -->
    <section class="hm-banner">
        <div class="hm-banner__bot" aria-hidden="true">🤖</div>
        <div class="hm-banner__txt">
            <span class="hm-badge">✨ IA Educacional</span>
            <h3>Crie atividades, provas e planos de aula em segundos</h3>
            <p>Conte com a inteligência artificial para economizar tempo e focar no que realmente importa: ensinar.</p>
        </div>
        <a href="/escolas" class="hm-btn">Criar com IA <?= $seta ?></a>
    </section>

    <!-- Numeros -->
    <section class="hm-stats">
        <?php foreach ($cartoes as [$ks, $km, $rotulo, $icone, $cor]): ?>
            <article class="hm-stat">
                <span class="hm-ico hm-ico--<?= $cor ?>"><?= $ic[$icone] ?></span>
                <div>
                    <b><?= (int) ($stats[$ks] ?? 0) ?></b>
                    <span><?= $h($rotulo) ?></span>
                    <?php if ($km !== null): ?>
                        <small class="<?= ((int) $mes[$km]) > 0 ? 'up' : '' ?>">+<?= (int) $mes[$km] ?> este mês</small>
                    <?php elseif ($ks === 'questoes'): ?>
                        <small><?= $aprov ?> aprovadas</small>
                    <?php else: ?>
                        <small>&nbsp;</small>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <!-- Acoes rapidas -->
    <div class="hm-sec"><h3>Ações rápidas</h3></div>
    <section class="hm-quick">
        <a href="/escolas" class="hm-q hm-q--green"><span class="hm-q__ico"><?= $ic['atividade'] ?></span><div><strong>Criar atividade</strong><small>Gere questões com IA</small></div><i><?= $seta ?></i></a>
        <a href="/escolas" class="hm-q hm-q--blue"><span class="hm-q__ico"><?= $ic['plano'] ?></span><div><strong>Criar plano de aula</strong><small>Plano completo em segundos</small></div><i><?= $seta ?></i></a>
        <a href="/provas" class="hm-q hm-q--violet"><span class="hm-q__ico"><?= $ic['prova'] ?></span><div><strong>Criar prova</strong><small>Questões personalizadas</small></div><i><?= $seta ?></i></a>
        <a href="/turmas" class="hm-q hm-q--orange"><span class="hm-q__ico"><?= $ic['turma'] ?></span><div><strong>Gerenciar turmas</strong><small>Organize seus alunos</small></div><i><?= $seta ?></i></a>
        <a href="/calendario" class="hm-q hm-q--pink"><span class="hm-q__ico"><?= $ic['cal'] ?></span><div><strong>Abrir calendário</strong><small>Veja seus compromissos</small></div><i><?= $seta ?></i></a>
        <a href="/creche" class="hm-q hm-q--teal"><span class="hm-q__ico"><?= $ic['creche'] ?></span><div><strong>Creche</strong><small>Atividades e cronograma</small></div><i><?= $seta ?></i></a>
    </section>

    <!-- Turmas / Producao / Banco -->
    <section class="hm-trio">
        <article class="hm-card">
            <div class="hm-card__head"><h3>Minhas turmas</h3><a href="/turmas">Ver todas <?= $seta ?></a></div>
            <?php if (!$turmasResumo): ?>
                <p class="hm-empty">Você ainda não criou turmas. <a href="/escolas">Comece por uma escola →</a></p>
            <?php else: ?>
                <ul class="hm-turmas">
                    <?php foreach ($turmasResumo as $i => $t):
                        [$bg, $fg] = $cores[$i % 3];
                        $sigla = mb_strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N}]/u', '', $t['nome']), 0, 2));
                    ?>
                        <li>
                            <a href="/turmas/<?= (int) $t['id'] ?>">
                                <span class="hm-sigla" style="background:<?= $bg ?>;color:<?= $fg ?>"><?= $h($sigla) ?></span>
                                <span class="hm-turma__txt">
                                    <strong><?= $h($t['nome']) ?></strong>
                                    <small><?= (int) $t['n_alunos'] ?> <?= (int) $t['n_alunos'] === 1 ? 'aluno' : 'alunos' ?> • <?= (int) $t['n_materias'] ?> <?= (int) $t['n_materias'] === 1 ? 'matéria' : 'matérias' ?></small>
                                </span>
                                <span class="hm-chev">›</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <article class="hm-card">
            <div class="hm-card__head"><h3>Produção este mês</h3><span class="hm-pill"><?= $h(date('m/Y')) ?></span></div>
            <div class="hm-bars" role="img" aria-label="Produção do mês por tipo">
                <?php foreach ($producao as [$rot, $val, $cor]): ?>
                    <div class="hm-bar">
                        <b><?= $val ?></b>
                        <span class="hm-bar__col" style="height:<?= max(3, (int) round($val / $maxProducao * 100)) ?>%;background:<?= $cor ?>"></span>
                        <small><?= $h($rot) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="hm-card">
            <div class="hm-card__head"><h3>Banco de questões</h3></div>
            <div class="hm-bank">
                <div class="hm-donut" style="background:<?= $donut ?>"><div><b><?= $totQ ?></b><small>questões</small></div></div>
                <ul class="hm-legend">
                    <li><i style="background:#12a874"></i>Aprovadas <b><?= $aprov ?> (<?= (int) $pctAprov ?>%)</b></li>
                    <li><i style="background:#f0c14b"></i>Rascunho <b><?= $rasc ?> (<?= $totQ > 0 ? 100 - (int) $pctAprov : 0 ?>%)</b></li>
                </ul>
            </div>
            <a href="/revisar" class="hm-btn hm-btn--ghost">Revisar questões <?= $seta ?></a>
        </article>
    </section>

    <!-- Atividade recente / frase / dica -->
    <section class="hm-trio hm-trio--b">
        <article class="hm-card">
            <div class="hm-card__head"><h3>Atividade recente</h3></div>
            <?php if (!$recentes): ?>
                <p class="hm-empty">Suas ações aparecerão aqui conforme você usa o sistema.</p>
            <?php else: ?>
                <ul class="hm-feed">
                    <?php foreach ($recentes as $r): ?>
                        <li><span class="hm-feed__dot"></span><span class="hm-feed__txt"><?= $h($r['texto']) ?></span><small><?= $h($quando($r['quando'])) ?></small></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <article class="hm-card hm-quote">
            <span class="hm-quote__ico">🎓</span>
            <h3>Pequenas ações geram grandes aprendizagens!</h3>
            <p>Você está construindo uma educação mais eficiente e significativa.</p>
        </article>

        <article class="hm-card">
            <div class="hm-card__head"><h3>💡 Dica do dia</h3></div>
            <p class="hm-tip"><?= $h($dica) ?></p>
            <a href="/escolas" class="hm-btn hm-btn--ghost">Começar agora <?= $seta ?></a>
        </article>
    </section>
</div>
