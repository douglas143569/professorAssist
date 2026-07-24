    <p class="breadcrumb">
        <a href="/modulos/<?= (int) $modulo['id'] ?>"><?= htmlspecialchars($modulo['titulo']) ?></a> /
        Nova questão
    </p>

    <div class="page-header">
        <h1>Nova questão (manual)</h1>
    </div>

    <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/questoes" class="form">
        <label>Enunciado
            <textarea name="enunciado" rows="4" required placeholder="Escreva o enunciado da questão"></textarea>
        </label>

        <div class="form--inline">
            <label>Tipo
                <select name="tipo">
                    <option value="multipla_escolha">Múltipla escolha</option>
                    <option value="verdadeiro_falso">Verdadeiro / Falso</option>
                    <option value="dissertativa">Dissertativa</option>
                </select>
            </label>
            <label>Dificuldade
                <select name="dificuldade">
                    <option value="facil">Fácil</option>
                    <option value="media" selected>Média</option>
                    <option value="dificil">Difícil</option>
                </select>
            </label>
            <label>Habilidade BNCC
                <input type="text" name="habilidade_bncc" value="<?= htmlspecialchars($modulo['codigos_bncc'] ?? '') ?>">
            </label>
            <label>Tags
                <input type="text" name="tags" placeholder="opcional">
            </label>
        </div>

        <fieldset class="alternativas">
            <legend>Alternativas <span class="muted">(marque a correta; não se aplica a dissertativa)</span></legend>
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="alt-row">
                    <input type="radio" name="correta" value="<?= $i ?>" <?= $i === 0 ? 'checked' : '' ?> title="Marcar como correta">
                    <input type="text" name="alt_texto[]" placeholder="Alternativa <?= $i + 1 ?>">
                </div>
            <?php endfor; ?>
        </fieldset>

        <div class="actions">
            <button type="submit" class="btn btn--primary">Criar questão</button>
            <a href="/modulos/<?= (int) $modulo['id'] ?>" class="btn btn--ghost">Cancelar</a>
        </div>
    </form>
