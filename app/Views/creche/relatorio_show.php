<?php $v = fn(string $c) => htmlspecialchars((string) $r[$c]); ?>
    <div class="no-print">
        <p class="breadcrumb"><a href="/creche/relatorios/banco">Banco de relatórios</a> / <?= $v('aluno_nome') ?></p>
        <div class="page-header">
            <div>
                <h1>Relatório de <?= $v('aluno_nome') ?></h1>
                <p class="muted">Criado em <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>. Edite o texto abaixo e salve.</p>
            </div>
            <div class="actions">
                <a href="/creche/relatorios/banco" class="btn btn--ghost">‹ Voltar ao banco</a>
                <button type="button" class="btn btn--ghost" id="rel-copiar">Copiar texto</button>
                <button type="button" class="btn btn--primary" onclick="window.print()">🖨 Imprimir / PDF</button>
            </div>
        </div>
    </div>

    <form method="post" action="/creche/relatorios/<?= (int) $r['id'] ?>" id="rel-form">
        <?= \App\Services\Csrf::campo() ?>
        <textarea name="texto" id="rel-texto-campo" hidden></textarea>
        <div class="card rel-folha-card">
            <article class="rel-folha">
                <h2 class="rel-folha__titulo">Relatório Individual Descritivo</h2>
                <dl class="rel-folha__dados">
                    <div><dt>Aluno(a)</dt><dd><?= $v('aluno_nome') ?></dd></div>
                    <div><dt>Idade</dt><dd><?= $v('idade') ?></dd></div>
                    <div><dt>Turma</dt><dd><?= $v('turma') ?></dd></div>
                    <div><dt>Período avaliado</dt><dd><?= $v('periodo') ?></dd></div>
                </dl>
                <div id="rel-corpo" class="rel-corpo" contenteditable="true" spellcheck="true"><?php
                    foreach (preg_split('/\n\s*\n/', (string) $r['texto']) as $par): ?><p><?= nl2br(htmlspecialchars(trim($par))) ?></p><?php endforeach; ?></div>
                <p class="rel-folha__assina">Professor(a): <?= $v('professor') ?></p>
            </article>
        </div>
        <div class="actions no-print" style="margin-top:14px;">
            <button type="submit" class="btn btn--primary">Salvar alterações</button>
        </div>
    </form>

    <form method="post" action="/creche/relatorios/<?= (int) $r['id'] ?>/excluir" class="no-print"
          onsubmit="return confirm('Excluir este relatório do banco? Não é possível desfazer.');" style="margin-top:12px;">
        <?= \App\Services\Csrf::campo() ?>
        <button type="submit" class="btn btn--danger">Excluir relatório</button>
    </form>

    <script>
    (function () {
        var corpo = document.getElementById('rel-corpo');
        function texto() {
            return Array.from(corpo.querySelectorAll('p, div')).map(function (p) { return p.innerText.trim(); })
                .filter(Boolean).join('\n\n') || corpo.innerText.trim();
        }
        document.getElementById('rel-form').addEventListener('submit', function () {
            document.getElementById('rel-texto-campo').value = texto();
        });
        var btn = document.getElementById('rel-copiar');
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(texto()).then(function () { btn.textContent = 'Copiado ✓'; });
        });
    })();
    </script>
