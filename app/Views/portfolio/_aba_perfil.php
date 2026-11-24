<?php /** Edicao do perfil + exclusao. Variaveis: $turmas, $periodos, $professoraPadrao. */ ?>
<form method="post" action="/creche/portfolio/<?= (int) $c['id'] ?>" enctype="multipart/form-data" class="form card pf-form">
    <?= \App\Services\Csrf::campo() ?>
    <h3>Perfil da criança</h3>
    <?php require __DIR__ . '/_perfil_campos.php'; ?>
    <p class="muted">Matriculada desde <?= pf_data($c['data_matricula']) ?> · portfólio criado em <?= pf_data($c['created_at']) ?></p>
    <div class="actions"><button class="btn btn--primary" type="submit">Salvar perfil</button></div>
</form>

<form method="post" action="/creche/portfolio/<?= (int) $c['id'] ?>/excluir" class="card pf-danger"
      onsubmit="return confirm('Excluir o portfólio inteiro desta criança (registros, fotos, vídeos e avaliações)? Não é possível desfazer.');">
    <?= \App\Services\Csrf::campo() ?>
    <h3>Excluir portfólio</h3>
    <p class="muted">Apaga o perfil e tudo o que foi registrado, inclusive os arquivos. Faça isso quando a criança deixar a escola e a família pedir a eliminação dos dados.</p>
    <button class="btn btn--danger" type="submit">Excluir portfólio desta criança</button>
</form>
