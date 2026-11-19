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

    <!-- Cartoes de acesso rapido (vidro neon) -->
    <?php
        $ondas = '<span class="gl-waves" aria-hidden="true"><svg viewBox="0 0 400 80" preserveAspectRatio="none"><path class="gl-w1" d="M0 40Q50 0 100 40T200 40T300 40T400 40V80H0Z"/></svg></span>';
        // [titulo, descricao, url, cor (rgb), icone]
        $acessos = [
            ['Criar atividade', 'Gere questões e dinâmicas com IA', '/escolas', '244,114,182', $ic['atividade']],
            ['Criar plano de aula', 'Plano completo em segundos', '/escolas', '34,211,238', $ic['plano']],
            ['Criar prova', 'Monte provas personalizadas', '/provas', '251,113,85', $ic['prova']],
            ['Minhas turmas', 'Organize turmas, matérias e alunos', '/turmas', '163,230,53', $ic['turma']],
            ['Calendário', 'Veja e crie seus compromissos', '/calendario', '167,139,250', $ic['cal']],
            ['Creche', 'Atividades, cronograma e relatórios', '/creche', '251,146,190', $ic['creche']],
            ['Banco de atividades', 'Tudo o que você já gerou, num só lugar', '/banco-atividades', '129,140,248', $ic['questao']],
        ];
    ?>
    <div class="hm-sec"><h3>O que você quer fazer hoje?</h3></div>
    <section class="gl-grid hc-grid">
        <button type="button" class="gl-card hc-card hc-start" id="hc-start" style="--i:0;--g:251,191,36" aria-haspopup="dialog">
            <span class="gl-tile" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.6 5.6 6.1.8-4.5 4.2 1.2 6L12 16.7 6.6 19.6l1.2-6L3.3 9.4l6.1-.8L12 3Z"/></svg></span>
            <?= $ondas ?>
            <strong class="gl-pill">Comece aqui</strong>
            <small class="gl-ctx">Primeira vez? Vamos te guiar em 1 clique</small>
            <span class="gl-open">Começar <span aria-hidden="true">↗</span></span>
        </button>
        <?php foreach ($acessos as $n => [$titulo, $desc, $url, $rgb, $icone]): ?>
            <a class="gl-card hc-card" href="<?= $h($url) ?>" style="--i:<?= $n + 1 ?>;--g:<?= $h($rgb) ?>">
                <span class="gl-tile" aria-hidden="true"><?= $icone ?></span>
                <?= $ondas ?>
                <strong class="gl-pill"><?= $h($titulo) ?></strong>
                <small class="gl-ctx"><?= $h($desc) ?></small>
                <span class="gl-open">Abrir <span aria-hidden="true">↗</span></span>
            </a>
        <?php endforeach; ?>
    </section>

    <!-- Pergunta do "Comece aqui" -->
    <dialog class="hc-dialog" id="hc-dialog" aria-labelledby="hc-titulo">
        <button type="button" class="hc-dialog__x" id="hc-fechar" aria-label="Fechar">×</button>
        <h3 id="hc-titulo">Você é professor(a) de escola ou de creche?</h3>
        <p>Escolha o seu caso e levamos você ao lugar certo.</p>
        <div class="hc-dialog__opcoes">
            <a href="/escolas#nova-escola" class="gl-card hc-opcao" style="--i:0;--g:251,191,36">
                <span class="gl-tile" aria-hidden="true"><?= $ic['escola'] ?></span>
                <strong class="gl-pill">Escola</strong>
                <small class="gl-ctx">Ensino Fundamental e Médio — vamos cadastrar a sua escola</small>
            </a>
            <a href="/creche" class="gl-card hc-opcao" style="--i:1;--g:244,114,182">
                <span class="gl-tile" aria-hidden="true"><?= $ic['creche'] ?></span>
                <strong class="gl-pill">Creche</strong>
                <small class="gl-ctx">Educação Infantil — atividades, cronograma e relatórios</small>
            </a>
        </div>
    </dialog>
    <script>
        (function () {
            var dlg = document.getElementById('hc-dialog');
            if (!dlg || typeof dlg.showModal !== 'function') {
                // Navegador sem <dialog>: vai direto para a pagina de escolha.
                var b = document.getElementById('hc-start');
                if (b) b.addEventListener('click', function () { location.href = '/escolas#nova-escola'; });
                return;
            }
            document.getElementById('hc-start').addEventListener('click', function () { dlg.showModal(); });
            document.getElementById('hc-fechar').addEventListener('click', function () { dlg.close(); });
            dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
        })();
    </script>

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
