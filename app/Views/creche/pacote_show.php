    <p class="breadcrumb">
        <a href="/creche/pacotes">Pacotes</a> / <?= htmlspecialchars($pacote['tema']) ?>
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($pacote['tema']) ?></h1>
            <p class="muted">
                <span class="tag"><?= htmlspecialchars($pacote['faixa_etaria']) ?></span>
                <?= count($itens) ?> atividade(s) imprimível(is)
            </p>
        </div>
        <a href="/creche/pacotes" class="btn btn--ghost">‹ Voltar</a>
    </div>

    <?php if (empty($itens)): ?>
        <div class="card card--empty"><p>Este pacote está vazio.</p></div>
    <?php else: ?>
        <div class="card" style="margin-bottom:16px;">
            <p style="margin:0 0 12px;"><strong>Imprimir atividades</strong> — marque as que quiser e clique em imprimir.</p>
            <div class="actions">
                <button type="button" class="btn btn--primary" id="btnImprimirSel">🖨 Imprimir selecionadas</button>
                <a class="btn btn--ghost" href="/creche/pacotes/<?= (int) $pacote['id'] ?>/imprimir">Imprimir todas</a>
                <a class="btn btn--ghost" href="/creche/pacotes/<?= (int) $pacote['id'] ?>/imprimir?gabarito=1">Imprimir com respostas</a>
            </div>
        </div>

        <div class="pack-grid">
            <?php foreach ($itens as $it): ?>
                <?php $verUrl = '/creche/pacotes/' . (int) $pacote['id'] . '/imprimir?itens=' . (int) $it['id']; ?>
                <div class="card pack-item">
                    <label class="pack-check">
                        <input type="checkbox" class="js-item" value="<?= (int) $it['id'] ?>" checked>
                        <?php if (!empty($it['tipo'])): ?><span class="tag"><?= htmlspecialchars($it['tipo']) ?></span><?php endif; ?>
                    </label>
                    <a class="pack-item__open" href="<?= $verUrl ?>" title="Abrir no tamanho de impressão">
                        <h3><?= htmlspecialchars($it['titulo']) ?></h3>
                        <?php if (!empty($it['instrucao'])): ?>
                            <p class="muted pack-item__desc"><?= htmlspecialchars($it['instrucao']) ?></p>
                        <?php endif; ?>
                        <?php
                            $emojis = [];
                            foreach ($it['itens'] ?? [] as $sub) {
                                if (!empty($sub['figura'])) {
                                    $emojis[] = $sub['figura'];
                                } elseif (!empty($sub['opcoes'])) {
                                    $emojis = array_merge($emojis, $sub['opcoes']);
                                } elseif (!empty($sub['esquerda'])) {
                                    $emojis[] = $sub['esquerda'];
                                    if (!empty($sub['direita'])) { $emojis[] = $sub['direita']; }
                                } elseif (!empty($sub['sequencia'])) {
                                    $emojis = array_merge($emojis, $sub['sequencia']);
                                }
                            }
                            $emojis = array_slice($emojis, 0, 8);
                        ?>
                        <?php if ($emojis): ?>
                            <div class="pack-figs">
                                <?php foreach ($emojis as $em): ?>
                                    <span class="pack-fig"><?= htmlspecialchars($em) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                    <span class="pack-item__foot">
                        <a class="btn btn--ghost btn--sm" href="<?= $verUrl ?>">Abrir ↗</a>
                        <a class="act-card__edit" href="/creche/pacote-itens/<?= (int) $it['id'] ?>">Editar ✎</a>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
            document.getElementById('btnImprimirSel')?.addEventListener('click', function () {
                var ids = Array.from(document.querySelectorAll('.js-item:checked')).map(function (c) { return c.value; });
                if (!ids.length) { alert('Selecione ao menos uma atividade.'); return; }
                window.location.href = '/creche/pacotes/<?= (int) $pacote['id'] ?>/imprimir?itens=' + ids.join(',');
            });
        </script>
    <?php endif; ?>

    <form method="post" action="/creche/pacotes/<?= (int) $pacote['id'] ?>/excluir"
          onsubmit="return confirm('Excluir o pacote inteiro?');" style="margin-top:20px;">
        <button type="submit" class="btn btn--danger">Excluir pacote</button>
    </form>
