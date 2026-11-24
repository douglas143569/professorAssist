<?php /** Novo registro pedagogico (fluxo rapido). */ $r = null; ?>
<form method="post" action="/creche/portfolio/<?= (int) $c['id'] ?>/registros" enctype="multipart/form-data" class="form pf-form card js-pf-salvar">
    <?= \App\Services\Csrf::campo() ?>
    <h3>Novo registro de <?= pf_h(explode(' ', trim($c['nome']))[0]) ?></h3>
    <p class="muted">Adicione fotos, escreva a observação e salve — o registro entra sozinho na linha do tempo e no portfólio.</p>
    <?php require __DIR__ . '/_registro_campos.php'; ?>
    <div class="actions">
        <button class="btn btn--primary" type="submit" data-loading="Salvando…">Salvar registro</button>
        <a class="btn btn--ghost" href="?aba=linha">Cancelar</a>
    </div>
</form>
