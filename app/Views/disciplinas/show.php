    <p class="breadcrumb"><a href="/disciplinas">Disciplinas</a> / <?= htmlspecialchars($disciplina['nome']) ?></p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($disciplina['nome']) ?></h1>
            <p class="muted">
                <span class="tag"><?= htmlspecialchars($disciplina['etapa']) ?></span>
                <?= htmlspecialchars($disciplina['ano_serie'] ?? '') ?>
            </p>
        </div>
    </div>

    <div class="split">
        <section>
            <h2>Módulos</h2>
            <?php if (empty($modulos)): ?>
                <div class="card card--empty">
                    <p>Nenhum módulo ainda. Crie um ao lado para começar a gerar conteúdo.</p>
                </div>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($modulos as $m): ?>
                        <li>
                            <a href="/modulos/<?= (int) $m['id'] ?>">
                                <strong><?= htmlspecialchars($m['titulo']) ?></strong>
                            </a>
                            <?php if (!empty($m['codigos_bncc'])): ?>
                                <span class="tag tag--bncc"><?= htmlspecialchars($m['codigos_bncc']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <aside>
            <div class="card">
                <h3>Novo módulo</h3>
                <form method="post" action="/disciplinas/<?= (int) $disciplina['id'] ?>/modulos" class="form">
                    <label>Título
                        <input type="text" name="titulo" placeholder="Ex: Frações" required>
                    </label>
                    <label>Habilidades BNCC <span class="muted">(opcional)</span>
                        <input type="text" name="codigos_bncc" placeholder="Ex: EF06MA07">
                    </label>
                    <label>Objetivos <span class="muted">(opcional)</span>
                        <textarea name="objetivos" rows="3" placeholder="Objetivos de aprendizagem"></textarea>
                    </label>
                    <button type="submit" class="btn btn--primary">Criar módulo</button>
                </form>
            </div>
        </aside>
    </div>
