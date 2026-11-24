<?php
/**
 * Dashboard da professora + lista de criancas.
 * Variaveis: $criancas, $turmas, $busca, $turma, $totalCriancas, $totais, $pendentes,
 *            $desatualizadas, $proximasAvaliacoes, $recentes.
 */
use App\Models\PortfolioCrianca;
use App\Services\Csrf;

$sub = 'criancas';
require __DIR__ . '/_topo.php';
?>
<div class="pf" data-token="<?= pf_h(Csrf::token()) ?>">

    <section class="pf-stats" aria-label="Resumo">
        <div class="pf-stat"><span>Registros criados</span><strong><?= (int) $totais['total'] ?></strong><small><?= (int) $totais['no_mes'] ?> neste mês · <?= (int) $totais['na_semana'] ?> na semana</small></div>
        <div class="pf-stat"><span>Crianças</span><strong><?= (int) $totalCriancas ?></strong><small>com portfólio aberto</small></div>
        <div class="pf-stat pf-stat--warn"><span>Portfólios pendentes</span><strong><?= (int) $pendentes ?></strong><small>ainda incompletos</small></div>
        <div class="pf-stat pf-stat--warn"><span>Sem registro novo</span><strong><?= count($desatualizadas) ?></strong><small>há mais de <?= PortfolioCrianca::DIAS_DESATUALIZADO ?> dias</small></div>
    </section>

    <?php if ($totalCriancas > 0): ?>
    <section class="pf-duo">
        <div class="card">
            <h3>Últimas atualizações</h3>
            <?php if (!$recentes): ?>
                <p class="muted">Nenhum registro ainda. Escolha uma criança e clique em <b>Novo registro</b>.</p>
            <?php else: ?>
                <ul class="pf-list">
                    <?php foreach ($recentes as $r): ?>
                        <li>
                            <a href="/creche/portfolio/registros/<?= (int) $r['id'] ?>">
                                <?php if ($r['capa_id']): ?><img src="<?= pf_midia_url((int) $r['capa_id']) ?>" alt="" loading="lazy"><?php else: ?><span class="pf-list__ph" aria-hidden="true">📝</span><?php endif; ?>
                                <span><b><?= pf_h($r['titulo']) ?></b><small><?= pf_h($r['nome']) ?> · <?= pf_data($r['data_registro']) ?></small></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="card">
            <h3>Próximas avaliações</h3>
            <?php if (!$proximasAvaliacoes): ?>
                <p class="muted">Tudo em dia: todas as crianças com registros já têm avaliação recente. 🎉</p>
            <?php else: ?>
                <p class="muted" style="margin-top:-4px;">Crianças sem avaliação descritiva nos últimos 90 dias.</p>
                <ul class="pf-list">
                    <?php foreach ($proximasAvaliacoes as $c): ?>
                        <li><a href="/creche/portfolio/<?= (int) $c['id'] ?>?aba=avaliacoes"><?= pf_avatar($c, 'pf-avatar--sm') ?><span><b><?= pf_h($c['nome']) ?></b><small><?= pf_h($c['turma']) ?> · <?= (int) $c['n_registros'] ?> registro(s)</small></span></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="pf-toolbar">
        <form method="get" class="pf-filters" role="search">
            <input type="search" name="q" value="<?= pf_h($busca) ?>" placeholder="Buscar criança…" aria-label="Buscar criança">
            <?php if ($turmas): ?>
                <select name="turma" aria-label="Filtrar por turma" onchange="this.form.submit()">
                    <option value="">Todas as turmas</option>
                    <?php foreach ($turmas as $t): ?><option value="<?= pf_h($t) ?>" <?= $turma === $t ? 'selected' : '' ?>><?= pf_h($t) ?></option><?php endforeach; ?>
                </select>
            <?php endif; ?>
            <button class="btn btn--ghost" type="submit">Buscar</button>
        </form>
        <a class="btn btn--primary" href="/creche/portfolio/nova">+ Nova criança</a>
    </div>

    <?php if (!$criancas): ?>
        <div class="card card--empty pf-empty">
            <div class="pf-empty__ico" aria-hidden="true">🧸</div>
            <h3><?= $totalCriancas ? 'Nenhuma criança encontrada' : 'Comece o primeiro portfólio' ?></h3>
            <p class="muted"><?= $totalCriancas ? 'Tente outro nome ou turma.' : 'Cadastre uma criança para registrar momentos, evidências e avaliações ao longo do ano.' ?></p>
            <?php if (!$totalCriancas): ?><a class="btn btn--primary" href="/creche/portfolio/nova">+ Cadastrar criança</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="pf-grid">
            <?php foreach ($criancas as $c):
                $p = PortfolioCrianca::progresso($c);
                $old = PortfolioCrianca::desatualizada($c);
            ?>
                <article class="pf-child">
                    <a class="pf-child__main" href="/creche/portfolio/<?= (int) $c['id'] ?>">
                        <?= pf_avatar($c) ?>
                        <h3><?= pf_h($c['nome']) ?></h3>
                        <p class="muted"><?= pf_h($c['turma']) ?> · <?= pf_h(PortfolioCrianca::idade($c['nascimento'])) ?></p>
                        <?= pf_barra($p['pct']) ?>
                        <p class="pf-child__meta">
                            <span><?= $p['pct'] ?>% completo</span>
                            <span><?= (int) $c['n_registros'] ?> registro(s) · <?= (int) $c['n_midias'] ?> mídia(s)</span>
                        </p>
                        <p class="pf-chips">
                            <?php if ($p['completo']): ?><span class="pf-chip pf-chip--ok">Completo</span><?php endif; ?>
                            <?php if ($old): ?><span class="pf-chip pf-chip--warn"><?= $c['ultimo_registro'] ? 'Último registro ' . pf_data($c['ultimo_registro']) : 'Sem registros' ?></span><?php endif; ?>
                        </p>
                    </a>
                    <div class="pf-child__act">
                        <a class="btn btn--primary btn--sm" href="/creche/portfolio/<?= (int) $c['id'] ?>?aba=registro">+ Novo registro</a>
                        <a class="btn btn--ghost btn--sm" href="/creche/portfolio/<?= (int) $c['id'] ?>?aba=linha">Linha do tempo</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script src="/assets/js/portfolio.js" defer></script>
