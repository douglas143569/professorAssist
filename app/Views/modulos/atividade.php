<?php $letras = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']; ?>

    <div class="no-print folha-toolbar">
        <a href="/modulos/<?= (int) $modulo['id'] ?>" class="btn btn--ghost btn--sm">‹ Voltar ao tema</a>
        <div class="actions">
            <?php if ($gabarito): ?>
                <a href="/modulos/<?= (int) $modulo['id'] ?>/atividade" class="btn btn--ghost btn--sm">Ocultar gabarito</a>
            <?php else: ?>
                <a href="/modulos/<?= (int) $modulo['id'] ?>/atividade?gabarito=1" class="btn btn--ghost btn--sm">Mostrar gabarito</a>
            <?php endif; ?>
            <button type="button" class="btn btn--primary btn--sm" onclick="window.print()">🖨 Imprimir</button>
        </div>
    </div>
    <p class="no-print muted" style="font-size:0.82rem; margin:-8px 0 18px;">
        Ao imprimir, escolha a impressora na janela do navegador (ou "Salvar como PDF").
    </p>

    <div class="folha">
        <header class="folha__head">
            <h1><?= htmlspecialchars($modulo['titulo']) ?></h1>
            <p class="folha__sub">
                <?= htmlspecialchars($modulo['disciplina_nome']) ?> · <?= htmlspecialchars($modulo['disciplina_etapa']) ?>
                <?php if ($gabarito): ?> · <strong>GABARITO</strong><?php endif; ?>
            </p>
            <div class="folha__meta">
                <span>Nome: ______________________________</span>
                <span>Turma: __________</span>
                <span>Data: ____ / ____ / ______</span>
            </div>
        </header>

        <?php if (empty($questoes)): ?>
            <p class="muted">Nenhuma questão neste tema ainda. Crie ou gere questões antes de montar a atividade.</p>
        <?php else: ?>
            <ol class="folha__questoes">
                <?php foreach ($questoes as $q): ?>
                    <li class="folha-q">
                        <div class="folha-q__enun"><?= nl2br(htmlspecialchars($q['enunciado'])) ?></div>

                        <?php if (!empty($q['alternativas'])): ?>
                            <div class="folha-q__alts">
                                <?php foreach ($q['alternativas'] as $i => $alt): ?>
                                    <div class="alt <?= ($gabarito && $alt['correta']) ? 'alt--correta' : '' ?>">
                                        <span class="alt__letra"><?= $letras[$i] ?? '?' ?>)</span>
                                        <?= htmlspecialchars($alt['texto']) ?>
                                        <?php if ($gabarito && $alt['correta']): ?> <span class="alt__marca">✓</span><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($q['tipo'] === 'dissertativa'): ?>
                            <div class="folha-q__linhas">
                                <span></span><span></span><span></span>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
