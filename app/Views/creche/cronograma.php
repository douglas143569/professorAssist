<?php
    $labels = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta'];
    $hoje = date('Y-m-d');
    $prevInicio = date('Y-m-d', strtotime('-7 days', $seg));
    $nextInicio = date('Y-m-d', strtotime('+7 days', $seg));
    $fmt = fn(int $ts) => date('d/m', $ts);
?>
    <div class="page-header">
        <div>
            <h1>Creche · Educação Infantil</h1>
            <p class="muted">Cronograma semanal de atividades lúdicas.</p>
        </div>
    </div>

    <div class="subnav">
        <a href="/creche" class="subnav__item">Atividades</a>
        <a href="/creche/cronograma" class="subnav__item is-active">Cronograma</a>
        <a href="/creche/pacotes" class="subnav__item">Pacotes</a>
    </div>

    <div class="card">
        <h3>Criar cronograma da semana com IA</h3>
        <form method="post" action="/creche/cronograma/criar" class="form js-ai">
            <?= \App\Services\Csrf::campo() ?>
            <input type="hidden" name="inicio" value="<?= htmlspecialchars($inicio) ?>">
            <div class="form--inline">
                <label style="flex:1 1 220px;">Faixa etária
                    <select name="faixa_etaria" required>
                        <?php foreach ($faixas as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label style="flex:1 1 240px;">Campo de experiência <span class="muted">(opcional)</span>
                    <select name="campo_experiencia">
                        <option value="">—</option>
                        <?php foreach ($campos as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Tema / projeto da semana <span class="muted">(opcional)</span>
                <input type="text" name="tema" placeholder="Ex: identidade, natureza, festa junina…">
            </label>
            <button type="submit" class="btn btn--primary" data-loading="Criando a semana…">✦ Criar</button>
        </form>
    </div>

    <div class="page-header" style="margin-top:8px;">
        <div class="cal__nav">
            <a class="btn btn--ghost" href="/creche/cronograma?inicio=<?= $prevInicio ?>" aria-label="Semana anterior">‹</a>
            <strong style="min-width:190px;text-align:center;">Semana de <?= $fmt($seg) ?> a <?= $fmt(strtotime('+4 days', $seg)) ?></strong>
            <a class="btn btn--ghost" href="/creche/cronograma?inicio=<?= $nextInicio ?>" aria-label="Próxima semana">›</a>
            <a class="btn btn--ghost" href="/creche/cronograma">Esta semana</a>
        </div>
        <?php if ($temItens): ?>
            <form method="post" action="/creche/cronograma/limpar" onsubmit="return confirm('Limpar todas as atividades desta semana?');">
            <?= \App\Services\Csrf::campo() ?>
                <input type="hidden" name="inicio" value="<?= htmlspecialchars($inicio) ?>">
                <button type="submit" class="btn btn--danger btn--sm">Limpar semana</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="week-grid">
        <?php for ($i = 0; $i < 5; $i++):
            $ts = strtotime("+{$i} days", $seg);
            $dataDia = date('Y-m-d', $ts);
            $evs = $porDia[$dataDia] ?? [];
            $isHoje = ($dataDia === $hoje);
        ?>
            <div class="week-col <?= $isHoje ? 'is-today' : '' ?>">
                <div class="week-col__head">
                    <strong><?= $labels[$i] ?></strong>
                    <span class="muted"><?= $fmt($ts) ?></span>
                </div>
                <div class="week-col__body">
                    <?php if (empty($evs)): ?>
                        <p class="week-empty">—</p>
                    <?php else: ?>
                        <?php foreach ($evs as $e): ?>
                            <a class="act-card" href="/creche/cronograma/<?= (int) $e['id'] ?>">
                                <strong><?= htmlspecialchars($e['titulo']) ?></strong>
                                <?php if (!empty($e['duracao'])): ?>
                                    <span class="tag tag--origem"><?= htmlspecialchars($e['duracao']) ?></span>
                                <?php endif; ?>
                                <span class="act-card__edit">Editar ✎</span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>
