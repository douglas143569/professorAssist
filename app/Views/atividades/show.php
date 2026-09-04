<?php
    $formatos = ['individual' => 'Individual', 'grupo' => 'Em grupo', 'discussao' => 'Discussão', 'pratica' => 'Prática', 'projeto' => 'Projeto', 'jogo' => 'Jogo'];
?>
    <p class="breadcrumb">
        <a href="/modulos/<?= (int) $atividade['modulo_id'] ?>"><?= htmlspecialchars($atividade['modulo_titulo']) ?></a> /
        Atividade
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($atividade['titulo']) ?></h1>
            <p class="muted">
                <span class="badge badge--<?= htmlspecialchars($atividade['status']) ?>"><?= htmlspecialchars($atividade['status']) ?></span>
                <span class="tag tag--origem"><?= $atividade['origem'] === 'ia' ? 'sugerida por IA' : 'manual' ?></span>
                <?php if (!empty($atividade['duracao'])): ?><span class="tag"><?= htmlspecialchars($atividade['duracao']) ?></span><?php endif; ?>
            </p>
        </div>
        <?php if ($atividade['status'] !== 'aprovado'): ?>
            <form method="post" action="/atividades/<?= (int) $atividade['id'] ?>/aprovar">
            <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--primary">✓ Aprovar</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($atividade['origem'] === 'ia' && $atividade['status'] === 'rascunho'): ?>
        <div class="notice">Sugestão gerada por IA. <strong>Revise e ajuste</strong> antes de aprovar.</div>
    <?php endif; ?>

    <form method="post" action="/atividades/<?= (int) $atividade['id'] ?>" class="form">
            <?= \App\Services\Csrf::campo() ?>
        <label>Título
            <input type="text" name="titulo" value="<?= htmlspecialchars($atividade['titulo']) ?>" required>
        </label>
        <div class="form--inline">
            <label style="flex:0 1 220px;">Formato
                <select name="formato">
                    <option value="">—</option>
                    <?php foreach ($formatos as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($atividade['formato'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="flex:0 1 180px;">Duração
                <input type="text" name="duracao" value="<?= htmlspecialchars($atividade['duracao'] ?? '') ?>">
            </label>
        </div>
        <label>Descrição <span class="muted">(como aplicar)</span>
            <textarea name="descricao" rows="10"><?= htmlspecialchars($atividade['descricao'] ?? '') ?></textarea>
        </label>
        <div class="actions">
            <button type="submit" class="btn btn--primary">Salvar alterações</button>
            <a href="/modulos/<?= (int) $atividade['modulo_id'] ?>" class="btn btn--ghost">Voltar ao módulo</a>
        </div>
    </form>

    <form method="post" action="/atividades/<?= (int) $atividade['id'] ?>/excluir"
          onsubmit="return confirm('Excluir esta atividade?');" style="margin-top:12px;">
            <?= \App\Services\Csrf::campo() ?>
        <button type="submit" class="btn btn--danger">Excluir atividade</button>
    </form>
