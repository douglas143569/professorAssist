<?php
    // Agrupa as turmas por escola (byUser ja vem ordenado por escola, depois nome).
    $porEscola = [];
    foreach ($turmas as $t) {
        $porEscola[(int) $t['escola_id']][] = $t;
    }
?>
    <div class="page-header">
        <div>
            <h1>Turmas</h1>
            <p class="muted">Todas as suas turmas, agrupadas por escola.</p>
        </div>
        <a href="/escolas" class="btn btn--primary">Escolas</a>
    </div>

    <?php if (empty($escolas)): ?>
        <div class="card card--empty">
            <p>Você ainda não cadastrou nenhuma escola. A turma é criada dentro de uma escola.</p>
            <p><a href="/escolas" class="btn btn--primary">Cadastrar escola</a></p>
        </div>
    <?php else: ?>
        <?php foreach ($escolas as $e): ?>
            <h2 style="margin-top:24px;">
                <a href="/escolas/<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></a>
            </h2>
            <?php $lista = $porEscola[(int) $e['id']] ?? []; ?>
            <?php if (empty($lista)): ?>
                <div class="card card--empty">
                    <p>Nenhuma turma nesta escola.
                        <a href="/escolas/<?= (int) $e['id'] ?>">Criar turma</a>
                    </p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($lista as $t): ?>
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
            <?= \App\Services\Csrf::campo() ?>
                                    <button type="submit" class="btn btn--danger btn--sm">Excluir</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
