    <p class="breadcrumb">
        <a href="/turmas">Turmas</a> /
        <?php if (!empty($disciplina['turma_id'])): ?>
            <a href="/turmas/<?= (int) $disciplina['turma_id'] ?>"><?= htmlspecialchars($disciplina['turma_nome'] ?? 'Turma') ?></a> /
        <?php endif; ?>
        <?= htmlspecialchars($disciplina['nome']) ?>
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($disciplina['nome']) ?></h1>
            <p class="muted">
                <span class="tag"><?= htmlspecialchars($disciplina['etapa']) ?></span>
                <?= htmlspecialchars($disciplina['ano_serie'] ?? '') ?>
            </p>
        </div>
        <a href="/disciplinas/<?= (int) $disciplina['id'] ?>/questoes" class="btn btn--ghost">Banco de questões</a>
    </div>

    <div class="split">
        <section>
            <h2>Temas da aula</h2>
            <?php if (empty($modulos)): ?>
                <div class="card card--empty">
                    <p>Nenhum tema ainda. Crie um ao lado para começar a gerar conteúdo, planos, atividades e questões.</p>
                </div>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($modulos as $m): ?>
                        <li>
                            <a href="/modulos/<?= (int) $m['id'] ?>" class="list__main">
                                <?= htmlspecialchars($m['titulo']) ?>
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
                <h3>Novo tema da aula</h3>
                <form method="post" action="/disciplinas/<?= (int) $disciplina['id'] ?>/modulos" class="form">
            <?= \App\Services\Csrf::campo() ?>
                    <label>Título
                        <input type="text" name="titulo" placeholder="Ex: Frações" required>
                    </label>
                    <label>Habilidades BNCC <span class="muted">(opcional)</span>
                        <input type="text" name="codigos_bncc" placeholder="Ex: EF06MA07">
                    </label>
                    <label>Objetivos <span class="muted">(opcional)</span>
                        <textarea name="objetivos" rows="3" placeholder="Objetivos de aprendizagem"></textarea>
                    </label>
                    <button type="submit" class="btn btn--primary">Criar tema</button>
                </form>
            </div>

            <form method="post" action="/disciplinas/<?= (int) $disciplina['id'] ?>/excluir"
                  onsubmit="return confirm('Excluir a matéria &quot;<?= htmlspecialchars($disciplina['nome'], ENT_QUOTES) ?>&quot; e todos os seus temas, conteúdos e questões?');"
                  style="margin-top:12px;">
            <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--danger btn--sm">Excluir matéria</button>
            </form>
        </aside>
    </div>
