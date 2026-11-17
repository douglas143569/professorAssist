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
            <a href="/creche/relatorios/banco" class="subnav__item">Banco de relatórios</a>
        </div>

        <p class="rel-privacidade">
            🔒 O relatório gerado é salvo no <a href="/creche/relatorios/banco"><strong>Banco de relatórios</strong></a>, visível só para você.
            Como contém dados de crianças, exclua quando não precisar mais.
        </p>
    </div>

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

        <button type="submit" class="btn btn--primary" data-loading="Elaborando o relatório…">✦ Gerar relatório</button>
    </form>
