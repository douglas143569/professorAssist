<?php
/** Dashboard da coordenacao. Variaveis: $total, $completos, $mediaPct, $turmas, $professoras, $incompletos, $totais. */
use App\Models\PortfolioCrianca;

$sub = 'coordenacao';
require __DIR__ . '/_topo.php';
?>
<div class="pf">
    <?php if ($total === 0): ?>
        <div class="card card--empty pf-empty"><div class="pf-empty__ico" aria-hidden="true">📊</div><h3>Sem dados ainda</h3><p class="muted">Cadastre crianças e registros para acompanhar o andamento dos portfólios.</p></div>
    <?php else: ?>
    <section class="pf-stats" aria-label="Estatísticas gerais">
        <div class="pf-stat"><span>Portfólios completos</span><strong><?= $completos ?></strong><small>de <?= $total ?> crianças</small></div>
        <div class="pf-stat pf-stat--warn"><span>Portfólios incompletos</span><strong><?= $total - $completos ?></strong><small>precisam de atenção</small></div>
        <div class="pf-stat"><span>Andamento médio</span><strong><?= $mediaPct ?>%</strong><small><?= pf_barra($mediaPct) ?></small></div>
        <div class="pf-stat"><span>Registros no mês</span><strong><?= (int) $totais['no_mes'] ?></strong><small><?= (int) $totais['total'] ?> no total</small></div>
    </section>

    <section class="pf-duo">
        <div class="card">
            <h3>Professoras que precisam atualizar registros</h3>
            <div class="pf-tablewrap">
            <table class="pf-table">
                <thead><tr><th>Professora</th><th>Crianças</th><th>Desatualizadas</th><th>Último registro</th></tr></thead>
                <tbody>
                <?php foreach ($professoras as $g): ?>
                    <tr>
                        <td><?= pf_h($g['nome']) ?></td>
                        <td><?= $g['criancas'] ?></td>
                        <td><?= $g['desatualizadas'] > 0 ? '<span class="pf-chip pf-chip--warn">' . $g['desatualizadas'] . '</span>' : '<span class="pf-chip pf-chip--ok">em dia</span>' ?></td>
                        <td><?= $g['ultimo'] ? pf_data($g['ultimo']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <p class="muted">“Desatualizada” = sem registro novo há mais de <?= PortfolioCrianca::DIAS_DESATUALIZADO ?> dias.</p>
        </div>

        <div class="card">
            <h3>Andamento por turma</h3>
            <?php foreach ($turmas as $g): ?>
                <div class="pf-turma">
                    <div><b><?= pf_h($g['nome']) ?></b><small class="muted"> · <?= $g['completos'] ?>/<?= $g['criancas'] ?> completos</small></div>
                    <?= pf_barra($g['pct']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card">
        <h3>Portfólios incompletos</h3>
        <?php if (!$incompletos): ?>
            <p class="muted">Todos os portfólios estão completos. 🎉</p>
        <?php else: ?>
            <div class="pf-tablewrap">
            <table class="pf-table">
                <thead><tr><th>Criança</th><th>Turma</th><th>Andamento</th><th>O que falta</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($incompletos as $c): ?>
                    <tr>
                        <td><?= pf_h($c['nome']) ?></td>
                        <td><?= pf_h($c['turma']) ?></td>
                        <td style="min-width:120px"><?= pf_barra($c['_prog']['pct']) ?></td>
                        <td class="muted"><?= pf_h(implode(' · ', array_keys(array_filter($c['_prog']['itens'], fn ($ok) => !$ok)))) ?></td>
                        <td><a class="btn btn--ghost btn--sm" href="/creche/portfolio/<?= (int) $c['id'] ?>">Abrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>
