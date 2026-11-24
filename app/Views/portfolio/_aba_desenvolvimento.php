<?php
/** Desenvolvimento por area: texto + evidencias (fotos, videos, arquivos). Variaveis: $areas, $midiasAreas. */
use App\Models\PortfolioCrianca;

$porArea = [];
foreach ($midiasAreas as $m) {
    $porArea[$m['area']][] = $m;
}
?>
<p class="muted pf-intro">Descreva o desenvolvimento em cada área e anexe evidências. Cada área é salva separadamente.</p>

<div class="pf-areas">
<?php foreach (PortfolioCrianca::AREAS as $key => [$rotulo, $icone, $rgb]):
    $texto = (string) ($areas[$key] ?? '');
    $ms = $porArea[$key] ?? [];
?>
    <details class="pf-area" id="area-<?= $key ?>" style="--cor:<?= $rgb ?>" <?= ($texto !== '' || $ms) ? '' : '' ?>>
        <summary>
            <span class="pf-area__ico" aria-hidden="true"><?= $icone ?></span>
            <span class="pf-area__tit"><?= pf_h($rotulo) ?></span>
            <span class="pf-chip <?= $texto !== '' ? 'pf-chip--ok' : '' ?>"><?= $texto !== '' ? 'Descrita' : 'Pendente' ?></span>
            <?php if ($ms): ?><span class="pf-chip"><?= count($ms) ?> evidência(s)</span><?php endif; ?>
        </summary>

        <form method="post" action="/creche/portfolio/<?= (int) $c['id'] ?>/areas" enctype="multipart/form-data" class="form">
            <?= \App\Services\Csrf::campo() ?>
            <input type="hidden" name="area" value="<?= $key ?>">
            <label>Registro descritivo
                <?= pf_area_texto('texto', $texto, ['id' => 'txt-' . $key, 'rows' => 5, 'max' => 4000, 'ia' => 'redigir,corrigir', 'foco' => 'Desenvolvimento em ' . $rotulo . ' (Educação Infantil)', 'crianca' => $c['id'], 'ph' => 'Observações curtas já servem — use a IA para transformar em texto pedagógico.']) ?>
            </label>

            <?php if ($ms): ?>
                <div class="pf-media-grid"><?php foreach ($ms as $m) { echo pf_midia($m, true); } ?></div>
            <?php endif; ?>

            <label>Anexar evidências <small class="muted">(fotos, vídeos, PDF/Word)</small>
                <input type="file" name="arquivos[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,.pdf,.doc,.docx,.odt,.ppt,.pptx">
            </label>
            <div class="actions"><button class="btn btn--primary btn--sm" type="submit">Salvar <?= pf_h($rotulo) ?></button></div>
        </form>
    </details>
<?php endforeach; ?>
</div>
