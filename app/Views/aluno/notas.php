<div class="page-header">
    <div>
        <h1>Minhas notas</h1>
        <p class="muted">
            <?= (int) $resumo['corrigidas'] ?> tarefa(s) corrigida(s)
            <?php if ($resumo['media'] !== null): ?>
                · média <strong><?= number_format($resumo['media'], 1, ',', '') ?></strong>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (empty($notas)): ?>
    <div class="card card--empty"><p>Nenhuma nota lançada ainda.</p></div>
<?php else: ?>
    <div class="lista-tarefas">
        <?php foreach ($notas as $n): ?>
            <div class="card tarefa-linha">
                <div>
                    <span class="tag"><?= htmlspecialchars($n['turma_nome']) ?></span>
                    <h3><a href="/aluno/tarefas/<?= (int) $n['tarefa_id'] ?>"><?= htmlspecialchars($n['titulo']) ?></a></h3>
                    <?php if (!empty($n['feedback'])): ?>
                        <p class="muted" style="white-space:pre-wrap;"><?= htmlspecialchars($n['feedback']) ?></p>
                    <?php endif; ?>
                    <p class="muted"><small>corrigida em <?= date('d/m/Y', strtotime($n['corrigida_em'])) ?></small></p>
                </div>
                <div class="tarefa-nota">
                    <strong><?= number_format((float) $n['nota'], 1, ',', '') ?></strong>
                    <small class="muted">nota</small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
