<?php
    /** @var array $apr  @var array[] $slides  @var bool $temTurma  @var ?array $material  @var bool $ffmpegOk */
    $aprId = (int) $apr['id'];
    $ffmpegOk = !empty($ffmpegOk);

    $slugCorte = static fn (string $path): string => 'cortar-' . preg_replace('/\W/', '', basename($path));

    $midiaSlide = static function (array $s, int $aprId, bool $ffmpegOk) use ($slugCorte): string {
        $imgAtual = htmlspecialchars((string) ($s['imagem'] ?? ''));
        $vidUrl = htmlspecialchars((string) ($s['video_url'] ?? ''));
        $vidAtual = htmlspecialchars((string) ($s['video_arquivo'] ?? ''));
        $mudoChk = !empty($s['video_mudo']) ? ' checked' : '';
        $mudoVal = !empty($s['video_mudo']) ? '1' : '0';

        $thumb = '';
        $rmImg = '';
        if (($s['imagem'] ?? '') !== '') {
            $base = rawurlencode(basename($s['imagem']));
            $thumb = "<img class=\"slide-edit__thumb\" src=\"/apresentacoes/{$aprId}/midia/{$base}\" alt=\"\">";
            $rmImg = '<label class="slide-edit__rm"><input type="checkbox" class="js-rm-img"> remover imagem</label>';
        }

        $rmVid = '';
        $painelCorte = '';
        if (($s['video_arquivo'] ?? '') !== '') {
            $base = rawurlencode(basename($s['video_arquivo']));
            $slug = $slugCorte($s['video_arquivo']);
            $rmVid = '<p class="muted" style="margin:4px 0 0;">vídeo enviado ✓</p>'
                . '<label class="slide-edit__rm"><input type="checkbox" class="js-rm-vid"> remover vídeo</label>';

            $btnCorte = $ffmpegOk
                ? "<button type=\"submit\" class=\"btn btn--primary btn--sm\" form=\"{$slug}\">✂ Cortar e salvar</button>"
                : '<p class="muted" style="font-size:.8rem;">Para cortar de verdade, instale o ffmpeg no servidor e defina FFMPEG_PATH no .env.</p>';

            $painelCorte = <<<HTML
<details class="trim-panel">
    <summary>Recortar vídeo</summary>
    <div class="trim-panel__corpo">
        <video class="js-trim-v" src="/apresentacoes/{$aprId}/midia/{$base}" preload="metadata" muted controls playsinline></video>
        <label>Início <input type="range" class="js-trim-a" min="0" max="100" step="0.1" value="0"></label>
        <label>Fim <input type="range" class="js-trim-b" min="0" max="100" step="0.1" value="100"></label>
        <p class="trim-times"><span class="js-trim-ta">0:00</span><span class="js-trim-tb">0:00</span></p>
        <p>
            <button type="button" class="btn btn--ghost btn--sm js-trim-preview">▶ Prévia do trecho</button>
            {$btnCorte}
        </p>
    </div>
</details>
HTML;
        }

        return <<<HTML
<div class="slide-edit__midia">
    <strong class="slide-edit__midia-lbl">Mídia <span class="muted">(opcional)</span></strong>
    <div class="slide-edit__midia-grid">
        <div>
            {$thumb}
            <label>Imagem — enviar arquivo
                <input type="file" name="slide_imagem[]" accept="image/png,image/jpeg,image/webp,image/gif">
            </label>
            <input type="hidden" name="slide_imagem_atual[]" value="{$imgAtual}">
            {$rmImg}
            <input type="hidden" name="slide_remover_imagem[]" value="0">
        </div>
        <div>
            <label>Vídeo — link
                <input type="url" name="slide_video_url[]" value="{$vidUrl}" placeholder="Link do YouTube ou Vimeo">
            </label>
            <label>Vídeo — enviar arquivo <span class="muted">(mp4/webm)</span>
                <input type="file" name="slide_video[]" accept="video/mp4,video/webm">
            </label>
            <input type="hidden" name="slide_video_arquivo_atual[]" value="{$vidAtual}">
            <label class="slide-edit__rm"><input type="checkbox" class="js-mudo"{$mudoChk}> sem áudio</label>
            <input type="hidden" name="slide_video_mudo[]" value="{$mudoVal}">
            {$rmVid}
            <input type="hidden" name="slide_remover_video[]" value="0">
            {$painelCorte}
        </div>
    </div>
</div>
HTML;
    };

    $renderSlide = static function (int $i, array $s) use ($aprId, $ffmpegOk, $midiaSlide): string {
        $titulo = htmlspecialchars($s['titulo'] ?? '');
        $topicos = htmlspecialchars(implode("\n", array_map('strval', $s['topicos'] ?? [])));
        $nota = htmlspecialchars($s['nota'] ?? '');
        $n = $i + 1;
        $midia = $midiaSlide($s, $aprId, $ffmpegOk);
        return <<<HTML
<fieldset class="slide-edit">
    <div class="slide-edit__head">
        <strong class="slide-edit__num">Slide {$n}</strong>
        <span class="slide-edit__acoes">
            <button type="button" class="btn btn--ghost btn--sm js-slide-up" title="Subir">↑</button>
            <button type="button" class="btn btn--ghost btn--sm js-slide-down" title="Descer">↓</button>
            <button type="button" class="btn btn--danger btn--sm js-slide-del" title="Remover">✕</button>
        </span>
    </div>
    <label>Título do slide
        <input type="text" name="slide_titulo[]" value="{$titulo}" maxlength="160">
    </label>
    <label>Tópicos <span class="muted">(um por linha)</span>
        <textarea name="slide_topicos[]" rows="4">{$topicos}</textarea>
    </label>
    <label>Nota do apresentador <span class="muted">(o que você fala)</span>
        <textarea name="slide_nota[]" rows="2">{$nota}</textarea>
    </label>
    {$midia}
</fieldset>
HTML;
    };
