<?php
    /** @var array $apr  @var array[] $slides */
    $aprId = (int) $apr['id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($apr['titulo']) ?> — apresentação</title>
    <style>
        :root { --bg:#0f172a; --fg:#f1f5f9; --muted:#94a3b8; --accent:#34d399; --card:#1e293b; }
        * { box-sizing: border-box; margin: 0; }
        html, body { height: 100%; }
        body {
            background: var(--bg); color: var(--fg); overflow: hidden;
            font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
        }

        .barra { position: fixed; top: 0; left: 0; height: 4px; background: var(--accent);
            width: 0; transition: width .35s ease; z-index: 10; }

        .palco { position: absolute; inset: 0; display: grid; place-items: center; padding: 6vh 8vw 12vh;
            cursor: pointer; }

        .slide {
            position: absolute; inset: 6vh 8vw 12vh; display: flex; flex-direction: column;
            justify-content: center; gap: 2.2rem; opacity: 0; transform: translateX(40px);
            transition: opacity .4s ease, transform .4s ease; pointer-events: none;
        }
        .slide.is-active { opacity: 1; transform: none; pointer-events: auto; }
        .slide.is-prev { transform: translateX(-40px); }

        .slide.has-midia { flex-direction: row; gap: 4vw; align-items: center; }
        .slide__texto { display: flex; flex-direction: column; gap: 2rem; flex: 1 1 46%; min-width: 0; }
        .slide.has-midia .slide__texto { justify-content: center; }
        .slide__midia { flex: 1 1 54%; display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
        .slide__midia img, .slide__midia iframe, .slide__midia video {
            width: 100%; max-height: 72vh; border: 0; border-radius: 12px; background: #000; display: block;
        }
        .slide__midia iframe { aspect-ratio: 16 / 9; height: auto; }

        .slide h2 { font-size: clamp(1.6rem, 4vw, 3rem); line-height: 1.15; letter-spacing: -.02em; }
        .slide ul { list-style: none; display: flex; flex-direction: column; gap: 1rem; max-width: 60ch; }
        .slide li {
            font-size: clamp(1rem, 2.1vw, 1.55rem); line-height: 1.4; padding-left: 1.6rem; position: relative;
            opacity: 0; transform: translateY(10px); transition: opacity .4s ease, transform .4s ease;
        }
        .slide li::before { content: "▸"; position: absolute; left: 0; color: var(--accent); }
        .slide.is-active li { opacity: 1; transform: none; transition-delay: calc(var(--i) * .09s + .15s); }

        .rodape {
            position: fixed; left: 0; right: 0; bottom: 0; padding: .9rem 8vw;
            display: flex; align-items: center; gap: 1rem; font-size: .85rem; color: var(--muted);
            background: linear-gradient(to top, rgba(15,23,42,.9), transparent);
        }
        .rodape .contador { font-variant-numeric: tabular-nums; }
        .rodape .dica { flex: 1; text-align: center; }
        .rodape a, .rodape button {
            color: var(--fg); background: var(--card); border: 1px solid #334155; border-radius: 8px;
            padding: .4rem .8rem; font: inherit; font-size: .8rem; cursor: pointer; text-decoration: none;
        }
        .rodape button:hover { background: #2a3a4f; }
        .rodape button:disabled { opacity: .35; cursor: default; }

        .notas {
            position: fixed; left: 0; right: 0; bottom: 3.4rem; padding: 1rem 8vw 1.4rem;
            background: var(--card); border-top: 2px solid var(--accent); color: var(--fg);
            font-size: 1rem; line-height: 1.5; transform: translateY(120%); transition: transform .3s ease;
            max-height: 40vh; overflow: auto;
        }
        .notas.aberta { transform: none; }
        .notas strong { color: var(--muted); display: block; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .3rem; }

        .vazio { text-align: center; color: var(--muted); }

        @media (max-width: 820px) {
            .slide.has-midia { flex-direction: column; gap: 1.5rem; justify-content: center; }
            .slide__texto, .slide__midia { flex: 1 1 auto; }
            .slide__midia img, .slide__midia video { max-height: 40vh; }
        }
        @media (prefers-reduced-motion: reduce) {
            .slide, .slide li, .barra, .notas { transition: none !important; }
            .slide li { opacity: 1; transform: none; }
        }
    </style>
</head>
<body>
    <div class="barra" id="barra"></div>

    <div class="palco" id="palco">
        <?php if (empty($slides)): ?>
            <p class="vazio">Esta apresentação ainda não tem slides. Volte ao editor e adicione alguns.</p>
        <?php else: ?>
            <?php foreach ($slides as $i => $s): ?>
                <?php
                    $img = (string) ($s['imagem'] ?? '');
                    $vidArq = (string) ($s['video_arquivo'] ?? '');
                    $mudo = !empty($s['video_mudo']);
                    $embed = \App\Services\Video::embed((string) ($s['video_url'] ?? ''), $mudo);
                    $temMidia = $img !== '' || $vidArq !== '' || $embed !== null;
                ?>
                <section class="slide<?= $temMidia ? ' has-midia' : '' ?>" data-i="<?= (int) $i ?>">
                    <div class="slide__texto">
                        <h2><?= htmlspecialchars($s['titulo'] ?? ('Slide ' . ($i + 1))) ?></h2>
                        <?php if (!empty($s['topicos'])): ?>
                            <ul>
                                <?php foreach ($s['topicos'] as $k => $topico): ?>
                                    <li style="--i:<?= (int) $k ?>"><?= htmlspecialchars((string) $topico) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <?php if ($temMidia): ?>
                        <div class="slide__midia">
                            <?php if ($img !== ''): ?>
                                <img src="/apresentacoes/<?= $aprId ?>/midia/<?= rawurlencode(basename($img)) ?>" alt="">
                            <?php endif; ?>
                            <?php if ($embed !== null): ?>
                                <iframe data-src="<?= htmlspecialchars($embed['src']) ?>" allow="fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                            <?php endif; ?>
                            <?php if ($vidArq !== ''): ?>
                                <video controls preload="none"<?= $mudo ? ' muted' : '' ?> src="/apresentacoes/<?= $aprId ?>/midia/<?= rawurlencode(basename($vidArq)) ?>"></video>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="notas" id="notas"><strong>Nota do apresentador</strong><span id="nota-texto"></span></div>

    <div class="rodape">
        <button type="button" id="btn-anterior" title="Slide anterior (←)">◀ Anterior</button>
        <span class="contador"><span id="atual">1</span> / <?= max(1, count($slides)) ?></span>
        <button type="button" id="btn-proximo" title="Próximo slide (→)">Próximo ▶</button>
        <span class="dica">← → navega · clique avança · <kbd>S</kbd> notas · <kbd>F</kbd> tela cheia · <kbd>Esc</kbd> sai</span>
        <button type="button" id="btn-notas">Notas</button>
        <a href="/apresentacoes/<?= $aprId ?>">Sair</a>
    </div>

    <script>
        (function () {
            var slides = Array.prototype.slice.call(document.querySelectorAll('.slide'));
            var total = slides.length;
            var barra = document.getElementById('barra');
            var atualEl = document.getElementById('atual');
            var notas = document.getElementById('notas');
            var notaTexto = document.getElementById('nota-texto');
            var voltarUrl = '/apresentacoes/<?= $aprId ?>';
            var notasSlide = <?= json_encode(array_map(static fn ($s) => (string) ($s['nota'] ?? ''), $slides)) ?>;
            var i = 0;

            if (!total) return;

            // Para clique num player nao pular de slide.
            document.querySelectorAll('.slide__midia').forEach(function (m) {
                m.addEventListener('click', function (e) { e.stopPropagation(); });
            });

            function pararMidia(slide) {
                slide.querySelectorAll('iframe[data-src]').forEach(function (f) { f.removeAttribute('src'); });
                slide.querySelectorAll('video').forEach(function (v) { try { v.pause(); } catch (e) {} });
            }
            function ligarMidia(slide) {
                slide.querySelectorAll('iframe[data-src]').forEach(function (f) {
                    if (!f.getAttribute('src')) f.setAttribute('src', f.getAttribute('data-src'));
                });
            }

            function mostrar(n) {
                var anterior = i;
                i = Math.max(0, Math.min(n, total - 1));
                slides.forEach(function (el, idx) {
                    var ativo = idx === i;
                    el.classList.toggle('is-active', ativo);
                    el.classList.toggle('is-prev', idx < i);
                    if (!ativo && idx === anterior) pararMidia(el);
                });
                ligarMidia(slides[i]);
                barra.style.width = ((i + 1) / total * 100) + '%';
                atualEl.textContent = i + 1;
                notaTexto.textContent = notasSlide[i] || '—';
                btnAnterior.disabled = i === 0;
                btnProximo.disabled = i === total - 1;
            }

            function proximo() { if (i < total - 1) mostrar(i + 1); }
            function anterior() { if (i > 0) mostrar(i - 1); }

            var btnAnterior = document.getElementById('btn-anterior');
            var btnProximo = document.getElementById('btn-proximo');
            btnAnterior.addEventListener('click', function (e) { e.stopPropagation(); anterior(); });
            btnProximo.addEventListener('click', function (e) { e.stopPropagation(); proximo(); });

            document.getElementById('palco').addEventListener('click', proximo);
            document.getElementById('btn-notas').addEventListener('click', function (e) {
                e.stopPropagation(); notas.classList.toggle('aberta');
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown') { e.preventDefault(); proximo(); }
                else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); anterior(); }
                else if (e.key === 's' || e.key === 'S') { notas.classList.toggle('aberta'); }
                else if (e.key === 'f' || e.key === 'F') {
                    if (document.fullscreenElement) document.exitFullscreen();
                    else document.documentElement.requestFullscreen && document.documentElement.requestFullscreen();
                }
                else if (e.key === 'Escape') {
                    if (document.fullscreenElement) return;
                    window.location.href = voltarUrl;
                }
            });

            mostrar(0);
        })();
    </script>
</body>
</html>
