<?php
    $v = fn(string $c) => htmlspecialchars($form[$c] ?? '');
    $grupos = [
        'Desenvolvimento' => ['social', 'emocional', 'linguagem', 'motor', 'cognitivo'],
        'Rotina e convivência' => ['alimentacao', 'higiene', 'participacao', 'colegas', 'adultos'],
        'Balanço do período' => ['dificuldades', 'avancos'],
    ];
    $dicas = [
        'dificuldades' => 'Descreva como situações a serem estimuladas, sem rótulos ou diagnósticos.',
        'avancos' => 'Conquistas e evolução observadas ao longo do período.',
    ];
?>
    <div class="no-print">
        <div class="page-header">
            <div>
                <h1>Creche · Educação Infantil</h1>
                <p class="muted">Relatório individual descritivo, pronto para entregar à família.</p>
            </div>
        </div>

        <div class="subnav">
            <a href="/creche" class="subnav__item">Atividades</a>
            <a href="/creche/cronograma" class="subnav__item">Cronograma</a>
            <a href="/creche/pacotes" class="subnav__item">Pacotes</a>
            <a href="/creche/relatorios" class="subnav__item is-active">Relatórios dos alunos</a>
        </div>

        <p class="rel-privacidade">
            🔒 Por privacidade, o relatório <strong>não é guardado</strong> no sistema: ele aparece só nesta tela.
            Copie, imprima ou salve em PDF antes de sair da página.
        </p>
    </div>

    <?php if ($relatorio !== null): ?>
        <div class="card rel-folha-card">
            <div class="no-print rel-toolbar">
                <h3 style="margin:0;">Relatório de <?= $v('nome') ?></h3>
                <div class="actions">
                    <button type="button" class="btn btn--ghost btn--sm" id="rel-copiar">Copiar texto</button>
                    <button type="button" class="btn btn--primary btn--sm" onclick="window.print()">🖨 Imprimir / PDF</button>
                </div>
            </div>
            <p class="no-print muted" style="font-size:.82rem;margin:0 0 12px;">Você pode editar o texto abaixo antes de copiar ou imprimir.</p>
            <article class="rel-folha" id="rel-texto" contenteditable="true" spellcheck="true">
                <h2 class="rel-folha__titulo">Relatório Individual Descritivo</h2>
                <dl class="rel-folha__dados">
                    <div><dt>Aluno(a)</dt><dd><?= $v('nome') ?></dd></div>
                    <div><dt>Idade</dt><dd><?= $v('idade') ?></dd></div>
                    <div><dt>Turma</dt><dd><?= $v('turma') ?></dd></div>
                    <div><dt>Período avaliado</dt><dd><?= $v('periodo') ?></dd></div>
                </dl>
                <?php foreach (preg_split('/\n\s*\n/', $relatorio) as $par): ?>
                    <p><?= nl2br(htmlspecialchars(trim($par))) ?></p>
                <?php endforeach; ?>
                <p class="rel-folha__assina">Professor(a): <?= $v('professor') ?></p>
            </article>
        </div>
    <?php endif; ?>

    <form method="post" action="/creche/relatorios/gerar" class="form js-ai no-print">
        <?= \App\Services\Csrf::campo() ?>
        <div class="card">
            <h3>Dados do aluno</h3>
            <div class="form--inline">
                <label style="flex:2 1 260px;">Nome do aluno
                    <input type="text" name="nome" value="<?= $v('nome') ?>" required maxlength="120">
                </label>
                <label style="flex:1 1 140px;">Idade
                    <input type="text" name="idade" value="<?= $v('idade') ?>" placeholder="Ex: 2 anos e 4 meses" required maxlength="60">
                </label>
                <label style="flex:1 1 160px;">Turma
                    <input type="text" name="turma" value="<?= $v('turma') ?>" placeholder="Ex: Maternal I" required maxlength="80">
                </label>
            </div>
            <div class="form--inline">
                <label style="flex:1 1 220px;">Período avaliado
                    <input type="text" name="periodo" value="<?= $v('periodo') ?>" placeholder="Ex: 1º trimestre de 2026" required maxlength="80">
                </label>
                <label style="flex:1 1 220px;">Professor(a)
                    <input type="text" name="professor" value="<?= ($form['professor'] ?? '') !== '' ? $v('professor') : htmlspecialchars($nomeProfessor) ?>" maxlength="120">
                </label>
            </div>
        </div>

        <?php foreach ($grupos as $titulo => $campos): ?>
            <div class="card">
                <h3><?= htmlspecialchars($titulo) ?></h3>
                <p class="muted" style="margin:-4px 0 10px;font-size:.82rem;">Preencha o que observou; o que ficar em branco não será comentado no relatório.</p>
                <div class="rel-grid">
                    <?php foreach ($campos as $c): ?>
                        <label><?= htmlspecialchars($observacoes[$c]) ?>
                            <?php if (isset($dicas[$c])): ?><span class="muted"> — <?= htmlspecialchars($dicas[$c]) ?></span><?php endif; ?>
                            <textarea name="<?= $c ?>" rows="3" maxlength="2000"><?= $v($c) ?></textarea>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn--primary" data-loading="Elaborando o relatório…">✦ <?= $relatorio !== null ? 'Gerar novamente' : 'Gerar relatório' ?></button>
    </form>

    <script>
    document.getElementById('rel-copiar')?.addEventListener('click', function () {
        var t = document.getElementById('rel-texto').innerText;
        navigator.clipboard.writeText(t).then(function () { this.textContent = 'Copiado ✓'; }.bind(this));
    });
    </script>
