<?php
/**
 * Portfolio individual (hub com abas).
 * Variaveis: $c, $prog, $aba, $idade, $opcoesProjeto + dados da aba (ver PortfolioController@show).
 */
use App\Services\Csrf;

$sub = 'criancas';
require __DIR__ . '/_topo.php';

$abas = [
    'visao' => 'Visão geral', 'apresentacao' => 'Apresentação', 'desenvolvimento' => 'Desenvolvimento',
    'registro' => 'Novo registro', 'linha' => 'Linha do tempo', 'galeria' => 'Galeria',
    'avaliacoes' => 'Avaliações', 'perfil' => 'Perfil',
];

$aba = in_array($aba ?? '', array_keys($abas), true) ? $aba : 'visao';
?>
<div class="pf" data-token="<?= pf_h(Csrf::token()) ?>" data-crianca="<?= (int) $c['id'] ?>">
    <p class="breadcrumb no-print"><a href="/creche/portfolio">Portfólio</a> / <?= pf_h($c['nome']) ?></p>

    <header class="pf-hero">
        <?= pf_avatar($c, 'pf-avatar--lg') ?>
        <div class="pf-hero__txt">
            <h2><?= pf_h($c['nome']) ?></h2>
            <p class="pf-chips">
                <span class="pf-chip"><?= pf_h($c['turma']) ?></span>
                <span class="pf-chip"><?= pf_h($idade) ?></span>
                <span class="pf-chip"><?= pf_h($c['periodo']) ?></span>
                <span class="pf-chip">Prof.ª <?= pf_h($c['professora']) ?></span>
            </p>
            <div class="pf-hero__prog"><?= pf_barra($prog['pct']) ?><span><?= $prog['pct'] ?>% do portfólio</span></div>
        </div>
        <div class="pf-hero__act">
            <a class="btn btn--primary" href="?aba=registro">+ Novo registro</a>
            <a class="btn btn--ghost" href="/creche/portfolio/<?= (int) $c['id'] ?>/relatorio" target="_blank" rel="noopener">📄 Relatório (PDF)</a>
        </div>
    </header>

    <?php if (!$c['autoriza_imagem']): ?>
        <p class="pf-aviso" role="note">⚠️ A autorização de uso de imagem desta criança ainda não foi marcada. Confirme com a família antes de registrar fotos e vídeos ou de imprimir o relatório. <a href="?aba=perfil">Atualizar perfil</a></p>
    <?php endif; ?>

    <nav class="pf-tabs" aria-label="Seções do portfólio">
        <?php foreach ($abas as $k => $rot): ?>
            <a href="?aba=<?= $k ?>" class="<?= $aba === $k ? 'is-on' : '' ?>"<?= $aba === $k ? ' aria-current="page"' : '' ?>><?= $rot ?></a>
        <?php endforeach; ?>
    </nav>

    <section class="pf-panel">
        <?php require __DIR__ . '/_aba_' . $aba . '.php'; ?>
    </section>
</div>

<dialog id="pf-lightbox" class="pf-lb" aria-label="Visualizar foto">
    <form method="dialog"><button class="pf-lb__x" aria-label="Fechar">×</button></form>
    <img alt=""><p></p>
</dialog>
<script src="/assets/js/portfolio.js" defer></script>
