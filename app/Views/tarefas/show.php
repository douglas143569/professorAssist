<?php
    $badge = [
        'pendente' => ['badge--pendente', 'Pendente'],
        'entregue' => ['badge--entregue', 'Entregue'],
        'corrigida' => ['badge--corrigida', 'Corrigida'],
    ];
    $prazoInput = '';
    if (!empty($tarefa['entrega_ate'])) {
        $ts = strtotime($tarefa['entrega_ate']);
        $prazoInput = $ts ? date('Y-m-d\TH:i', $ts) : '';
    }
    $totAlunos = count($entregas);
    $totEntregues = count(array_filter($entregas, fn ($e) => $e['status'] !== 'pendente'));
    $totCorrigidas = count(array_filter($entregas, fn ($e) => $e['status'] === 'corrigida'));
?>
<p class="breadcrumb">
    <a href="/escolas">Escolas</a> /
    <a href="/escolas/<?= (int) $tarefa['escola_id'] ?>"><?= htmlspecialchars($tarefa['escola_nome'] ?? 'Escola') ?></a> /
    <a href="/turmas/<?= (int) $tarefa['turma_id'] ?>"><?= htmlspecialchars($tarefa['turma_nome']) ?></a> /
    <?= htmlspecialchars($tarefa['titulo']) ?>
</p>

<div class="page-header">
    <div>
        <h1><?= htmlspecialchars($tarefa['titulo']) ?></h1>
        <p class="muted">
            <?php if ((int) $tarefa['publicada'] === 1): ?>
                <span class="badge badge--corrigida">Publicada</span>
            <?php else: ?>
                <span class="badge badge--rascunho">Rascunho — os alunos ainda não veem</span>
            <?php endif; ?>
            <span class="tag"><?= htmlspecialchars($tiposEntrega[$tarefa['tipo_entrega']] ?? $tarefa['tipo_entrega']) ?></span>
        </p>
    </div>
    <div style="display:flex;gap:8px;align-items:flex-start;">
        <?php if ((int) $tarefa['publicada'] === 0): ?>
            <form method="post" action="/tarefas/<?= (int) $tarefa['id'] ?>/publicar">
                <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--primary">Publicar</button>
            </form>
        <?php endif; ?>
        <form method="post" action="/tarefas/<?= (int) $tarefa['id'] ?>/excluir"
              onsubmit="return confirm('Excluir a tarefa e todas as entregas?');">
            <?= \App\Services\Csrf::campo() ?>
            <button type="submit" class="btn btn--danger btn--sm">Excluir</button>
        </form>
    </div>
</div>

<div class="split">
    <section>
        <h2>Entregas <span class="muted" style="font-weight:400;">(<?= $totCorrigidas ?> corrigidas · <?= $totEntregues ?>/<?= $totAlunos ?> entregaram)</span></h2>

        <?php if (empty($entregas)): ?>
            <div class="card card--empty"><p>Nenhum aluno matriculado nesta turma ainda.</p></div>
        <?php else: ?>
            <?php foreach ($entregas as $e): ?>
                <?php [$cls, $rot] = $badge[$e['status']] ?? $badge['pendente']; ?>
                <div class="card">
                    <p style="margin-bottom:6px;">
                        <span class="badge <?= $cls ?>"><?= $rot ?></span>
                        <strong><?= htmlspecialchars($e['aluno_nome']) ?></strong>
                        <?php if ($e['entregue_em']): ?>
                            <small class="muted">· <?= date('d/m/Y H:i', strtotime($e['entregue_em'])) ?></small>
                        <?php endif; ?>
                    </p>

                    <?php if ($e['status'] === 'pendente'): ?>
                        <p class="muted">Ainda não entregou.</p>
                    <?php else: ?>
                        <?php if (!empty($e['resposta_texto'])): ?>
                            <p style="white-space:pre-wrap;background:var(--surface-2);padding:10px;border-radius:8px;">
                                <?= htmlspecialchars($e['resposta_texto']) ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($e['arquivo_path'])): ?>
                            <p><a href="/tarefas/<?= (int) $tarefa['id'] ?>/entregas/<?= (int) $e['aluno_id'] ?>/arquivo">
                                📎 <?= htmlspecialchars($e['arquivo_nome'] ?: 'arquivo') ?>
                            </a></p>
                        <?php endif; ?>

                        <form method="post" action="/tarefas/<?= (int) $tarefa['id'] ?>/entregas/<?= (int) $e['aluno_id'] ?>/corrigir"
                              class="form form--inline" style="margin-top:8px;">
                            <?= \App\Services\Csrf::campo() ?>
                            <label style="max-width:110px;">Nota
                                <input type="text" name="nota" inputmode="decimal"
                                       value="<?= $e['nota'] !== null ? htmlspecialchars(number_format((float) $e['nota'], 1, ',', '')) : '' ?>"
                                       placeholder="0–10" required>
                            </label>
                            <label style="flex:1;">Feedback (opcional)
                                <input type="text" name="feedback" value="<?= htmlspecialchars($e['feedback'] ?? '') ?>"
                                       placeholder="Comentário para o aluno">
                            </label>
                            <button type="submit" class="btn btn--primary btn--sm">
                                <?= $e['status'] === 'corrigida' ? 'Atualizar nota' : 'Lançar nota' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <aside>
        <div class="card">
            <h3>Editar tarefa</h3>
            <form method="post" action="/tarefas/<?= (int) $tarefa['id'] ?>" class="form">
                <?= \App\Services\Csrf::campo() ?>
                <label>Título
                    <input type="text" name="titulo" value="<?= htmlspecialchars($tarefa['titulo']) ?>" required>
                </label>
                <label>Enunciado / instruções
                    <textarea name="descricao" rows="5"><?= htmlspecialchars($tarefa['descricao'] ?? '') ?></textarea>
                </label>
                <label>Tipo de entrega
                    <select name="tipo_entrega">
                        <?php foreach ($tiposEntrega as $k => $rotulo): ?>
                            <option value="<?= $k ?>" <?= $tarefa['tipo_entrega'] === $k ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Prazo de entrega (opcional)
                    <input type="datetime-local" name="entrega_ate" value="<?= htmlspecialchars($prazoInput) ?>">
                </label>
                <button type="submit" class="btn btn--primary">Salvar</button>
            </form>
        </div>
    </aside>
</div>
