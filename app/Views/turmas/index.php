    <div class="page-header">
        <div>
            <h1>Turmas</h1>
            <p class="muted">Organize por turma → matéria → tema da aula.</p>
        </div>
    </div>

    <div class="split">
        <section>
            <?php if (empty($turmas)): ?>
                <div class="card card--empty">
                    <p>Nenhuma turma ainda. Crie a primeira ao lado.</p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($turmas as $t): ?>
                        <div class="card turma-card">
                            <a class="turma-card__main" href="/turmas/<?= (int) $t['id'] ?>">
                                <?php if (!empty($t['etapa'])): ?><span class="tag"><?= htmlspecialchars($t['etapa']) ?></span><?php endif; ?>
                                <h3><?= htmlspecialchars($t['nome']) ?></h3>
                                <p class="muted">
                                    <?php if (!empty($t['ano_serie'])): ?><?= htmlspecialchars($t['ano_serie']) ?> · <?php endif; ?>
                                    <?= (int) $t['n_materias'] ?> matéria(s)
                                </p>
                            </a>
                            <div class="turma-card__foot">
                                <a href="/turmas/<?= (int) $t['id'] ?>" class="btn btn--ghost btn--sm">Abrir</a>
                                <form method="post" action="/turmas/<?= (int) $t['id'] ?>/excluir"
                                      onsubmit="return confirm('Excluir a turma &quot;<?= htmlspecialchars($t['nome'], ENT_QUOTES) ?>&quot; e tudo dentro dela (matérias, temas, conteúdos e questões)?');">
                                    <button type="submit" class="btn btn--danger btn--sm">Excluir</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside>
            <div class="card">
                <h3>Nova turma</h3>
                <form method="post" action="/turmas" class="form">
                    <label>Nome da turma
                        <input type="text" name="nome" placeholder="Ex: 6º ano A" required>
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
                    <button type="submit" class="btn btn--primary">Criar turma</button>
                </form>
            </div>
        </aside>
    </div>
