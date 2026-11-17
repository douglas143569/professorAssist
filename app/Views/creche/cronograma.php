<?php
    $hoje = date('Y-m-d');
    $fmt = fn(string $d) => date('d/m', strtotime($d));
    $e = fn($v) => nl2br(htmlspecialchars((string) $v));
?>
    <div class="page-header">
        <div>
            <h1>Creche · Educação Infantil</h1>
            <p class="muted">Cronograma mensal de atividades, organizado por semana e alinhado à BNCC.</p>
        </div>
    </div>

    <div class="subnav">
        <a href="/creche" class="subnav__item">Atividades</a>
        <a href="/creche/cronograma" class="subnav__item is-active">Cronograma</a>
        <a href="/creche/pacotes" class="subnav__item">Pacotes</a>
        <a href="/creche/relatorios" class="subnav__item">Relatórios dos alunos</a>
        <a href="/creche/relatorios/banco" class="subnav__item">Banco de relatórios</a>
    </div>

    <div class="card">
        <h3>Criar cronograma mensal com IA</h3>
        <form method="post" action="/creche/cronograma/criar" class="form js-ai">
            <?= \App\Services\Csrf::campo() ?>
            <div class="form--inline">
                <label style="flex:1 1 220px;">Faixa etária
                    <select name="faixa_etaria" required>
                        <?php foreach ($faixas as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label style="flex:0 1 180px;">Mês de referência
                    <input type="month" name="mes" value="<?= htmlspecialchars($mes) ?>" required>
                </label>
                <label style="flex:0 1 150px;">Nº de crianças
                    <input type="number" name="num_criancas" min="1" max="60" placeholder="Ex: 15">
                </label>
                <label style="flex:0 1 140px;">Período
                    <select name="periodo">
                        <option value="Integral" selected>Integral</option>
                        <option value="Parcial">Parcial</option>
                    </select>
                </label>
            </div>
            <label>Tema do mês <span class="muted">(opcional)</span>
                <input type="text" name="tema" placeholder="Ex: Identidade e família, Natureza ao nosso redor, Festa junina…">
            </label>
            <?php if ($temItens): ?>
                <p class="muted" style="font-size:.82rem;margin:0;">Já existe um cronograma neste mês: ao criar de novo, ele será substituído.</p>
            <?php endif; ?>
            <button type="submit" class="btn btn--primary" data-loading="Criando o mês… isso pode levar alguns instantes">✦ Criar cronograma do mês</button>
        </form>
    </div>

    <div class="page-header" style="margin-top:8px;">
        <div class="cal__nav">
            <a class="btn btn--ghost" href="/creche/cronograma?mes=<?= $mesAnterior ?>" aria-label="Mês anterior">‹</a>
            <strong style="min-width:190px;text-align:center;"><?= htmlspecialchars($mesNome) ?></strong>
            <a class="btn btn--ghost" href="/creche/cronograma?mes=<?= $mesSeguinte ?>" aria-label="Próximo mês">›</a>
            <a class="btn btn--ghost" href="/creche/cronograma">Este mês</a>
        </div>
        <?php if ($temItens): ?>
            <form method="post" action="/creche/cronograma/limpar" onsubmit="return confirm('Limpar o cronograma de <?= htmlspecialchars($mesNome) ?>?');">
                <?= \App\Services\Csrf::campo() ?>
                <input type="hidden" name="mes" value="<?= htmlspecialchars($mes) ?>">
                <button type="submit" class="btn btn--danger btn--sm">Limpar mês</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($resumo): ?>
        <p class="muted" style="margin:0 0 12px;">
            <span class="tag"><?= htmlspecialchars($resumo['faixa_etaria']) ?></span>
            <span class="tag tag--origem"><?= htmlspecialchars($resumo['periodo']) ?></span>
            <?php if (!empty($resumo['num_criancas'])): ?><span class="tag tag--origem"><?= (int) $resumo['num_criancas'] ?> crianças</span><?php endif; ?>
            <?php if (!empty($resumo['tema'])): ?><span class="tag tag--bncc">Tema: <?= htmlspecialchars($resumo['tema']) ?></span><?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (!$temItens): ?>
        <div class="card card--empty">
            <p>Nenhum cronograma em <?= htmlspecialchars($mesNome) ?>. Preencha os dados acima para gerar o mês inteiro.</p>
        </div>
    <?php else: foreach ($semanas as $n => $dias): ?>
        <section class="cron-semana">
            <h2 class="cron-semana__titulo">Semana <?= (int) $n ?>
                <span class="muted"><?= $fmt(array_key_first($dias)) ?> a <?= $fmt(array_key_last($dias)) ?></span>
            </h2>
            <div class="cron-tabela-wrap">
                <table class="cron-tabela">
                    <thead>
                        <tr><th>Dia</th><th>Atividade</th><th>Objetivos</th><th>Campos da BNCC</th><th>Recursos</th><th>Avaliação</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dias as $data => $evs):
                        $rotulo = $nomesDias[(int) date('N', strtotime($data)) - 1] ?? '';
                        if (empty($evs)): ?>
                            <tr class="<?= $data === $hoje ? 'is-today' : '' ?>">
                                <td class="cron-dia"><strong><?= htmlspecialchars($rotulo) ?></strong><span><?= $fmt($data) ?></span></td>
                                <td colspan="5" class="muted">—</td>
                            </tr>
                        <?php else: foreach ($evs as $ev): ?>
                            <tr class="<?= $data === $hoje ? 'is-today' : '' ?>">
                                <td class="cron-dia"><strong><?= htmlspecialchars($rotulo) ?></strong><span><?= $fmt($data) ?></span></td>
                                <td>
                                    <a href="/creche/cronograma/<?= (int) $ev['id'] ?>"><strong><?= htmlspecialchars($ev['titulo']) ?></strong></a>
                                    <?php if (!empty($ev['tipo_atividade'])): ?><br><span class="tag"><?= htmlspecialchars($ev['tipo_atividade']) ?></span><?php endif; ?>
                                    <?php if (!empty($ev['duracao'])): ?><span class="tag tag--origem"><?= htmlspecialchars($ev['duracao']) ?></span><?php endif; ?>
                                </td>
                                <td><?= $e($ev['objetivos'] ?? '') ?></td>
                                <td><?= htmlspecialchars($ev['campo_experiencia'] ?? '') ?></td>
                                <td><?= $e($ev['materiais'] ?? '') ?></td>
                                <td><?= $e($ev['avaliacao'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; endif;
                    endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; endif; ?>

    <?php if ($resumo): ?>
        <div class="cron-fechamento">
            <?php foreach ([
                'resumo_objetivos' => 'Resumo dos objetivos do mês',
                'competencias' => 'Competências desenvolvidas',
                'adaptacoes' => 'Adaptações para crianças com necessidades específicas',
                'observacoes' => 'Observações pedagógicas',
            ] as $campo => $titulo): if (empty($resumo[$campo])) { continue; } ?>
                <div class="card">
                    <h3><?= htmlspecialchars($titulo) ?></h3>
                    <p style="margin:0;"><?= $e($resumo[$campo]) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
