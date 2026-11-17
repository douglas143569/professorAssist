    <p class="breadcrumb">
        <a href="/creche/pacotes">Pacotes</a> / <?= htmlspecialchars($pacote['tema']) ?>
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($pacote['tema']) ?></h1>
            <p class="muted">
                <span class="tag"><?= htmlspecialchars($pacote['faixa_etaria']) ?></span>
                <?php if (!empty($pacote['campo_experiencia'])): ?>
                    <span class="tag"><?= htmlspecialchars($pacote['campo_experiencia']) ?></span>
                <?php endif; ?>
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

        <div class="pkg-grid pkg-grid--wide">
            <?php foreach ($itens as $n => $it): $c = $n % 5; ?>
                <?php
                    $verUrl = '/creche/pacotes/' . (int) $pacote['id'] . '/imprimir?itens=' . (int) $it['id'];
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
                <div class="pkg-card pkg-card--<?= $c ?>" style="--i:<?= $n % 12 ?>">
                    <div class="pkg-card__top pkg-card__top--item">
                        <label class="pkg-check">
                            <input type="checkbox" class="js-item" value="<?= (int) $it['id'] ?>" checked>
                            <span>Imprimir</span>
                        </label>
                        <span class="pkg-tags">
                            <?php if (!empty($it['tipo'])): ?><span class="pkg-tag"><?= htmlspecialchars($it['tipo']) ?></span><?php endif; ?>
                            <?php if (!empty($it['campo_bncc'])): ?><span class="pkg-tag"><?= htmlspecialchars($it['campo_bncc']) ?></span><?php endif; ?>
                        </span>
                        <a class="pkg-open" href="<?= $verUrl ?>" title="Abrir no tamanho de impressão">
                            <span class="pkg-card__tema"><?= htmlspecialchars($it['titulo']) ?></span>
                            <?php if (!empty($it['instrucao'])): ?>
                                <span class="pkg-desc"><?= htmlspecialchars($it['instrucao']) ?></span>
                            <?php endif; ?>
                            <?php if ($emojis): ?>
                                <span class="pkg-figs">
                                    <?php foreach ($emojis as $k => $em): ?>
                                        <span class="pkg-fig" style="--k:<?= $k ?>"><?= htmlspecialchars($em) ?></span>
                                    <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="pkg-card__foot pkg-card__foot--row">
                        <a class="pkg-btn" href="<?= $verUrl ?>">Abrir ↗</a>
                        <a class="pkg-btn pkg-btn--edit" href="/creche/pacote-itens/<?= (int) $it['id'] ?>">Editar ✎</a>
                    </div>
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
            <?= \App\Services\Csrf::campo() ?>
        <button type="submit" class="btn btn--danger">Excluir pacote</button>
    </form>
