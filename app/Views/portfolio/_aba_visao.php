<?php /** Visao geral: checklist de progresso, ultimos registros e mensagem final. */ ?>
<div class="pf-duo">
    <div class="card">
        <h3>Como está este portfólio</h3>
        <ul class="pf-check-list">
            <?php foreach ($prog['itens'] as $rot => $ok): ?>
                <li class="<?= $ok ? 'is-ok' : '' ?>"><span aria-hidden="true"><?= $ok ? '✓' : '○' ?></span> <?= pf_h($rot) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="muted"><?= (int) $c['n_registros'] ?> registro(s) · <?= (int) $c['n_midias'] ?> mídia(s) · <?= (int) $c['n_areas'] ?>/9 áreas descritas · <?= (int) $c['n_aval'] ?> avaliação(ões)</p>
    </div>

    <div class="card">
        <h3>Últimos registros</h3>
        <?php if (!$recentes): ?>
            <p class="muted">Ainda não há registros. <a href="?aba=registro">Crie o primeiro</a> — leva menos de um minuto.</p>
        <?php else: ?>
            <ul class="pf-list">
                <?php foreach ($recentes as $r): ?>
                    <li><a href="/creche/portfolio/registros/<?= (int) $r['id'] ?>">
                        <?php if ($r['capa_id']): ?><img src="<?= pf_midia_url((int) $r['capa_id']) ?>" alt="" loading="lazy"><?php else: ?><span class="pf-list__ph" aria-hidden="true">📝</span><?php endif; ?>
                        <span><b><?= pf_h($r['titulo']) ?></b><small><?= pf_data($r['data_registro']) ?> · <?= pf_h($r['categoria']) ?></small></span>
                    </a></li>
                <?php endforeach; ?>
            </ul>
            <a href="?aba=linha">Ver linha do tempo completa →</a>
        <?php endif; ?>
    </div>
</div>

<form method="post" action="/creche/portfolio/<?= (int) $c['id'] ?>/mensagem" class="form card">
    <?= \App\Services\Csrf::campo() ?>
    <h3>Mensagem final da professora</h3>
    <p class="muted">Aparece na última página do relatório em PDF.</p>
    <?= pf_area_texto('mensagem_final', (string) $c['mensagem_final'], ['rows' => 5, 'max' => 3000, 'ia' => 'redigir,corrigir', 'foco' => 'Mensagem final carinhosa da professora para a família no encerramento do portfólio', 'crianca' => $c['id'], 'ph' => 'Ex.: cantou na roda, aprendeu a pedir ajuda, fez novos amigos…  (a IA transforma em uma mensagem final)']) ?>
    <div class="actions"><button class="btn btn--primary" type="submit">Salvar mensagem</button></div>
</form>
