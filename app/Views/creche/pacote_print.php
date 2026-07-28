    <div class="no-print folha-toolbar">
        <a href="/creche/pacotes/<?= (int) $pacote['id'] ?>" class="btn btn--ghost btn--sm">‹ Voltar ao pacote</a>
        <div class="actions">
            <?php if ($gabarito): ?>
                <a href="/creche/pacotes/<?= (int) $pacote['id'] ?>/imprimir<?= !empty($_GET['itens']) ? '?itens=' . htmlspecialchars($_GET['itens']) : '' ?>" class="btn btn--ghost btn--sm">Ocultar respostas</a>
            <?php else: ?>
                <a href="/creche/pacotes/<?= (int) $pacote['id'] ?>/imprimir?<?= !empty($_GET['itens']) ? 'itens=' . htmlspecialchars($_GET['itens']) . '&' : '' ?>gabarito=1" class="btn btn--ghost btn--sm">Mostrar respostas</a>
            <?php endif; ?>
            <button type="button" class="btn btn--primary btn--sm" onclick="window.print()">🖨 Imprimir</button>
        </div>
    </div>
    <p class="no-print muted" style="font-size:0.82rem; margin:-8px 0 18px;">
        Dica: na janela de impressão, ative <strong>"Gráficos de segundo plano"</strong> para as cores saírem. Cada atividade sai numa folha.
    </p>

    <?php if (empty($itens)): ?>
        <div class="card card--empty no-print"><p>Nenhuma atividade selecionada.</p></div>
    <?php endif; ?>

    <?php
        $letras = ['a', 'b', 'c', 'd', 'e', 'f'];
        $temas = [0 => 'ws-a', 1 => 'ws-b', 2 => 'ws-c', 3 => 'ws-d'];
    ?>

    <?php foreach ($itens as $idx => $it):
        $lista = $it['itens'] ?? [];
        $formato = $it['formato'] ?? 'escrever';
        $acento = $temas[$idx % 4];
    ?>
        <div class="ws-page <?= $acento ?>">
            <header class="ws-head">
                <span class="ws-head__icon">✏️</span>
                <div class="ws-head__txt">
                    <div class="ws-head__title">Minhas Atividades</div>
                    <div class="ws-head__sub">Atividades para <?= htmlspecialchars($pacote['faixa_etaria']) ?> · para imprimir</div>
                </div>
                <span class="ws-head__dots" aria-hidden="true"></span>
            </header>

            <div class="ws-fields">
                <label>Nome<span class="ws-line"></span></label>
                <label>Turma<span class="ws-line ws-line--sm"></span></label>
                <label>Escola<span class="ws-line"></span></label>
                <label>Data<span class="ws-line ws-line--sm"></span></label>
            </div>

            <?php $sepTitulo = preg_match('/[.?!]$/', trim($it['titulo'])) ? '' : '.'; ?>
            <div class="ws-instr">
                <span class="ws-instr__n"><?= $idx + 1 ?></span>
                <span><strong><?= htmlspecialchars($it['titulo']) . $sepTitulo ?></strong>
                    <?php if (!empty($it['instrucao'])): ?> <?= htmlspecialchars($it['instrucao']) ?><?php endif; ?>
                </span>
            </div>

            <?php if (empty($lista)): ?>
                <p class="muted">Esta atividade não tem itens.</p>

            <?php elseif ($formato === 'sequencia'): ?>
                <div class="ws-rows">
                    <?php foreach ($lista as $row): ?>
                        <div class="ws-row ws-seq">
                            <?php if (!empty($row['rotulo'])): ?><span class="ws-row__lbl"><?= htmlspecialchars($row['rotulo']) ?></span><?php endif; ?>
                            <div class="ws-seq__items">
                                <?php foreach ($row['sequencia'] ?? [] as $s): ?>
                                    <span class="ws-seq__fig"><?= htmlspecialchars($s) ?></span>
                                <?php endforeach; ?>
                                <span class="ws-seq__arrow">→</span>
                                <span class="ws-box ws-box--seq"><?= $gabarito ? htmlspecialchars($row['resposta'] ?? '') : '?' ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($formato === 'pintar'): ?>
                <div class="ws-grid">
                    <?php foreach ($lista as $fig): ?>
                        <div class="ws-item ws-item--paint <?= ($gabarito && !empty($fig['pintar'])) ? 'ws-item--marked' : '' ?>">
                            <div class="ws-fig"><?= htmlspecialchars($fig['figura'] ?? '') ?></div>
                            <?php if (!empty($fig['rotulo'])): ?>
                                <div class="ws-rot"><?= htmlspecialchars($fig['rotulo']) ?></div>
                            <?php endif; ?>
                            <?php if ($gabarito && !empty($fig['pintar'])): ?>
                                <div class="ws-mark">✔ pintar</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($formato === 'ligar'): ?>
                <?php if ($gabarito): ?>
                    <div class="ws-match ws-match--ans">
                        <?php foreach ($lista as $par): ?>
                            <div class="ws-pair">
                                <span class="ws-node">
                                    <span class="ws-node__fig"><?= htmlspecialchars($par['esquerda'] ?? '') ?></span>
                                    <?php if (!empty($par['esq_rotulo'])): ?><span class="ws-node__lbl"><?= htmlspecialchars($par['esq_rotulo']) ?></span><?php endif; ?>
                                </span>
                                <span class="ws-link"></span>
                                <span class="ws-node ws-node--r">
                                    <span class="ws-node__fig"><?= htmlspecialchars($par['direita'] ?? '') ?></span>
                                    <?php if (!empty($par['dir_rotulo'])): ?><span class="ws-node__lbl"><?= htmlspecialchars($par['dir_rotulo']) ?></span><?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php $direitas = $lista; $direitas = array_reverse($direitas); ?>
                    <div class="ws-match">
                        <div class="ws-match__col">
                            <?php foreach ($lista as $par): ?>
                                <div class="ws-node">
                                    <span class="ws-node__fig"><?= htmlspecialchars($par['esquerda'] ?? '') ?></span>
                                    <?php if (!empty($par['esq_rotulo'])): ?><span class="ws-node__lbl"><?= htmlspecialchars($par['esq_rotulo']) ?></span><?php endif; ?>
                                    <span class="ws-dot"></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="ws-match__col ws-match__col--r">
                            <?php foreach ($direitas as $par): ?>
                                <div class="ws-node ws-node--r">
                                    <span class="ws-dot"></span>
                                    <span class="ws-node__fig"><?= htmlspecialchars($par['direita'] ?? '') ?></span>
                                    <?php if (!empty($par['dir_rotulo'])): ?><span class="ws-node__lbl"><?= htmlspecialchars($par['dir_rotulo']) ?></span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($formato === 'circular'): ?>
                <div class="ws-rows">
                    <?php foreach ($lista as $row): $opcoes = $row['opcoes'] ?? []; $correta = (int) ($row['correta'] ?? 0); ?>
                        <div class="ws-row">
                            <?php if (!empty($row['rotulo'])): ?><span class="ws-row__lbl"><?= htmlspecialchars($row['rotulo']) ?></span><?php endif; ?>
                            <div class="ws-opts">
                                <?php foreach ($opcoes as $k => $op): ?>
                                    <span class="ws-opt <?= ($gabarito && ($k + 1) === $correta) ? 'ws-opt--correta' : '' ?>"><?= htmlspecialchars($op) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: /* escrever */ ?>
                <div class="ws-grid">
                    <?php foreach ($lista as $fig): ?>
                        <div class="ws-item">
                            <div class="ws-fig"><?= htmlspecialchars($fig['figura'] ?? '') ?></div>
                            <?php if (!empty($fig['rotulo'])): ?>
                                <div class="ws-rot"><?= htmlspecialchars($fig['rotulo']) ?></div>
                            <?php endif; ?>
                            <div class="ws-box"><?= $gabarito ? htmlspecialchars($fig['resposta'] ?? '') : '' ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <footer class="ws-foot">
                <span>Assistente do Professor</span>
                <span>✂ ✏ 🎨</span>
            </footer>
        </div>
    <?php endforeach; ?>
