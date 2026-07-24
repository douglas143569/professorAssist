    <div class="page-header">
        <div>
            <h1>Disciplinas</h1>
            <p class="muted">Professor: <?= htmlspecialchars($professor['name']) ?></p>
        </div>
    </div>

    <div class="split">
        <section>
            <?php if (empty($disciplinas)): ?>
                <div class="card card--empty">
                    <p>Nenhuma disciplina ainda. Crie a primeira ao lado.</p>
                </div>
            <?php else: ?>
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
        </section>

        <aside>
            <div class="card">
                <h3>Nova disciplina</h3>
                <form method="post" action="/disciplinas" class="form">
                    <label>Nome
                        <input type="text" name="nome" placeholder="Ex: Matemática" required>
                    </label>
                    <label>Etapa
                        <select name="etapa">
                            <option value="EF">Ensino Fundamental</option>
                            <option value="EM">Ensino Médio</option>
                        </select>
                    </label>
                    <label>Ano / Série <span class="muted">(opcional)</span>
                        <input type="text" name="ano_serie" placeholder="Ex: 6º ano">
                    </label>
                    <button type="submit" class="btn btn--primary">Criar disciplina</button>
                </form>
            </div>
        </aside>
    </div>
