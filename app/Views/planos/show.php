    <p class="breadcrumb">
        <a href="/modulos/<?= (int) $plano['modulo_id'] ?>"><?= htmlspecialchars($plano['modulo_titulo']) ?></a> /
        Plano de aula
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($plano['titulo']) ?></h1>
            <p class="muted">
                <span class="badge badge--<?= htmlspecialchars($plano['status']) ?>"><?= htmlspecialchars($plano['status']) ?></span>
                <span class="tag tag--origem"><?= $plano['origem'] === 'ia' ? 'gerado por IA' : 'manual' ?></span>
                <?php if (!empty($plano['duracao'])): ?>
                    <span class="tag"><?= htmlspecialchars($plano['duracao']) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($plano['status'] !== 'aprovado'): ?>
            <form method="post" action="/planos/<?= (int) $plano['id'] ?>/aprovar">
                <button type="submit" class="btn btn--primary">✓ Aprovar</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($plano['origem'] === 'ia' && $plano['status'] === 'rascunho'): ?>
        <div class="notice">Rascunho gerado por IA. <strong>Revise e ajuste</strong> antes de aprovar — você é responsável pelo plano final.</div>
    <?php endif; ?>

    <form method="post" action="/planos/<?= (int) $plano['id'] ?>" class="form">
        <div class="form--inline">
            <label style="flex:1 1 auto;">Título
                <input type="text" name="titulo" value="<?= htmlspecialchars($plano['titulo']) ?>" required>
            </label>
            <label style="flex:0 0 220px;">Duração
                <input type="text" name="duracao" value="<?= htmlspecialchars($plano['duracao'] ?? '') ?>">
            </label>
        </div>
        <label>Plano <span class="muted">(markdown)</span>
            <textarea name="corpo" rows="26" class="mono"><?= htmlspecialchars($plano['corpo'] ?? '') ?></textarea>
        </label>
        <div class="actions">
            <button type="submit" class="btn btn--primary">Salvar alterações</button>
            <a href="/modulos/<?= (int) $plano['modulo_id'] ?>" class="btn btn--ghost">Voltar ao módulo</a>
        </div>
    </form>

    <form method="post" action="/planos/<?= (int) $plano['id'] ?>/excluir"
          onsubmit="return confirm('Excluir este plano de aula?');" style="margin-top:12px;">
        <button type="submit" class="btn btn--danger">Excluir plano</button>
    </form>
