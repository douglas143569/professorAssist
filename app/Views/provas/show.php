<?php
    $dificuldadeLabel = ['facil' => 'Fácil', 'media' => 'Média', 'dificil' => 'Difícil'];
    $tipoLabel = [
        'multipla_escolha' => 'Múltipla escolha',
        'verdadeiro_falso' => 'V ou F',
        'dissertativa' => 'Dissertativa',
    ];
?>
    <p class="breadcrumb">
        <a href="/provas">Provas</a> / <?= htmlspecialchars($prova['titulo']) ?>
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($prova['titulo']) ?></h1>
            <p class="muted">
                <?= htmlspecialchars($prova['disciplina_nome']) ?>
                <?php if (!empty($prova['turma_nome'])): ?> · <?= htmlspecialchars($prova['turma_nome']) ?><?php endif; ?>
                · <?= count($questoes) ?> questão(ões) · total <?= number_format($total_pontos, 2, ',', '') ?> pontos
            </p>
        </div>
    </div>

    <?php if (empty($questoes)): ?>
        <div class="card card--empty">
            <p>Esta prova está sem questões. Adicione abaixo, no banco da matéria.</p>
        </div>
    <?php endif; ?>

    <!-- ---------------- Impressão ---------------- -->
    <div class="card">
        <h3>Imprimir</h3>
        <?php if (empty($versoes)): ?>
            <p class="muted">Gere as versões primeiro.</p>
        <?php else: ?>
            <table class="tabela-versoes">
                <thead>
                    <tr><th>Versão</th><th>Prova do aluno</th><th>Com gabarito</th><th>Cartão-resposta</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($versoes as $v): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($v['rotulo']) ?></strong>
                                <?php if ($v['rotulo'] === 'A'): ?>
                                    <span class="muted" style="font-size:0.76rem;">(sua ordem)</span>
                                <?php endif; ?>
                            </td>
                            <td><a href="/provas/<?= (int) $prova['id'] ?>/imprimir?versao=<?= urlencode($v['rotulo']) ?>">Abrir ↗</a></td>
                            <td><a href="/provas/<?= (int) $prova['id'] ?>/imprimir?versao=<?= urlencode($v['rotulo']) ?>&amp;gabarito=1">Abrir ↗</a></td>
                            <td><a href="/provas/<?= (int) $prova['id'] ?>/cartao?versao=<?= urlencode($v['rotulo']) ?>">Abrir ↗</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <form method="post" action="/provas/<?= (int) $prova['id'] ?>/versoes" class="form form--inline" style="margin-top:16px;">
            <label style="flex:0 1 200px;">Quantas versões
                <select name="quantidade">
                    <?php foreach ($rotulos as $i => $r): ?>
                        <option value="<?= $i + 1 ?>" <?= count($versoes) === $i + 1 ? 'selected' : '' ?>>
                            <?= $i + 1 ?> (<?= implode(', ', array_slice($rotulos, 0, $i + 1)) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn--ghost"
                    onclick="return confirm('Regerar as versões? O embaralhamento muda e os gabaritos já impressos deixam de valer.');">
                Gerar versões
            </button>
        </form>
        <p class="muted" style="font-size:0.82rem; margin:8px 0 0;">
            A versão A mantém a ordem definida por você. As demais embaralham a ordem das questões
            <strong>e</strong> das alternativas — cada uma com seu gabarito próprio.
        </p>
    </div>

    <!-- ---------------- Questões da prova ---------------- -->
    <h2 style="margin-top:32px;">Questões da prova</h2>

    <form method="post" action="/provas/<?= (int) $prova['id'] ?>" class="form">
        <div class="form--inline">
            <label style="flex:2 1 320px;">Título
                <input type="text" name="titulo" value="<?= htmlspecialchars($prova['titulo']) ?>" required>
            </label>
        </div>
        <label>Instruções
            <textarea name="instrucoes" rows="2"><?= htmlspecialchars($prova['instrucoes'] ?? '') ?></textarea>
        </label>

        <?php if (!empty($questoes)): ?>
            <ul class="list list--prova">
                <?php foreach ($questoes as $q): ?>
                    <li>
                        <div class="prova-q">
                            <div class="prova-q__campos">
                                <label class="mini">Ordem
                                    <input type="number" name="ordem[<?= (int) $q['id'] ?>]"
                                           value="<?= (int) $q['ordem'] ?>" min="0" max="999">
                                </label>
                                <label class="mini">Pontos
                                    <input type="text" name="pontuacao[<?= (int) $q['id'] ?>]"
                                           value="<?= number_format((float) $q['pontuacao'], 2, ',', '') ?>">
                                </label>
                            </div>

                            <div class="prova-q__corpo">
                                <p><?= nl2br(htmlspecialchars($q['enunciado'])) ?></p>
                                <p class="muted" style="font-size:0.8rem;">
                                    <span class="tag"><?= htmlspecialchars($tipoLabel[$q['tipo']] ?? $q['tipo']) ?></span>
                                    <span class="tag"><?= htmlspecialchars($dificuldadeLabel[$q['dificuldade']] ?? $q['dificuldade']) ?></span>
                                    <?php if (!empty($q['habilidade_bncc'])): ?>
                                        <span class="tag tag--bncc"><?= htmlspecialchars($q['habilidade_bncc']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($q['modulo_titulo'])): ?>
                                        · <?= htmlspecialchars($q['modulo_titulo']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="actions">
                <button type="submit" class="btn btn--primary">Salvar prova</button>
                <a href="/questoes/<?= (int) $questoes[0]['id'] ?>" class="muted" style="font-size:0.85rem;">Editar questões no banco</a>
            </div>
        <?php endif; ?>
    </form>

    <?php if (!empty($questoes)): ?>
        <div class="actions" style="margin-top:12px;">
            <?php foreach ($questoes as $q): ?>
                <form method="post" action="/provas/<?= (int) $prova['id'] ?>/questoes/<?= (int) $q['id'] ?>/remover" style="display:inline;">
                    <button type="submit" class="btn btn--danger btn--sm"
                            onclick="return confirm('Remover a questão nº <?= (int) $q['ordem'] ?> desta prova?');">
                        Remover nº <?= (int) $q['ordem'] ?>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ---------------- Adicionar do banco ---------------- -->
    <h2 style="margin-top:32px;">Adicionar questão do banco</h2>

    <?php if (empty($disponiveis)): ?>
        <div class="card card--empty">
            <p>
                Não há mais questões aprovadas desta matéria fora da prova.
                <a href="/disciplinas/<?= (int) $prova['disciplina_id'] ?>/questoes">Ir para o banco de questões</a>
                para criar ou aprovar mais.
            </p>
        </div>
    <?php else: ?>
        <div class="card">
            <form method="post" action="/provas/<?= (int) $prova['id'] ?>/questoes" class="form form--inline">
                <label style="flex:1 1 420px;">Questão aprovada
                    <select name="questao_id" required>
                        <?php foreach ($disponiveis as $d): ?>
                            <option value="<?= (int) $d['id'] ?>">
                                [<?= htmlspecialchars($dificuldadeLabel[$d['dificuldade']] ?? $d['dificuldade']) ?>]
                                <?= htmlspecialchars(mb_substr($d['enunciado'], 0, 90)) ?><?= mb_strlen($d['enunciado']) > 90 ? '…' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="btn btn--ghost">Adicionar</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="actions" style="margin-top:32px;">
        <form method="post" action="/provas/<?= (int) $prova['id'] ?>/excluir">
            <button type="submit" class="btn btn--danger"
                    onclick="return confirm('Excluir esta prova? As questões continuam no banco.');">
                Excluir prova
            </button>
        </form>
    </div>
