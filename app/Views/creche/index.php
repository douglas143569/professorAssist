    <?php
        $rascunhos = count(array_filter($atividades ?? [], static fn ($a) => ($a['status'] ?? '') === 'rascunho'));
        $aprovadas = count(array_filter($atividades ?? [], static fn ($a) => ($a['status'] ?? '') === 'aprovado'));
    ?>
    <div class="creche-page">
    <header class="page-header creche-hero">
        <span class="creche-hero__icon" aria-hidden="true">✿</span>
        <div>
            <h1>Creche · Educação Infantil</h1>
            <p class="muted">Atividades lúdicas por faixa etária e campo de experiência (BNCC).</p>
        </div>
        <a class="btn btn--ghost creche-hero__link" href="/creche/cronograma">Ver cronograma <span>→</span></a>
    </header>

    <div class="subnav">
        <a href="/creche" class="subnav__item is-active">Atividades</a>
        <a href="/creche/cronograma" class="subnav__item">Cronograma</a>
        <a href="/creche/pacotes" class="subnav__item">Pacotes</a>
        <a href="/creche/relatorios" class="subnav__item">Relatórios dos alunos</a>
        <a href="/creche/relatorios/banco" class="subnav__item">Banco de relatórios</a>
    </div>

    <section class="creche-stats" aria-label="Resumo das atividades">
        <div class="creche-stat"><span class="creche-stat__icon is-mint">✿</span><span><strong><?= count($atividades ?? []) ?></strong><small>Atividades criadas</small></span></div>
        <div class="creche-stat"><span class="creche-stat__icon is-amber">◷</span><span><strong><?= $rascunhos ?></strong><small>Aguardando revisão</small></span></div>
        <div class="creche-stat"><span class="creche-stat__icon is-blue">✓</span><span><strong><?= $aprovadas ?></strong><small>Aprovadas</small></span></div>
    </section>

    <section class="card creche-generator">
        <div class="creche-section-title"><span class="creche-section-title__icon">✦</span><div><small>ASSISTENTE DE PLANEJAMENTO</small><h2>Sugerir atividades lúdicas com IA</h2><p>Personalize as ideias para a idade e os interesses da sua turma.</p></div></div>
        <form method="post" action="/creche/atividades/gerar" class="form js-ai">
            <?= \App\Services\Csrf::campo() ?>
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
    </section>

    <div class="page-header creche-list-header">
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
        <?php
            $icones = [
                '<path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/><circle cx="12" cy="12" r="2.5"/>',
                '<path d="M9 18V6l10-2v12"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="16" r="2"/>',
                '<path d="M12 21s-8-5.2-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.8-8 11-8 11Z"/>',
                '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z"/>',
                '<circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9.5v.01M15 9.5v.01"/>',
            ];
        ?>
        <div class="pkg-grid">
            <?php foreach ($atividades as $i => $a): $c = $i % 5; ?>
                <a class="pkg-card pkg-card--<?= $c ?>" href="/creche/atividades/<?= (int) $a['id'] ?>" style="--i:<?= $i % 12 ?>">
                    <span class="pkg-card__top">
                        <span class="pkg-card__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="46" height="46" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><?= $icones[$c] ?></svg></span>
                        <span class="pkg-card__tema"><?= htmlspecialchars($a['titulo']) ?></span>
                        <span class="pkg-card__faixa"><?= htmlspecialchars($a['faixa_etaria']) ?></span>
                    </span>
                    <span class="pkg-card__foot">
                        <span>
                            <?php if (!empty($a['duracao'])): ?><strong><?= htmlspecialchars($a['duracao']) ?></strong> · <?php endif; ?>
                            <span class="badge badge--<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span>
                            <span class="tag tag--origem"><?= $a['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                        </span>
                        <b class="pkg-card__cta">Abrir atividade ›</b>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="notice creche-notice">
        <strong>Em breve nesta aba:</strong> acompanhamento das crianças (checklists de desenvolvimento, relatórios e alertas).
        Como envolve dados sensíveis de menores, será liberado após o plano de proteção de dados (LGPD/ECA).
    </div>
    </div>
