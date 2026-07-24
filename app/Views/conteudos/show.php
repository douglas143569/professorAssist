    <p class="breadcrumb">
        <a href="/disciplinas">Disciplinas</a> /
        <a href="/modulos/<?= (int) $conteudo['modulo_id'] ?>"><?= htmlspecialchars($conteudo['modulo_titulo']) ?></a> /
        Conteúdo
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($conteudo['titulo']) ?></h1>
            <p class="muted">
                <span class="badge badge--<?= htmlspecialchars($conteudo['status']) ?>"><?= htmlspecialchars($conteudo['status']) ?></span>
                <span class="tag tag--origem"><?= $conteudo['origem'] === 'ia' ? 'gerado por IA' : 'manual' ?></span>
            </p>
        </div>
        <?php if ($conteudo['status'] !== 'aprovado'): ?>
            <form method="post" action="/conteudos/<?= (int) $conteudo['id'] ?>/aprovar">
                <button type="submit" class="btn btn--primary">✓ Aprovar</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($conteudo['origem'] === 'ia' && $conteudo['status'] === 'rascunho'): ?>
        <div class="notice">
            Rascunho gerado por IA. <strong>Revise e ajuste</strong> antes de aprovar — você é responsável pelo conteúdo final.
        </div>
    <?php endif; ?>

    <form method="post" action="/conteudos/<?= (int) $conteudo['id'] ?>" class="form">
        <label>Título
            <input type="text" name="titulo" value="<?= htmlspecialchars($conteudo['titulo']) ?>" required>
        </label>
        <label>Conteúdo <span class="muted">(markdown)</span>
            <textarea name="corpo" rows="24" class="mono"><?= htmlspecialchars($conteudo['corpo'] ?? '') ?></textarea>
        </label>
        <div class="actions">
            <button type="submit" class="btn btn--primary">Salvar alterações</button>
            <a href="/modulos/<?= (int) $conteudo['modulo_id'] ?>" class="btn btn--ghost">Voltar ao módulo</a>
        </div>
    </form>