?>
<p class="breadcrumb">
    <a href="/modulos/<?= (int) $apr['modulo_id'] ?>"><?= htmlspecialchars($apr['modulo_titulo']) ?></a> /
    Apresentação
</p>

<div class="page-header">
    <div>
        <h1><?= htmlspecialchars($apr['titulo']) ?></h1>
        <p class="muted">
            <span class="badge badge--<?= htmlspecialchars($apr['status']) ?>"><?= htmlspecialchars($apr['status']) ?></span>
            <span class="tag tag--origem"><?= $apr['origem'] === 'ia' ? 'gerado por IA' : 'manual' ?></span>
            <?php if (!empty($apr['foco'])): ?>
                <span class="tag">foco: <?= htmlspecialchars($apr['foco']) ?></span>
            <?php endif; ?>
            <span class="tag"><?= count($slides) ?> slide(s)</span>
        </p>
    </div>
    <div style="display:flex;gap:8px;align-items:flex-start;">
        <a href="/apresentacoes/<?= (int) $apr['id'] ?>/apresentar" class="btn btn--ghost" target="_blank" rel="noopener">▶ Apresentar</a>
        <?php if ($apr['status'] !== 'aprovado'): ?>
            <form method="post" action="/apresentacoes/<?= (int) $apr['id'] ?>/aprovar">
                <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--primary">✓ Aprovar</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($apr['origem'] === 'ia' && $apr['status'] === 'rascunho'): ?>
    <div class="notice">Rascunho gerado por IA. <strong>Revise os slides e o resumo</strong> antes de aprovar — você é responsável pelo material final.</div>
<?php endif; ?>

<form method="post" action="/apresentacoes/<?= (int) $apr['id'] ?>" class="form" enctype="multipart/form-data">
    <?= \App\Services\Csrf::campo() ?>

    <div class="form--inline">
        <label style="flex:1 1 auto;">Título
            <input type="text" name="titulo" value="<?= htmlspecialchars($apr['titulo']) ?>" required>
        </label>
        <label style="flex:0 0 240px;">Foco <span class="muted">(opcional)</span>
            <input type="text" name="foco" value="<?= htmlspecialchars($apr['foco'] ?? '') ?>">
        </label>
    </div>

    <h2>Slides</h2>
    <div id="slides">
        <?php foreach ($slides as $i => $s): ?>
            <?= $renderSlide((int) $i, $s) ?>
        <?php endforeach; ?>
    </div>
    <p><button type="button" class="btn btn--ghost" id="js-add-slide">+ Adicionar slide</button></p>

    <h2>Resumo do assunto</h2>
    <label>Resumo <span class="muted">(texto corrido; comece linhas com "- " para listas)</span>
        <textarea name="resumo" rows="12" class="mono"><?= htmlspecialchars($apr['resumo'] ?? '') ?></textarea>
    </label>

    <?php if (trim((string) ($apr['resumo'] ?? '')) !== ''): ?>
        <p class="muted" style="margin:14px 0 4px;">Como o aluno vê:</p>
        <div class="prosa-card">
            <div class="prosa"><?= \App\Services\Markdown::render($apr['resumo']) ?></div>
        </div>
    <?php endif; ?>

    <div class="actions">
        <button type="submit" class="btn btn--primary">Salvar alterações</button>
        <a href="/modulos/<?= (int) $apr['modulo_id'] ?>" class="btn btn--ghost">Voltar ao tema</a>
    </div>
