    <p class="breadcrumb">
        <a href="/escolas">Escolas</a> /
        <a href="/escolas/<?= (int) $turma['escola_id'] ?>"><?= htmlspecialchars($turma['escola_nome'] ?? 'Escola') ?></a> /
        <?= htmlspecialchars($turma['nome']) ?>
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($turma['nome']) ?></h1>
            <p class="muted">
                <?php if (!empty($turma['etapa'])): ?><span class="tag"><?= htmlspecialchars($turma['etapa']) ?></span><?php endif; ?>
                <?= htmlspecialchars($turma['ano_serie'] ?? '') ?>
            </p>
        </div>
    </div>

    <div class="split">
        <section>
            <h2>Matérias</h2>
            <?php if (empty($materias)): ?>
                <div class="card card--empty">
                    <p>Nenhuma matéria ainda. Adicione uma ao lado (ex: Matemática, Português).</p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($materias as $m): ?>
                        <div class="card turma-card">
                            <a class="turma-card__main" href="/disciplinas/<?= (int) $m['id'] ?>">
                                <span class="tag"><?= htmlspecialchars($m['etapa']) ?></span>
                                <h3><?= htmlspecialchars($m['nome']) ?></h3>
                            </a>
                            <div class="turma-card__foot">
                                <a href="/disciplinas/<?= (int) $m['id'] ?>" class="btn btn--ghost btn--sm">Abrir</a>
                                <form method="post" action="/disciplinas/<?= (int) $m['id'] ?>/excluir"
                                      onsubmit="return confirm('Excluir a matéria &quot;<?= htmlspecialchars($m['nome'], ENT_QUOTES) ?>&quot; e todos os seus temas, conteúdos e questões?');">
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
                <h3>Nova matéria</h3>
                <form method="post" action="/turmas/<?= (int) $turma['id'] ?>/materias" class="form">
                    <label>Nome da matéria
                        <input type="text" name="nome" placeholder="Ex: Matemática" required>
                    </label>
                    <p class="muted" style="font-size:0.82rem;">A matéria herda a etapa/ano da turma.</p>
                    <button type="submit" class="btn btn--primary">Criar matéria</button>
                </form>
            </div>

            <form method="post" action="/turmas/<?= (int) $turma['id'] ?>/excluir"
                  onsubmit="return confirm('Excluir a turma e tudo dentro dela?');" style="margin-top:12px;">
                <button type="submit" class="btn btn--danger btn--sm">Excluir turma</button>
            </form>
        </aside>
    </div>
