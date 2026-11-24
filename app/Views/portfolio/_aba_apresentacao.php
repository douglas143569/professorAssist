<?php /** Apresentacao: texto inicial, adaptacao e caracteristicas observadas. */ ?>
<form method="post" action="/creche/portfolio/<?= (int) $c['id'] ?>/apresentacao" class="form pf-form">
    <?= \App\Services\Csrf::campo() ?>
    <div class="card">
        <h3>Texto inicial da professora</h3>
        <p class="muted">Quem é esta criança? É a primeira página do portfólio.</p>
        <?= pf_area_texto('apresentacao', (string) $c['apresentacao'], ['rows' => 6, 'max' => 4000, 'ia' => 'redigir,corrigir', 'foco' => 'Apresentação inicial de uma criança no portfólio pedagógico', 'crianca' => $c['id'], 'ph' => 'Ex.: alegre, curiosa, adora música e brincar com massinha…']) ?>
    </div>
    <div class="card">
        <h3>Adaptação</h3>
        <p class="muted">Como foi a chegada e a adaptação à escola.</p>
        <?= pf_area_texto('adaptacao', (string) $c['adaptacao'], ['rows' => 5, 'max' => 3000, 'ia' => 'redigir,corrigir', 'foco' => 'Processo de adaptação de uma criança à creche', 'crianca' => $c['id'], 'ph' => 'Ex.: choro nos primeiros dias, aceitou o objeto de apego, hoje entra tranquila…']) ?>
    </div>
    <div class="card">
        <h3>Características observadas</h3>
        <p class="muted">Gostos, jeito de se relacionar, interesses, forma de aprender.</p>
        <?= pf_area_texto('caracteristicas', (string) $c['caracteristicas'], ['rows' => 5, 'max' => 3000, 'ia' => 'redigir,corrigir', 'foco' => 'Características e interesses observados em uma criança', 'crianca' => $c['id'], 'ph' => 'Ex.: gosta de livros com bichos, observa antes de participar, procura os colegas…']) ?>
    </div>
    <div class="actions"><button class="btn btn--primary" type="submit">Salvar apresentação</button></div>
</form>
