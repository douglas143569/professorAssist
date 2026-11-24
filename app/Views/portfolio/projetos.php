<?php
/** Lista e cadastro de projetos pedagogicos. Variaveis: $projetos. */
use App\Services\Csrf;

$sub = 'projetos';
require __DIR__ . '/_topo.php';
?>
<div class="pf" data-token="<?= pf_h(Csrf::token()) ?>">
    <div class="pf-duo pf-duo--wide-left">
        <div>
            <?php if (!$projetos): ?>
                <div class="card card--empty pf-empty">
                    <div class="pf-empty__ico" aria-hidden="true">🎨</div>
                    <h3>Nenhum projeto ainda</h3>
                    <p class="muted">Projetos reúnem registros de várias crianças em torno de um tema, como “Descobrindo as Cores”.</p>
                </div>
            <?php else: ?>
                <div class="pf-grid pf-grid--proj">
                    <?php foreach ($projetos as $p): ?>
                        <a class="card card--link pf-proj" href="/creche/portfolio/projetos/<?= (int) $p['id'] ?>">
                            <h3>📌 <?= pf_h($p['titulo']) ?></h3>
                            <p class="muted"><?= $p['data_inicio'] ? pf_data($p['data_inicio']) . ($p['data_fim'] ? ' a ' . pf_data($p['data_fim']) : ' em diante') : 'Sem datas' ?></p>
                            <p class="pf-chips"><span class="pf-chip"><?= (int) $p['n_registros'] ?> registro(s)</span><span class="pf-chip"><?= (int) $p['n_criancas'] ?> criança(s)</span></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <form method="post" action="/creche/portfolio/projetos" class="form card">
            <?= Csrf::campo() ?>
            <h3>Novo projeto</h3>
            <?php $p = null; require __DIR__ . '/_projeto_campos.php'; ?>
            <button class="btn btn--primary" type="submit">Criar projeto</button>
        </form>
    </div>
</div>
<script src="/assets/js/portfolio.js" defer></script>
