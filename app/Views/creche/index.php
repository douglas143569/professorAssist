    <div class="page-header">
        <div>
            <h1>Creche · Educação Infantil</h1>
            <p class="muted">Atividades lúdicas por faixa etária e campo de experiência (BNCC).</p>
        </div>
    </div>

    <div class="subnav">
        <a href="/creche" class="subnav__item is-active">Atividades</a>
        <a href="/creche/cronograma" class="subnav__item">Cronograma</a>
        <a href="/creche/pacotes" class="subnav__item">Pacotes</a>
    </div>

    <div class="card">
        <h3>Sugerir atividades lúdicas com IA</h3>
        <form method="post" action="/creche/atividades/gerar" class="form js-ai">
            <div class="form--inline">
                <label style="flex:1 1 220px;">Faixa etária
                    <select name="faixa_etaria" required>
                        <?php foreach ($faixas as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>" <?= $filtroFaixa === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label style="flex:0 1 110px;">Quantidade
                    <input type="number" name="quantidade" value="4" min="1" max="10">
                </label>
            </div>
            <label>Campo de experiência <span class="muted">(opcional)</span>
                <select name="campo_experiencia">
                    <option value="">—</option>
                    <?php foreach ($campos as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Tema / projeto <span class="muted">(opcional)</span>
                <input type="text" name="tema" placeholder="Ex: animais, cores, festa junina…">
            </label>
            <button type="submit" class="btn btn--primary" data-loading="Sugerindo…">✦ Sugerir atividades</button>
        </form>
    </div>

    <div class="page-header" style="margin-top:8px;">
        <h2>Atividades</h2>
        <form method="get" action="/creche" class="form--inline">
            <label style="flex:0 1 220px;">Filtrar por faixa
                <select name="faixa" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php foreach ($faixas as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>" <?= $filtroFaixa === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
    </div>

    <?php if (empty($atividades)): ?>
        <div class="card card--empty">
            <p>Nenhuma atividade ainda. Use o gerador acima para criar sugestões por faixa etária.</p>
        </div>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($atividades as $a): ?>
                <li>
                    <a href="/creche/atividades/<?= (int) $a['id'] ?>" class="list__main"><?= htmlspecialchars($a['titulo']) ?></a>
                    <span class="tag"><?= htmlspecialchars($a['faixa_etaria']) ?></span>
                    <?php if (!empty($a['duracao'])): ?>
                        <span class="tag tag--origem"><?= htmlspecialchars($a['duracao']) ?></span>
                    <?php endif; ?>
                    <span class="badge badge--<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span>
                    <span class="tag tag--origem"><?= $a['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="notice" style="margin-top:24px;">
        <strong>Em breve nesta aba:</strong> acompanhamento das crianças (checklists de desenvolvimento, relatórios e alertas).
        Como envolve dados sensíveis de menores, será liberado após o plano de proteção de dados (LGPD/ECA).
    </div>
