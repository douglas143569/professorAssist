    <p class="breadcrumb">
        <a href="/creche/cronograma">Cronograma</a> / Editar atividade
    </p>

    <div class="page-header">
        <div>
            <h1>Editar atividade</h1>
            <p class="muted">
                <span class="tag"><?= htmlspecialchars($item['faixa_etaria']) ?></span>
                <span class="tag tag--origem"><?= $item['origem'] === 'ia' ? 'gerada por IA' : 'manual' ?></span>
            </p>
        </div>
    </div>

    <form method="post" action="/creche/cronograma/<?= (int) $item['id'] ?>" class="form">
        <label>Título
            <input type="text" name="titulo" value="<?= htmlspecialchars($item['titulo']) ?>" required>
        </label>
        <div class="form--inline">
            <label style="flex:0 1 170px;">Data
                <input type="date" name="data" value="<?= htmlspecialchars($item['data']) ?>" required>
            </label>
            <label style="flex:1 1 220px;">Faixa etária
                <select name="faixa_etaria">
                    <?php foreach ($faixas as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>" <?= $item['faixa_etaria'] === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="flex:0 1 150px;">Duração
                <input type="text" name="duracao" value="<?= htmlspecialchars($item['duracao'] ?? '') ?>">
            </label>
        </div>
        <label>Campo de experiência
            <select name="campo_experiencia">
                <option value="">—</option>
                <?php foreach ($campos as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= ($item['campo_experiencia'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Como conduzir
            <textarea name="descricao" rows="8"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
        </label>
        <label>Materiais
            <textarea name="materiais" rows="3"><?= htmlspecialchars($item['materiais'] ?? '') ?></textarea>
        </label>
        <div class="actions">
            <button type="submit" class="btn btn--primary">Salvar alterações</button>
            <a href="/creche/cronograma" class="btn btn--ghost">Voltar ao cronograma</a>
        </div>
    </form>

    <form method="post" action="/creche/cronograma/<?= (int) $item['id'] ?>/excluir"
          onsubmit="return confirm('Remover esta atividade do cronograma?');" style="margin-top:12px;">
        <button type="submit" class="btn btn--danger">Remover do cronograma</button>
    </form>
