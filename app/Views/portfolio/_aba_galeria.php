<?php /** Galeria de evidencias. Variaveis: $midias, $filtros, $meses, $opcoesProjeto. */
$comTipo = true;
require __DIR__ . '/_filtros.php';

$contagem = array_count_values(array_column($midias, 'tipo'));
?>
<p class="muted pf-intro">
    <?= count($midias) ?> item(ns)
    <?php if ($contagem): ?>· <?= (int) ($contagem['foto'] ?? 0) ?> foto(s) · <?= (int) ($contagem['video'] ?? 0) ?> vídeo(s) · <?= (int) ($contagem['producao'] ?? 0) ?> produção(ões) · <?= (int) ($contagem['arquivo'] ?? 0) ?> arquivo(s)<?php endif; ?>
</p>
<?php if (!$midias): ?>
    <div class="card card--empty pf-empty">
        <div class="pf-empty__ico" aria-hidden="true">🖼️</div>
        <h3>A galeria está vazia</h3>
        <p class="muted">Fotos, vídeos e produções enviados nos registros e nas áreas de desenvolvimento aparecem aqui automaticamente.</p>
    </div>
<?php else: ?>
    <div class="pf-media-grid pf-media-grid--lg">
        <?php foreach ($midias as $m): ?>
            <figure class="pf-fig">
                <?= pf_midia($m) ?>
                <figcaption>
                    <?= $m['registro_titulo'] ? '<a href="/creche/portfolio/registros/' . (int) $m['registro_id'] . '">' . pf_h($m['registro_titulo']) . '</a>' : 'Área de desenvolvimento' ?>
                    <small><?= pf_data($m['data_ref']) ?><?= $m['projeto_titulo'] ? ' · ' . pf_h($m['projeto_titulo']) : '' ?></small>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
