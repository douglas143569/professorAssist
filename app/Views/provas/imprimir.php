    <div class="folha-toolbar no-print">
        <a href="/provas/<?= (int) $prova['id'] ?>" class="btn btn--ghost">← Voltar</a>
        <button type="button" class="btn btn--primary" onclick="window.print()">Imprimir</button>
        <?php if ($mostrarGabarito): ?>
            <a href="/provas/<?= (int) $prova['id'] ?>/imprimir?versao=<?= urlencode($rotulo) ?>" class="btn btn--ghost">Ver sem gabarito</a>
        <?php else: ?>
            <a href="/provas/<?= (int) $prova['id'] ?>/imprimir?versao=<?= urlencode($rotulo) ?>&amp;gabarito=1" class="btn btn--ghost">Ver com gabarito</a>
        <?php endif; ?>
        <span class="muted" style="font-size:0.82rem;">Versão <?= htmlspecialchars($rotulo) ?></span>
    </div>

    <div class="folha prova-folha">
        <header class="prova-cab">
            <div class="prova-cab__topo">
                <div>
                    <?php if (!empty($prova['escola_nome'])): ?>
                        <p class="prova-cab__escola"><?= htmlspecialchars($prova['escola_nome']) ?></p>
                    <?php endif; ?>
                    <h1><?= htmlspecialchars($prova['titulo']) ?></h1>
                    <p class="prova-cab__meta">
                        <?= htmlspecialchars($prova['disciplina_nome']) ?>
                        <?php if (!empty($prova['turma_nome'])): ?> · <?= htmlspecialchars($prova['turma_nome']) ?><?php endif; ?>
                    </p>
                </div>
                <div class="prova-cab__versao">
                    <span>Versão</span>
                    <strong><?= htmlspecialchars($rotulo) ?></strong>
                </div>
            </div>

            <div class="prova-cab__campos">
                <span class="campo campo--nome">Nome: <i></i></span>
                <span class="campo">Data: <i></i></span>
                <span class="campo">Nota: <i></i></span>
            </div>

            <p class="prova-cab__valor">
                <?= count($questoes) ?> questões · valor total <?= number_format($total_pontos, 2, ',', '') ?> pontos
            </p>

            <?php if (!empty($prova['instrucoes'])): ?>
                <div class="prova-instrucoes">
                    <strong>Instruções:</strong> <?= nl2br(htmlspecialchars($prova['instrucoes'])) ?>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($mostrarGabarito): ?>
            <div class="prova-gabarito no-print-break">
                <strong>GABARITO — versão <?= htmlspecialchars($rotulo) ?></strong>
                <div class="prova-gabarito__linha">
                    <?php foreach ($gabarito as $numero => $letra): ?>
                        <span><?= (int) $numero ?><i><?= htmlspecialchars($letra) ?></i></span>
                    <?php endforeach; ?>
                </div>
                <p>Esta cópia é do professor — não entregue ao aluno.</p>
            </div>
        <?php endif; ?>

        <ol class="prova-questoes">
            <?php foreach ($questoes as $q): ?>
                <li class="prova-questao">
                    <div class="prova-questao__enunciado">
                        <span class="prova-questao__num"><?= (int) $q['numero'] ?>.</span>
                        <div>
                            <?= nl2br(htmlspecialchars($q['enunciado'])) ?>
                            <span class="prova-questao__pontos">(<?= number_format((float) $q['pontuacao'], 2, ',', '') ?> pt)</span>
                        </div>
                    </div>

                    <?php if (!empty($q['alternativas'])): ?>
                        <ul class="prova-alts">
                            <?php foreach ($q['alternativas'] as $alt): ?>
                                <li class="<?= ($mostrarGabarito && !empty($alt['correta'])) ? 'is-correta' : '' ?>">
                                    <span class="prova-alts__letra"><?= htmlspecialchars($alt['letra']) ?>)</span>
                                    <span><?= htmlspecialchars($alt['texto']) ?></span>
                                    <?php if ($mostrarGabarito && !empty($alt['correta'])): ?>
                                        <span class="prova-alts__check">✓</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="prova-linhas">
                            <span></span><span></span><span></span><span></span>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
