<?php
    $tipos = ['multipla_escolha' => 'Múltipla escolha', 'verdadeiro_falso' => 'Verdadeiro / Falso', 'dissertativa' => 'Dissertativa'];
    // Garante ao menos 4 linhas de alternativa para edicao.
    $linhas = $alternativas;
    for ($i = count($linhas); $i < 4; $i++) {
        $linhas[] = ['texto' => '', 'correta' => 0];
    }
?>
    <p class="breadcrumb">
        <a href="/modulos/<?= (int) $questao['modulo_id'] ?>"><?= htmlspecialchars($questao['modulo_titulo'] ?? 'Módulo') ?></a> /
        Questão
    </p>

    <div class="page-header">
        <div>
            <h1>Revisar questão</h1>
            <p class="muted">
                <span class="badge badge--<?= htmlspecialchars($questao['status']) ?>"><?= htmlspecialchars($questao['status']) ?></span>
                <span class="tag tag--origem"><?= $questao['origem'] === 'ia' ? 'gerada por IA' : 'manual' ?></span>
                <?php if (!empty($questao['habilidade_bncc'])): ?>
                    <span class="tag tag--bncc"><?= htmlspecialchars($questao['habilidade_bncc']) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($questao['status'] !== 'aprovado'): ?>
            <form method="post" action="/questoes/<?= (int) $questao['id'] ?>/aprovar">
            <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--primary">✓ Aprovar</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($questao['origem'] === 'ia' && $questao['status'] === 'rascunho'): ?>
        <div class="notice">Questão gerada por IA. <strong>Revise o gabarito</strong> antes de aprovar.</div>
    <?php endif; ?>

    <form method="post" action="/questoes/<?= (int) $questao['id'] ?>" class="form">
            <?= \App\Services\Csrf::campo() ?>
        <label>Enunciado
            <textarea name="enunciado" rows="4" required><?= htmlspecialchars($questao['enunciado']) ?></textarea>
        </label>

        <div class="form--inline">
            <label>Tipo
                <select name="tipo">
                    <?php foreach ($tipos as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $questao['tipo'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Dificuldade
                <select name="dificuldade">
                    <?php foreach (['facil' => 'Fácil', 'media' => 'Média', 'dificil' => 'Difícil'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $questao['dificuldade'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Habilidade BNCC
                <input type="text" name="habilidade_bncc" value="<?= htmlspecialchars($questao['habilidade_bncc'] ?? '') ?>">
            </label>
            <label>Tags
                <input type="text" name="tags" value="<?= htmlspecialchars($questao['tags'] ?? '') ?>">
            </label>
        </div>

        <fieldset class="alternativas">
            <legend>Alternativas <span class="muted">(marque a correta; deixe em branco para ignorar — não se aplica a dissertativa)</span></legend>
            <?php foreach ($linhas as $i => $alt): ?>
                <div class="alt-row">
                    <input type="radio" name="correta" value="<?= $i ?>" <?= !empty($alt['correta']) ? 'checked' : '' ?> title="Marcar como correta">
                    <input type="text" name="alt_texto[]" value="<?= htmlspecialchars($alt['texto'] ?? '') ?>" placeholder="Alternativa <?= $i + 1 ?>">
                </div>
            <?php endforeach; ?>
        </fieldset>

        <div class="actions">
            <button type="submit" class="btn btn--primary">Salvar alterações</button>
            <a href="/modulos/<?= (int) $questao['modulo_id'] ?>" class="btn btn--ghost">Voltar ao módulo</a>
        </div>
    </form>

    <form method="post" action="/questoes/<?= (int) $questao['id'] ?>/excluir"
          onsubmit="return confirm('Excluir esta questão?');" style="margin-top:12px;">
            <?= \App\Services\Csrf::campo() ?>
        <button type="submit" class="btn btn--danger">Excluir questão</button>
    </form>
