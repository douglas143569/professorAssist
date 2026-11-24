<?php
/** Menu da Creche em cards de vidro neon. Uso: $ativa = 'atividades'; require '_nav.php'; */
$svg = fn(string $p) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
$ondas = '<span class="gl-waves" aria-hidden="true"><svg viewBox="0 0 400 80" preserveAspectRatio="none"><path class="gl-w1" d="M0 40Q50 0 100 40T200 40T300 40T400 40V80H0Z"/></svg></span>';
// [chave, titulo, descricao, url, rgb, icone]
$__itens = [
    ['atividades', 'Atividades', 'Ideias prontas para o dia a dia', '/creche', '244,114,182', $svg('<path d="M12 3l2.6 5.6 6.1.8-4.5 4.2 1.2 6L12 16.7 6.6 19.6l1.2-6L3.3 9.4l6.1-.8L12 3Z"/>')],
    ['cronograma', 'Cronograma', 'Planejamento mensal por semana', '/creche/cronograma', '34,211,238', $svg('<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>')],
    ['pacotes', 'Pacotes', 'Atividades temáticas reunidas', '/creche/pacotes', '251,146,60', $svg('<path d="M21 8l-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/>')],
    ['relatorios', 'Relatórios dos alunos', 'Relatório descritivo individual', '/creche/relatorios', '163,230,53', $svg('<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-6-6Z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>')],
    ['portfolio', 'Portfólio', 'Desenvolvimento de cada criança', '/creche/portfolio', '45,212,191', $svg('<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-8 8M14 20"/>')],
    ['banco', 'Banco de relatórios', 'Tudo o que você já gerou', '/creche/relatorios/banco', '167,139,250', $svg('<path d="M4 7c0-1.1 3.6-2 8-2s8 .9 8 2-3.6 2-8 2-8-.9-8-2Z"/><path d="M4 7v10c0 1.1 3.6 2 8 2s8-.9 8-2V7"/><path d="M4 12c0 1.1 3.6 2 8 2s8-.9 8-2"/>')],
];
?>
<?php if (($ativa ?? "") === "atividades"): ?>
<nav class="gl-grid cr-nav" aria-label="Seções da Creche">
    <?php foreach ($__itens as $n => [$chave, $titulo, $desc, $url, $rgb, $icone]): ?>
        <a class="gl-card cr-nav__card<?= ($ativa ?? '') === $chave ? ' is-active' : '' ?>" href="<?= $url ?>" style="--i:<?= $n ?>;--g:<?= $rgb ?>"<?= ($ativa ?? '') === $chave ? ' aria-current="page"' : '' ?>>
            <span class="gl-tile" aria-hidden="true"><?= $icone ?></span>
            <?= $ondas ?>
            <strong class="gl-pill"><?= htmlspecialchars($titulo) ?></strong>
            <small class="gl-ctx"><?= htmlspecialchars($desc) ?></small>
        </a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
