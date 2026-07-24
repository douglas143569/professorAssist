    <p class="breadcrumb">
        <a href="/creche/pacotes">Pacotes</a> / <?= htmlspecialchars($pacote['tema']) ?>
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($pacote['tema']) ?></h1>
            <p class="muted">
                <span class="tag"><?= htmlspecialchars($pacote['faixa_etaria']) ?></span>
                <?= count($itens) ?> atividade(s)
            </p>
        </div>
        <a href="/creche/pacotes" class="btn btn--ghost">‹ Voltar</a>
    </div>

    <?php if (empty($itens)): ?>
        <div class="card card--empty"><p>Este pacote está vazio.</p></div>
    <?php else: ?>
        <div class="pack-grid">
            <?php foreach ($itens as $it): ?>
                <a class="card card--link pack-item" href="/creche/pacote-itens/<?= (int) $it['id'] ?>">
                    <?php if (!empty($it['tipo'])): ?>
                        <span class="tag"><?= htmlspecialchars($it['tipo']) ?></span>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($it['titulo']) ?></h3>
                    <?php if (!empty($it['descricao'])): ?>
                        <p class="muted pack-item__desc"><?= htmlspecialchars(mb_strimwidth($it['descricao'], 0, 130, '…')) ?></p>
                    <?php endif; ?>
                    <span class="pack-item__foot">
                        <?php if (!empty($it['duracao'])): ?><span class="tag tag--origem"><?= htmlspecialchars($it['duracao']) ?></span><?php endif; ?>
                        <span class="act-card__edit">Editar ✎</span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/creche/pacotes/<?= (int) $pacote['id'] ?>/excluir"
          onsubmit="return confirm('Excluir o pacote inteiro?');" style="margin-top:20px;">
        <button type="submit" class="btn btn--danger">Excluir pacote</button>
    </form>
