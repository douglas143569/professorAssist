<?php
/** Detalhe/edicao de um registro. Variaveis: $r, $c, $idade, $midias, $opcoesProjeto. */
use App\Models\PortfolioCrianca;
use App\Services\Csrf;

$sub = 'criancas';
require __DIR__ . '/_topo.php';
$areaRot = $r['area'] ? PortfolioCrianca::AREAS[$r['area']] ?? null : null;
?>
<div class="pf" data-token="<?= pf_h(Csrf::token()) ?>" data-crianca="<?= (int) $c['id'] ?>">
    <p class="breadcrumb"><a href="/creche/portfolio">Portfólio</a> / <a href="/creche/portfolio/<?= (int) $c['id'] ?>"><?= pf_h($c['nome']) ?></a> / <a href="/creche/portfolio/<?= (int) $c['id'] ?>?aba=linha">Linha do tempo</a></p>

    <article class="card pf-detalhe">
        <p class="pf-chips">
            <span class="pf-chip"><?= pf_data($r['data_registro']) ?></span>
            <span class="pf-chip"><?= pf_h($r['categoria']) ?></span>
            <?php if ($areaRot): ?><span class="pf-chip"><?= $areaRot[1] ?> <?= pf_h($areaRot[0]) ?></span><?php endif; ?>
            <?php if ($r['projeto_titulo']): ?><span class="pf-chip pf-chip--proj">📌 <?= pf_h($r['projeto_titulo']) ?></span><?php endif; ?>
        </p>
        <h2><?= pf_h($r['titulo']) ?></h2>
        <?php if ($midias): ?><div class="pf-media-grid pf-media-grid--lg"><?php foreach ($midias as $m) { echo pf_midia($m, true); } ?></div><?php endif; ?>
        <div class="pf-texto"><?= nl2br(pf_h($r['descricao'])) ?></div>
        <?php if ($r['objetivos']): ?><h4>Objetivos trabalhados</h4><div class="pf-texto"><?= nl2br(pf_h($r['objetivos'])) ?></div><?php endif; ?>
        <?php if ($r['habilidades']): ?><h4>Habilidades observadas</h4><ul class="pf-skills"><?php foreach (array_filter(array_map('trim', preg_split('/\R/', $r['habilidades']))) as $h): ?><li><?= pf_h(ltrim($h, "-•* ")) ?></li><?php endforeach; ?></ul><?php endif; ?>
    </article>

    <details class="card pf-edit">
        <summary><b>✏️ Editar registro</b></summary>
        <form method="post" action="/creche/portfolio/registros/<?= (int) $r['id'] ?>" enctype="multipart/form-data" class="form pf-form">
            <?= Csrf::campo() ?>
            <?php require __DIR__ . '/_registro_campos.php'; ?>
            <div class="actions"><button class="btn btn--primary" type="submit">Salvar alterações</button></div>
        </form>
    </details>

    <form method="post" action="/creche/portfolio/registros/<?= (int) $r['id'] ?>/excluir" onsubmit="return confirm('Excluir este registro e os arquivos dele?');">
        <?= Csrf::campo() ?>
        <button class="btn btn--danger" type="submit">Excluir registro</button>
    </form>
</div>
<dialog id="pf-lightbox" class="pf-lb" aria-label="Visualizar foto"><form method="dialog"><button class="pf-lb__x" aria-label="Fechar">×</button></form><img alt=""><p></p></dialog>
<script src="/assets/js/portfolio.js" defer></script>
