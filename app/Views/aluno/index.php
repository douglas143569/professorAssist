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
        return $ts ? 'até ' . date('d/m/Y H:i', $ts) : 'sem prazo';
    };
?>
<div class="page-header">
    <div>
        <h1>Olá, <?= htmlspecialchars(explode(' ', trim($aluno['name']))[0]) ?>!</h1>
        <p class="muted">
            <?php foreach ($turmas as $t): ?>
                <span class="tag"><?= htmlspecialchars(($t['escola_nome'] ? $t['escola_nome'] . ' · ' : '') . $t['nome']) ?></span>
            <?php endforeach; ?>
        </p>
    </div>
</div>

<div class="aluno-stats">
    <div class="card stat"><strong><?= count($pendentes) ?></strong><span>tarefa(s) pendente(s)</span></div>
    <div class="card stat"><strong><?= (int) $resumo['entregues'] ?></strong><span>entregue(s) aguardando</span></div>
    <div class="card stat"><strong><?= (int) $resumo['corrigidas'] ?></strong><span>corrigida(s)</span></div>
    <div class="card stat"><strong><?= $resumo['media'] !== null ? number_format($resumo['media'], 1, ',', '') : '—' ?></strong><span>média das notas</span></div>
</div>

<div class="split">
    <section>
        <h2>Próximas tarefas</h2>
        <?php if (empty($proximas)): ?>
            <div class="card card--empty"><p>Nenhuma tarefa liberada ainda.</p></div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($proximas as $t): ?>
                    <?php [$cls, $rot] = $badge[$t['status']] ?? $badge['pendente']; ?>
                    <a class="card card--link" href="/aluno/tarefas/<?= (int) $t['id'] ?>">
                        <span class="badge <?= $cls ?>"><?= $rot ?></span>
                        <span class="tag"><?= htmlspecialchars($t['turma_nome']) ?></span>
                        <h3><?= htmlspecialchars($t['titulo']) ?></h3>
                        <p class="muted"><?= $fmtPrazo($t['entrega_ate']) ?>
                            <?php if ($t['status'] === 'corrigida' && $t['nota'] !== null): ?>
                                · nota <strong><?= number_format((float) $t['nota'], 1, ',', '') ?></strong>
                            <?php endif; ?>
                        </p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p style="margin-top:12px;"><a href="/aluno/tarefas">Ver todas as tarefas →</a></p>
    </section>

    <aside>
        <h2>Avisos fixados</h2>
        <?php if (empty($avisos)): ?>
            <div class="card card--empty"><p>Sem avisos fixados.</p></div>
        <?php else: ?>
            <?php foreach ($avisos as $a): ?>
                <div class="card">
                    <span class="tag"><?= htmlspecialchars($a['turma_nome']) ?></span>
                    <h3><?= htmlspecialchars($a['titulo']) ?></h3>
                    <p class="muted" style="white-space:pre-wrap;"><?= htmlspecialchars(mb_strimwidth($a['corpo'], 0, 220, '…')) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <p style="margin-top:12px;"><a href="/aluno/avisos">Ver mural →</a></p>
    </aside>
</div>
