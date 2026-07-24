    <div class="greet">
        <h2>Olá, <?= htmlspecialchars(explode(' ', $professor['name'])[0]) ?> 👋</h2>
        <p class="muted">Prepare conteúdos e provas com apoio de IA — você revisa e aprova.</p>
    </div>

    <div class="stat-grid">
        <div class="stat">
            <div class="stat__value"><?= (int) $stats['disciplinas'] ?></div>
            <div class="stat__label">Disciplinas</div>
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
        <a href="/disciplinas">
            <span class="quick__icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 19a2 2 0 0 1 2-2h13"/></svg>
            </span>
            <span>
                <strong>Minhas disciplinas</strong>
                <small>Criar disciplinas, módulos e conteúdo</small>
            </span>
        </a>
        <?php if (!empty($disciplinas)): ?>
            <a href="/disciplinas/<?= (int) $disciplinas[0]['id'] ?>/questoes">
                <span class="quick__icon">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.9.4-1.5 1-1.5 2"/><path d="M11.5 17h.01"/><circle cx="12" cy="12" r="9"/></svg>
                </span>
                <span>
                    <strong>Banco de questões</strong>
                    <small>Revisar e filtrar questões</small>
                </span>
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($disciplinas)): ?>
        <h3 style="margin-top:32px;">Suas disciplinas</h3>
        <div class="card-grid">
            <?php foreach ($disciplinas as $d): ?>
                <a class="card card--link" href="/disciplinas/<?= (int) $d['id'] ?>">
                    <span class="tag"><?= htmlspecialchars($d['etapa']) ?></span>
                    <h3><?= htmlspecialchars($d['nome']) ?></h3>
                    <?php if (!empty($d['ano_serie'])): ?>
                        <p class="muted"><?= htmlspecialchars($d['ano_serie']) ?></p>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
