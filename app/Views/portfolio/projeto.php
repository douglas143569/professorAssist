<?php
/** Um projeto: objetivos, atividades, evidencias e participacao por crianca. Variaveis: $p, $porCrianca. */
use App\Services\Csrf;

$sub = 'projetos';
require __DIR__ . '/_topo.php';
?>
<div class="pf" data-token="<?= pf_h(Csrf::token()) ?>">
    <p class="breadcrumb"><a href="/creche/portfolio/projetos">Projetos</a> / <?= pf_h($p['titulo']) ?></p>

    <article class="card pf-detalhe">
        <h2>📌 <?= pf_h($p['titulo']) ?></h2>
        <p class="muted"><?= $p['data_inicio'] ? pf_data($p['data_inicio']) . ($p['data_fim'] ? ' a ' . pf_data($p['data_fim']) : ' em diante') : 'Sem datas definidas' ?></p>
        <?php if ($p['objetivos']): ?><h4>Objetivos</h4><div class="pf-texto"><?= nl2br(pf_h($p['objetivos'])) ?></div><?php endif; ?>
        <?php if ($p['atividades']): ?><h4>Atividades realizadas</h4><div class="pf-texto"><?= nl2br(pf_h($p['atividades'])) ?></div><?php endif; ?>
    </article>

    <section>
        <h3>Participação e evidências por criança</h3>
        <?php if (!$porCrianca): ?>
            <p class="muted">Nenhum registro ligado a este projeto ainda. Ao criar um registro, escolha este projeto no campo “Projeto pedagógico”.</p>
        <?php else: foreach ($porCrianca as $cid => $g): ?>
            <div class="card">
                <h4><a href="/creche/portfolio/<?= (int) $cid ?>"><?= pf_h($g['nome']) ?></a> <span class="pf-chip"><?= count($g['registros']) ?> registro(s)</span></h4>
                <ul class="pf-list">
                    <?php foreach ($g['registros'] as $r): ?>
                        <li><a href="/creche/portfolio/registros/<?= (int) $r['id'] ?>">
                            <?php if ($r['capa_id']): ?><img src="<?= pf_midia_url((int) $r['capa_id']) ?>" alt="" loading="lazy"><?php else: ?><span class="pf-list__ph" aria-hidden="true">📝</span><?php endif; ?>
                            <span><b><?= pf_h($r['titulo']) ?></b><small><?= pf_data($r['data_registro']) ?> · <?= pf_h(mb_strimwidth(preg_replace('/\s+/', ' ', $r['descricao']), 0, 110, '…')) ?></small></span>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; endif; ?>
    </section>

    <details class="card pf-edit">
        <summary><b>✏️ Editar projeto</b></summary>
        <form method="post" action="/creche/portfolio/projetos/<?= (int) $p['id'] ?>" class="form">
            <?= Csrf::campo() ?>
            <?php require __DIR__ . '/_projeto_campos.php'; ?>
            <button class="btn btn--primary" type="submit">Salvar</button>
        </form>
    </details>
    <form method="post" action="/creche/portfolio/projetos/<?= (int) $p['id'] ?>/excluir" onsubmit="return confirm('Excluir o projeto? Os registros continuam nos portfólios, apenas sem projeto.');">
        <?= Csrf::campo() ?>
        <button class="btn btn--danger" type="submit">Excluir projeto</button>
    </form>
</div>
<script src="/assets/js/portfolio.js" defer></script>
