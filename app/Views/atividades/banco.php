    <div class="page-header">
        <div>
            <h1>Banco de atividades</h1>
            <p class="muted">Todas as atividades geradas no sistema, salvas num só lugar.</p>
        </div>
    </div>

    <form method="get" action="/banco-atividades" class="form--inline bank-filtros">
        <?php if ($moduloId > 0): ?><input type="hidden" name="modulo" value="<?= (int) $moduloId ?>"><?php endif; ?>
        <label style="flex:2 1 260px;">Buscar
            <input type="search" name="q" value="<?= htmlspecialchars($busca) ?>" placeholder="Título, tema, matéria ou faixa etária…">
        </label>
        <label style="flex:1 1 150px;">Origem
            <select name="tipo" onchange="this.form.submit()">
                <option value="">Todas</option>
                <option value="escola" <?= $tipo === 'escola' ? 'selected' : '' ?>>Escola (por tema)</option>
                <option value="creche" <?= $tipo === 'creche' ? 'selected' : '' ?>>Creche</option>
            </select>
        </label>
        <label style="flex:1 1 150px;">Status
            <select name="status" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="rascunho" <?= $status === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                <option value="aprovado" <?= $status === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
            </select>
        </label>
        <button type="submit" class="btn btn--ghost">Filtrar</button>
        <?php if ($moduloId > 0): ?><a href="/banco-atividades" class="btn btn--ghost">Ver todas as atividades</a><?php endif; ?>
    </form>

    <p class="muted" style="margin:12px 0;"><?= count($itens) ?> atividade(s) encontrada(s)<?= $moduloId > 0 ? ' neste tema' : '' ?>.</p>

    <?php if (empty($itens)): ?>
        <div class="card card--empty"><p>Nenhuma atividade encontrada. Gere atividades em um tema (“Atividades sugeridas”) ou na Creche e elas aparecerão aqui.</p></div>
    <?php else: ?>
        <div class="gl-grid">
            <?php foreach ($itens as $n => $it): ?>
                <a class="gl-card" href="<?= htmlspecialchars($it['url']) ?>" style="--i:<?= $n % 12 ?>">
                    <span class="gl-tile" aria-hidden="true"><svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/><circle cx="12" cy="12" r="2.5"/></svg></span>
                    <span class="gl-waves" aria-hidden="true"><svg viewBox="0 0 400 80" preserveAspectRatio="none"><path class="gl-w1" d="M0 40Q50 0 100 40T200 40T300 40T400 40V80H0Z"/></svg></span>
                    <strong class="gl-pill"><?= htmlspecialchars($it['titulo']) ?></strong>
                    <small class="gl-ctx"><?= htmlspecialchars($it['contexto']) ?></small>
                    <span class="gl-meta">
                        <span class="badge badge--<?= htmlspecialchars($it['status']) ?>"><?= htmlspecialchars($it['status']) ?></span>
                        <span class="tag tag--origem"><?= $it['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                        <?php if (!empty($it['formato'])): ?><span class="tag"><?= htmlspecialchars($it['formato']) ?></span><?php endif; ?>
                        <?php if (!empty($it['duracao'])): ?><span class="tag tag--origem"><?= htmlspecialchars($it['duracao']) ?></span><?php endif; ?>
                    </span>
                    <span class="gl-open">Abrir <span aria-hidden="true">↗</span></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
