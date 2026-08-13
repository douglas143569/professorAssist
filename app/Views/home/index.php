    <div class="greet">
        <h2>Olá, <?= htmlspecialchars(explode(' ', $professor['name'])[0]) ?> 👋</h2>
        <p class="muted">Prepare conteúdos e provas com apoio de IA — você revisa e aprova.</p>
    </div>

    <div class="stat-grid">
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['escolas'] ?></div>
            <div class="stat__label">Escolas</div>
        </div>
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['turmas'] ?></div>
            <div class="stat__label">Turmas</div>
        </div>
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['planos'] ?></div>
            <div class="stat__label">Planos de aula</div>
        </div>
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['atividades'] ?></div>
            <div class="stat__label">Atividades</div>
        </div>
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['conteudos'] ?></div>
            <div class="stat__label">Conteúdos</div>
        </div>
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['questoes'] ?></div>
            <div class="stat__label">Questões no banco</div>
            <?php if ($stats['questoes'] > 0): ?>
                <div class="stat__sub"><?= (int) $stats['questoes_aprovadas'] ?> aprovada(s)</div>
            <?php endif; ?>
        </div>
        <div class="stat">
            <div class="stat__value">—</div>
            <div class="stat__label">Provas <span class="soon">em breve</span></div>
        </div>
    </div>

    <h3>Ações rápidas</h3>
    <div class="quick">
        <a href="/escolas">
            <span class="quick__icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8h20L12 3Z"/><path d="M4 8v11h16V8"/><path d="M9 19v-5h6v5"/></svg>
            </span>
            <span>
                <strong>Minhas escolas</strong>
                <small>Escola → turma → matéria → tema</small>
            </span>
        </a>
        <a href="/turmas">
            <span class="quick__icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <span>
                <strong>Minhas turmas</strong>
                <small>Turma → matéria → tema da aula</small>
            </span>
        </a>
        <a href="/creche">
            <span class="quick__icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a3 3 0 0 1 3 3c0 1.5-1 2-1 3h-4c0-1-1-1.5-1-3a3 3 0 0 1 3-3Z"/><path d="M5 21v-3a7 7 0 0 1 14 0v3"/></svg>
            </span>
            <span>
                <strong>Creche</strong>
                <small>Atividades, cronograma e pacotes</small>
            </span>
        </a>
    </div>

    <?php if (!empty($turmas)): ?>
        <h3 style="margin-top:32px;">Suas turmas</h3>
        <div class="card-grid">
            <?php foreach ($turmas as $t): ?>
                <a class="card card--link" href="/turmas/<?= (int) $t['id'] ?>">
                    <?php if (!empty($t['etapa'])): ?><span class="tag"><?= htmlspecialchars($t['etapa']) ?></span><?php endif; ?>
                    <h3><?= htmlspecialchars($t['nome']) ?></h3>
                    <p class="muted"><?= (int) $t['n_materias'] ?> matéria(s)</p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
