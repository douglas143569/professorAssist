<?php
    // Letras que aparecem no cartao: o maior numero de alternativas da prova.
    $maxAlts = 0;
    foreach ($questoes as $q) {
        $maxAlts = max($maxAlts, count($q['alternativas'] ?? []));
    }
    $letras = array_slice(['a', 'b', 'c', 'd', 'e', 'f'], 0, max(4, $maxAlts));
?>
    <div class="folha-toolbar no-print">
        <a href="/provas/<?= (int) $prova['id'] ?>" class="btn btn--ghost">← Voltar</a>
        <button type="button" class="btn btn--primary" onclick="window.print()">Imprimir</button>
        <span class="muted" style="font-size:0.82rem;">
            Cartão da versão <?= htmlspecialchars($rotulo) ?> — o gabarito fica na folha do professor.
        </span>
    </div>

    <div class="folha cartao-folha">
        <header class="cartao-cab">
            <div>
                <?php if (!empty($prova['escola_nome'])): ?>
                    <p class="prova-cab__escola"><?= htmlspecialchars($prova['escola_nome']) ?></p>
                <?php endif; ?>
                <h1>Cartão-resposta</h1>
                <p class="prova-cab__meta">
                    <?= htmlspecialchars($prova['titulo']) ?> ·
                    <?= htmlspecialchars($prova['disciplina_nome']) ?>
                </p>
            </div>
            <div class="prova-cab__versao">
                <span>Versão</span>
                <strong><?= htmlspecialchars($rotulo) ?></strong>
            </div>
        </header>

        <div class="prova-cab__campos">
            <span class="campo campo--nome">Nome: <i></i></span>
            <span class="campo">Turma: <i></i></span>
            <span class="campo">Data: <i></i></span>
        </div>

        <p class="cartao-instrucao">
            Preencha completamente a bolha da alternativa escolhida. Use caneta azul ou preta.
            Rasuras invalidam a resposta.
        </p>

        <div class="cartao-grade">
            <?php foreach ($questoes as $q): ?>
                <div class="cartao-linha">
                    <span class="cartao-num"><?= (int) $q['numero'] ?></span>
                    <?php if (!empty($q['alternativas'])): ?>
                        <?php foreach ($letras as $letra): ?>
                            <?php $existe = count($q['alternativas']) >= array_search($letra, $letras, true) + 1; ?>
                            <span class="cartao-bolha <?= $existe ? '' : 'is-vazia' ?>">
                                <?= $existe ? htmlspecialchars($letra) : '' ?>
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="cartao-dissertativa">questão dissertativa — responda na folha da prova</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
