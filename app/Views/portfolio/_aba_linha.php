<?php /** Linha do tempo cronologica. Variaveis: $itens, $filtros, $meses, $opcoesProjeto. */
require __DIR__ . '/_filtros.php';
?>
<?php if (!$itens): ?>
    <div class="card card--empty pf-empty">
        <div class="pf-empty__ico" aria-hidden="true">🌱</div>
        <h3>Nenhum registro por aqui</h3>
        <p class="muted">Cada momento registrado aparece nesta linha do tempo, do mais recente ao mais antigo.</p>
        <a class="btn btn--primary" href="?aba=registro">+ Novo registro</a>
    </div>
<?php else: ?>
    <ol class="pf-timeline">
        <?php $mesAtual = ''; foreach ($itens as $r):
            $ym = substr($r['data_registro'], 0, 7);
            if ($ym !== $mesAtual): $mesAtual = $ym; ?>
                <li class="pf-timeline__mes" aria-hidden="true"><span><?= pf_h(pf_mes($ym)) ?></span></li>
            <?php endif; ?>
            <li class="pf-tl">
                <time datetime="<?= pf_h($r['data_registro']) ?>"><b><?= date('d', strtotime($r['data_registro'])) ?></b><?= pf_h(['01'=>'jan','02'=>'fev','03'=>'mar','04'=>'abr','05'=>'mai','06'=>'jun','07'=>'jul','08'=>'ago','09'=>'set','10'=>'out','11'=>'nov','12'=>'dez'][substr($ym, 5, 2)]) ?></time>
                <article class="pf-tl__card">
                    <?php if ($r['capa_id']): ?>
                        <a class="pf-tl__img" href="/creche/portfolio/registros/<?= (int) $r['id'] ?>" tabindex="-1" aria-hidden="true"><img src="<?= pf_midia_url((int) $r['capa_id']) ?>" alt="" loading="lazy"></a>
                    <?php endif; ?>
                    <div class="pf-tl__body">
                        <p class="pf-chips"><span class="pf-chip"><?= pf_h($r['categoria']) ?></span>
                            <?php if ($r['projeto_titulo']): ?><span class="pf-chip pf-chip--proj">📌 <?= pf_h($r['projeto_titulo']) ?></span><?php endif; ?>
                            <?php if ((int) $r['n_midias'] > 0): ?><span class="pf-chip">🖼 <?= (int) $r['n_midias'] ?></span><?php endif; ?></p>
                        <h4><?= pf_h($r['titulo']) ?></h4>
                        <p><?= pf_h(mb_strimwidth(preg_replace('/\s+/', ' ', $r['descricao']), 0, 180, '…')) ?></p>
                        <a class="btn btn--ghost btn--sm" href="/creche/portfolio/registros/<?= (int) $r['id'] ?>">Visualizar detalhes</a>
                    </div>
                </article>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