</form>

<div class="card" style="margin-top:16px;">
    <h3>Resumo para os alunos</h3>
    <?php if (!$temTurma): ?>
        <p class="muted">Vincule a matéria deste tema a uma turma para poder publicar o resumo na Área do Aluno.</p>
    <?php elseif (trim((string) $apr['resumo']) === ''): ?>
        <p class="muted">Escreva e salve o resumo acima para poder publicá-lo.</p>
    <?php elseif ($material !== null): ?>
        <p>Publicado ✓ <span class="muted">— os alunos da turma veem em "Materiais".</span></p>
        <form method="post" action="/apresentacoes/<?= (int) $apr['id'] ?>/despublicar-resumo">
            <?= \App\Services\Csrf::campo() ?>
            <button type="submit" class="btn btn--danger btn--sm">Remover da Área do Aluno</button>
        </form>
    <?php else: ?>
        <p class="muted">Envia o resumo para os alunos da turma como material de apoio.</p>
        <form method="post" action="/apresentacoes/<?= (int) $apr['id'] ?>/publicar-resumo">
            <?= \App\Services\Csrf::campo() ?>
            <button type="submit" class="btn btn--primary">Publicar resumo para a turma</button>
        </form>
    <?php endif; ?>
</div>

<form method="post" action="/apresentacoes/<?= (int) $apr['id'] ?>/excluir"
      onsubmit="return confirm('Excluir esta apresentação?');" style="margin-top:12px;">
    <?= \App\Services\Csrf::campo() ?>
    <button type="submit" class="btn btn--danger">Excluir apresentação</button>
</form>

<template id="tpl-slide">
    <fieldset class="slide-edit">
        <div class="slide-edit__head">
            <strong class="slide-edit__num">Slide</strong>
            <span class="slide-edit__acoes">
                <button type="button" class="btn btn--ghost btn--sm js-slide-up" title="Subir">↑</button>
                <button type="button" class="btn btn--ghost btn--sm js-slide-down" title="Descer">↓</button>
                <button type="button" class="btn btn--danger btn--sm js-slide-del" title="Remover">✕</button>
            </span>
        </div>
        <label>Título do slide
            <input type="text" name="slide_titulo[]" value="" maxlength="160">
        </label>
        <label>Tópicos <span class="muted">(um por linha)</span>
            <textarea name="slide_topicos[]" rows="4"></textarea>
        </label>
        <label>Nota do apresentador <span class="muted">(o que você fala)</span>
            <textarea name="slide_nota[]" rows="2"></textarea>
        </label>
        <div class="slide-edit__midia">
            <strong class="slide-edit__midia-lbl">Mídia <span class="muted">(opcional)</span></strong>
            <div class="slide-edit__midia-grid">
                <div>
                    <label>Imagem — enviar arquivo
                        <input type="file" name="slide_imagem[]" accept="image/png,image/jpeg,image/webp,image/gif">
                    </label>
                    <input type="hidden" name="slide_imagem_atual[]" value="">
                    <input type="hidden" name="slide_remover_imagem[]" value="0">
                </div>
                <div>
                    <label>Vídeo — link
                        <input type="url" name="slide_video_url[]" value="" placeholder="Link do YouTube ou Vimeo">
                    </label>
                    <label>Vídeo — enviar arquivo <span class="muted">(mp4/webm)</span>
                        <input type="file" name="slide_video[]" accept="video/mp4,video/webm">
                    </label>
                    <input type="hidden" name="slide_video_arquivo_atual[]" value="">
                    <label class="slide-edit__rm"><input type="checkbox" class="js-mudo"> sem áudio</label>
                    <input type="hidden" name="slide_video_mudo[]" value="0">
                    <input type="hidden" name="slide_remover_video[]" value="0">
                </div>
            </div>
        </div>
    </fieldset>
</template>

