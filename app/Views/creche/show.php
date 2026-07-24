    <p class="breadcrumb">
        <a href="/creche">Creche</a> / Atividade
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($atividade['titulo']) ?></h1>
            <p class="muted">
                <span class="badge badge--<?= htmlspecialchars($atividade['status']) ?>"><?= htmlspecialchars($atividade['status']) ?></span>
                <span class="tag"><?= htmlspecialchars($atividade['faixa_etaria']) ?></span>
                <span class="tag tag--origem"><?= $atividade['origem'] === 'ia' ? 'sugerida por IA' : 'manual' ?></span>
                <?php if (!empty($atividade['duracao'])): ?><span class="tag tag--origem"><?= htmlspecialchars($atividade['duracao']) ?></span><?php endif; ?>
            </p>
        </div>
        <?php if ($atividade['status'] !== 'aprovado'): ?>
            <form method="post" action="/creche/atividades/<?= (int) $atividade['id'] ?>/aprovar">
                <button type="submit" class="btn btn--primary">✓ Aprovar</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($atividade['origem'] === 'ia' && $atividade['status'] === 'rascunho'): ?>
        <div class="notice">Sugestão gerada por IA. <strong>Revise a segurança e a adequação à faixa etária</strong> antes de aprovar.</div>
    <?php endif; ?>

    <form method="post" action="/creche/atividades/<?= (int) $atividade['id'] ?>" class="form">
        <label>Título
            <input type="text" name="titulo" value="<?= htmlspecialchars($atividade['titulo']) ?>" required>
        </label>
        <div class="form--inline">
            <label style="flex:1 1 220px;">Faixa etária
                <select name="faixa_etaria">
                    <?php foreach ($faixas as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>" <?= $atividade['faixa_etaria'] === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="flex:0 1 180px;">Duração
                <input type="text" name="duracao" value="<?= htmlspecialchars($atividade['duracao'] ?? '') ?>">
            </label>
        </div>
        <label>Campo de experiência
            <select name="campo_experiencia">
                <option value="">—</option>
                <?php foreach ($campos as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= ($atividade['campo_experiencia'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Como conduzir
            <textarea name="descricao" rows="8"><?= htmlspecialchars($atividade['descricao'] ?? '') ?></textarea>
        </label>
        <label>Materiais
            <textarea name="materiais" rows="3"><?= htmlspecialchars($atividade['materiais'] ?? '') ?></textarea>
        </label>
        <div class="actions">
            <button type="submit" class="btn btn--primary">Salvar alterações</button>
            <a href="/creche" class="btn btn--ghost">Voltar</a>
        </div>
    </form>

    <form method="post" action="/creche/atividades/<?= (int) $atividade['id'] ?>/excluir"
          onsubmit="return confirm('Excluir esta atividade?');" style="margin-top:12px;">
        <button type="submit" class="btn btn--danger">Excluir atividade</button>
    </form>
