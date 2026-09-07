<?php
    $badge = [
        'pendente' => ['badge--pendente', 'Pendente'],
        'entregue' => ['badge--entregue', 'Entregue'],
        'corrigida' => ['badge--corrigida', 'Corrigida'],
    ];
    $fmtPrazo = static function (?string $d): string {
        if (!$d) {
            return 'sem prazo';
        }
        $ts = strtotime($d);
        if (!$ts) {
            return 'sem prazo';
        }
        $venceu = $ts < time();
        return ($venceu ? 'venceu em ' : 'até ') . date('d/m/Y H:i', $ts);
    };
?>
<div class="page-header"><h1>Minhas tarefas</h1></div>

<?php if (empty($tarefas)): ?>
    <div class="card card--empty"><p>Nenhuma tarefa liberada ainda.</p></div>
<?php else: ?>
    <div class="lista-tarefas">
        <?php foreach ($tarefas as $t): ?>
            <?php [$cls, $rot] = $badge[$t['status']] ?? $badge['pendente']; ?>
            <a class="card card--link tarefa-linha" href="/aluno/tarefas/<?= (int) $t['id'] ?>">
                <div>
                    <span class="badge <?= $cls ?>"><?= $rot ?></span>
                    <span class="tag"><?= htmlspecialchars($t['turma_nome']) ?></span>
                    <h3><?= htmlspecialchars($t['titulo']) ?></h3>
                    <p class="muted"><?= $fmtPrazo($t['entrega_ate']) ?></p>
                </div>
                <?php if ($t['status'] === 'corrigida' && $t['nota'] !== null): ?>
                    <div class="tarefa-nota">
                        <strong><?= number_format((float) $t['nota'], 1, ',', '') ?></strong>
                        <small class="muted">nota</small>
                    </div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