<?php foreach ($slides as $s): ?>
    <?php if (($s['video_arquivo'] ?? '') !== ''): ?>
        <form id="<?= htmlspecialchars($slugCorte($s['video_arquivo'])) ?>" method="post"
              action="/apresentacoes/<?= $aprId ?>/cortar-video" hidden>
            <?= \App\Services\Csrf::campo() ?>
            <input type="hidden" name="arquivo" value="<?= htmlspecialchars(basename($s['video_arquivo'])) ?>">
            <input type="hidden" name="inicio" value="0">
            <input type="hidden" name="fim" value="0">
        </form>
    <?php endif; ?>
<?php endforeach; ?>

<script>
(function () {
    var lista = document.getElementById('slides');
    var tpl = document.getElementById('tpl-slide');
    if (!lista || !tpl) return;

    function renumerar() {
        lista.querySelectorAll('.slide-edit__num').forEach(function (el, i) {
            el.textContent = 'Slide ' + (i + 1);
        });
    }

    document.getElementById('js-add-slide').addEventListener('click', function () {
        lista.appendChild(tpl.content.cloneNode(true));
        renumerar();
    });

    lista.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        if (!btn) return;
        var slide = btn.closest('.slide-edit');
        if (!slide) return;

        if (btn.classList.contains('js-slide-del')) {
            slide.remove();
        } else if (btn.classList.contains('js-slide-up') && slide.previousElementSibling) {
            slide.parentNode.insertBefore(slide, slide.previousElementSibling);
        } else if (btn.classList.contains('js-slide-down') && slide.nextElementSibling) {
            slide.parentNode.insertBefore(slide.nextElementSibling, slide);
        }
        renumerar();
    });

    // Checkboxes que espelham num <input hidden> do slide.
    lista.addEventListener('change', function (e) {
        var chk = e.target;
        var slide = chk.closest && chk.closest('.slide-edit');
        if (!slide) return;
        var alvo = null;
        if (chk.classList.contains('js-rm-img')) alvo = 'slide_remover_imagem[]';
        else if (chk.classList.contains('js-rm-vid')) alvo = 'slide_remover_video[]';
        else if (chk.classList.contains('js-mudo')) alvo = 'slide_video_mudo[]';
        if (!alvo) return;
        var hidden = slide.querySelector('input[type="hidden"][name="' + alvo + '"]');
        if (hidden) hidden.value = chk.checked ? '1' : '0';
    });
})();

// Recorte de vídeo: prévia do trecho + sincroniza início/fim com o form de corte.
(function () {
    function mmss(s) {
        s = Math.max(0, Math.round(s));
        return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    }
    document.querySelectorAll('.trim-panel').forEach(function (panel) {
        var v = panel.querySelector('.js-trim-v');
        var a = panel.querySelector('.js-trim-a');
        var b = panel.querySelector('.js-trim-b');
        var ta = panel.querySelector('.js-trim-ta');
        var tb = panel.querySelector('.js-trim-tb');
        var btnPrev = panel.querySelector('.js-trim-preview');
        var btnCortar = panel.querySelector('button[form]');
        var form = btnCortar ? document.getElementById(btnCortar.getAttribute('form')) : null;
        if (!v || !a || !b) return;

        function sync() {
            if (parseFloat(a.value) >= parseFloat(b.value)) {
                a.value = Math.max(0, parseFloat(b.value) - 0.5);
            }
            ta.textContent = mmss(a.value);
            tb.textContent = mmss(b.value);
            if (form) {
                form.querySelector('input[name="inicio"]').value = a.value;
                form.querySelector('input[name="fim"]').value = b.value;
            }
        }

        v.addEventListener('loadedmetadata', function () {
            var d = v.duration && isFinite(v.duration) ? v.duration : 100;
            a.max = b.max = d.toFixed(1);
            a.value = 0;
            b.value = d.toFixed(1);
            sync();
        });
        a.addEventListener('input', sync);
        b.addEventListener('input', sync);

        var pararEm = null;
        v.addEventListener('timeupdate', function () {
            if (pararEm !== null && v.currentTime >= pararEm) { v.pause(); pararEm = null; }
        });
        if (btnPrev) btnPrev.addEventListener('click', function () {
            v.currentTime = parseFloat(a.value) || 0;
            pararEm = parseFloat(b.value) || v.duration;
            v.play();
        });

        if (btnCortar) btnCortar.addEventListener('click', function (e) {
            sync();
            if (!confirm('Cortar o vídeo para o trecho ' + mmss(a.value) + '–' + mmss(b.value) + '? O arquivo original é substituído.')) {
                e.preventDefault();
            }
        });
    });
})();
</script>
