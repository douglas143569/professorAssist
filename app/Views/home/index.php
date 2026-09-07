    <div class="greet">
        <h2>Olá, <?= htmlspecialchars(explode(' ', $professor['name'])[0]) ?> 👋</h2>
        <p class="muted">Prepare conteúdos e provas com apoio de IA — você revisa e aprova.</p>
    </div>

    <?php if ($revisao['total'] > 0): ?>
        <a class="chamada-revisao" href="/revisar">
            <span class="chamada-revisao__num"><?= (int) $revisao['total'] ?></span>
            <span class="chamada-revisao__texto">
                <strong>
                    <?= $revisao['total'] === 1 ? 'item espera' : 'itens esperam' ?> sua revisão
                </strong>
                <small>
                    <?php
                        $partes = [];
                        foreach ($revisao['por_tipo'] as $chave => $n) {
                            if ($n > 0) {
                                $partes[] = $n . ' ' . mb_strtolower(\App\Models\Revisao::TIPOS[$chave]['rotulo']);
                            }
                        }
                        echo htmlspecialchars(implode(' · ', $partes));
                    ?>
                </small>
            </span>
            <span class="chamada-revisao__cta">Revisar e aprovar →</span>
        </a>
    <?php endif; ?>

    <?php
        $cartoes = [
            ['escolas', 'Escolas'],
            ['turmas', 'Turmas'],
            ['planos', 'Planos de aula'],
            ['apresentacoes', 'Apresentações'],
            ['atividades', 'Atividades'],
            ['conteudos', 'Conteúdos'],
        ];
    ?>
    <div class="stat-grid">
        <?php foreach ($cartoes as [$chave, $rotulo]): ?>
            <div class="stat js-reveal">
                <div class="stat__value" data-count="<?= (int) ($stats[$chave] ?? 0) ?>"><?= (int) ($stats[$chave] ?? 0) ?></div>
                <div class="stat__label"><?= $rotulo ?></div>
            </div>
        <?php endforeach; ?>
        <div class="stat js-reveal">
            <div class="stat__value" data-count="<?= (int) $stats['questoes'] ?>"><?= (int) $stats['questoes'] ?></div>
            <div class="stat__label">Questões no banco</div>
            <?php if ($stats['questoes'] > 0): ?>
                <div class="stat__sub"><?= (int) $stats['questoes_aprovadas'] ?> aprovada(s)</div>
            <?php endif; ?>
        </div>
        <div class="stat js-reveal">
            <div class="stat__value" data-count="<?= (int) $stats['provas'] ?>"><?= (int) $stats['provas'] ?></div>
            <div class="stat__label">Provas</div>
        </div>
    </div>

    <h3>Ações rápidas</h3>
    <div class="quick">
        <a href="/escolas" class="js-reveal">
            <span class="quick__icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8h20L12 3Z"/><path d="M4 8v11h16V8"/><path d="M9 19v-5h6v5"/></svg>
            </span>
            <span>
                <strong>Minhas escolas</strong>
                <small>Escola → turma → matéria → tema</small>
            </span>
        </a>
        <a href="/turmas" class="js-reveal">
            <span class="quick__icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <span>
                <strong>Minhas turmas</strong>
                <small>Turma → matéria → tema da aula</small>
            </span>
        </a>
        <a href="/creche" class="js-reveal">
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
                <a class="card card--link js-reveal" href="/turmas/<?= (int) $t['id'] ?>">
                    <?php if (!empty($t['etapa'])): ?><span class="tag"><?= htmlspecialchars($t['etapa']) ?></span><?php endif; ?>
                    <h3><?= htmlspecialchars($t['nome']) ?></h3>
                    <p class="muted"><?= (int) $t['n_materias'] ?> matéria(s)</p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
        $totQuestoes = (int) $stats['questoes'];
        $aprovadas = (int) $stats['questoes_aprovadas'];
        $producao = [
            'Planos' => (int) $stats['planos'],
            'Apresent.' => (int) ($stats['apresentacoes'] ?? 0),
            'Atividades' => (int) $stats['atividades'],
            'Conteúdos' => (int) $stats['conteudos'],
            'Provas' => (int) $stats['provas'],
        ];
        $temProducao = array_sum($producao) > 0;
    ?>
    <?php if ($totQuestoes > 0 || $temProducao): ?>
        <h3 style="margin-top:32px;">Resumo</h3>
        <div class="chart-grid">
            <?php if ($totQuestoes > 0): ?>
                <div class="chart-card js-reveal">
                    <h3>Banco de questões</h3>
                    <div class="chart-box" id="chart-questoes"></div>
                </div>
            <?php endif; ?>
            <?php if ($temProducao): ?>
                <div class="chart-card js-reveal">
                    <h3>Produção</h3>
                    <div class="chart-box" id="chart-producao"></div>
                </div>
            <?php endif; ?>
        </div>

        <script src="/assets/js/frappe-charts.min.umd.js"></script>
        <script>
            (function () {
                if (!window.frappe || !frappe.Chart) return;
                var semAnim = window.matchMedia
                    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                var op = { animate: !semAnim };

                <?php if ($totQuestoes > 0): ?>
                new frappe.Chart('#chart-questoes', Object.assign({
                    type: 'donut', height: 220,
                    colors: ['#0f7a54', '#e7c14b'],
                    data: {
                        labels: ['Aprovadas', 'Rascunho'],
                        datasets: [{ values: [<?= $aprovadas ?>, <?= max(0, $totQuestoes - $aprovadas) ?>] }]
                    }
                }, op));
                <?php endif; ?>

                <?php if ($temProducao): ?>
                new frappe.Chart('#chart-producao', Object.assign({
                    type: 'bar', height: 220,
                    colors: ['#0f7a54'],
                    data: {
                        labels: <?= json_encode(array_keys($producao)) ?>,
                        datasets: [{ values: <?= json_encode(array_values($producao)) ?> }]
                    }
                }, op));
                <?php endif; ?>
            })();
        </script>
    <?php endif; ?>
