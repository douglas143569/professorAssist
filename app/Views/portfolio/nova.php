<?php
/** Cadastro de crianca. Variaveis: $turmas, $periodos, $professoraPadrao. */
use App\Services\Csrf;

$sub = 'criancas';
$c = null;
require __DIR__ . '/_topo.php';
?>
<div class="pf" data-token="<?= pf_h(Csrf::token()) ?>">
    <p class="breadcrumb"><a href="/creche/portfolio">Portfólio</a> / Nova criança</p>
    <form method="post" action="/creche/portfolio/criancas" enctype="multipart/form-data" class="form card pf-form">
        <?= Csrf::campo() ?>
        <h3>Perfil da criança</h3>
        <?php require __DIR__ . '/_perfil_campos.php'; ?>
        <div class="actions">
            <button class="btn btn--primary" type="submit">Criar portfólio</button>
            <a class="btn btn--ghost" href="/creche/portfolio">Cancelar</a>
        </div>
    </form>
    <p class="pf-privacidade">🔒 Os dados ficam visíveis só para você e podem ser excluídos a qualquer momento. A IA nunca recebe o nome nem a foto da criança.</p>
</div>
