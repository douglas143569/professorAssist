<?php
/** Topo comum: cabecalho + menu da Creche + sub-menu do Portfolio. Uso: $sub = 'criancas'; require '_topo.php'; */
require_once __DIR__ . '/_helpers.php';
$sub = $sub ?? 'criancas';
$__abas = [
    'criancas' => ['Crianças', '/creche/portfolio'],
    'projetos' => ['Projetos', '/creche/portfolio/projetos'],
    'coordenacao' => ['Coordenação', '/creche/portfolio/coordenacao'],
];
?>
<link rel="stylesheet" href="/assets/css/portfolio.css">
<div class="no-print">
    <div class="page-header">
        <div>
            <h1>Creche · Portfólio</h1>
            <p class="muted">Registre, acompanhe e apresente o desenvolvimento de cada criança ao longo do ano.</p>
        </div>
    </div>
    <?php $ativa = 'portfolio'; require dirname(__DIR__) . '/creche/_nav.php'; ?>
    <nav class="pf-subnav" aria-label="Portfólio">
        <?php foreach ($__abas as $k => [$rot, $url]): ?>
            <a href="<?= $url ?>" class="<?= $sub === $k ? 'is-on' : '' ?>"<?= $sub === $k ? ' aria-current="page"' : '' ?>><?= $rot ?></a>
        <?php endforeach; ?>
    </nav>
</div>
