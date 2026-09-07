<?php
    $tipo = $tarefa['tipo_entrega'];
    $aceitaTexto = in_array($tipo, ['texto', 'ambos'], true);
    $aceitaArquivo = in_array($tipo, ['arquivo', 'ambos'], true);
    $status = $entrega['status'] ?? 'pendente';
    $corrigida = $status === 'corrigida';

    $prazo = null;
    if (!empty($tarefa['entrega_ate'])) {
        $ts = strtotime($tarefa['entrega_ate']);
        $prazo = $ts ? date('d/m/Y H:i', $ts) : null;
        $venceu = $ts && $ts < time();
    }
?>
<p class="breadcrumb"><a href="/aluno/tarefas">Minhas tarefas</a> / <?= htmlspecialchars($tarefa['titulo']) ?></p>

<div class="page-header">
    <div>
        <h1><?= htmlspecialchars($tarefa['titulo']) ?></h1>
        <p class="muted">
            <span class="tag"><?= htmlspecialchars($tarefa['turma_nome']) ?></span>
            <?php if ($prazo): ?>
                <span class="badge <?= !empty($venceu) && !$corrigida ? 'badge--pendente' : 'badge--entregue' ?>">
                    <?= !empty($venceu) ? 'Venceu em ' : 'Entrega até ' ?><?= $prazo ?>
                </span>
            <?php else: ?>
                <span class="tag">sem prazo</span>
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="split">
    <section>
        <?php if (!empty($tarefa['descricao'])): ?>
            <div class="card">
                <h3>Enunciado</h3>
                <p style="white-space:pre-wrap;"><?= htmlspecialchars($tarefa['descricao']) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($corrigida): ?>
            <div class="card">
                <h3>Correção do professor</h3>
                <p>Nota: <strong style="font-size:1.3rem;"><?= number_format((float) $entrega['nota'], 1, ',', '') ?></strong></p>
                <?php if (!empty($entrega['feedback'])): ?>
                    <p class="muted" style="white-space:pre-wrap;"><?= htmlspecialchars($entrega['feedback']) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <aside>
        <div class="card">
            <h3><?= $status === 'pendente' ? 'Enviar entrega' : 'Sua entrega' ?></h3>

            <?php if ($status !== 'pendente'): ?>
                <p class="muted">
                    Enviada em <?= date('d/m/Y H:i', strtotime($entrega['entregue_em'])) ?>.
                    <?= $corrigida ? 'Já corrigida — não é possível reenviar.' : 'Você ainda pode reenviar até a correção.' ?>
                </p>
                <?php if (!empty($entrega['resposta_texto'])): ?>
                    <p style="white-space:pre-wrap;background:var(--surface-2);padding:10px;border-radius:8px;">
                        <?= htmlspecialchars($entrega['resposta_texto']) ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($entrega['arquivo_path'])): ?>
                    <p><a href="/aluno/entregas/<?= (int) $entrega['id'] ?>/arquivo">
                        📎 <?= htmlspecialchars($entrega['arquivo_nome'] ?: 'arquivo enviado') ?>
                    </a></p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!$corrigida): ?>
                <form method="post" action="/aluno/tarefas/<?= (int) $tarefa['id'] ?>/entregar"
                      enctype="multipart/form-data" class="form js-enviar" style="margin-top:12px;">
                    <?= \App\Services\Csrf::campo() ?>

                    <?php if ($aceitaTexto): ?>
                        <label>Sua resposta
                            <textarea name="resposta_texto" rows="6" placeholder="Escreva sua resposta aqui…"><?= htmlspecialchars($entrega['resposta_texto'] ?? '') ?></textarea>
                        </label>
                    <?php endif; ?>

                    <?php if ($aceitaArquivo): ?>
                        <label>Anexar arquivo
                            <input type="file" name="arquivo"
                                   accept=".pdf,.doc,.docx,.odt,.jpg,.jpeg,.png,.zip">
                            <small class="campo-dica">PDF, Word, imagem ou zip. Até <?= (int) ($_ENV['UPLOAD_MAX_MB'] ?? 10) ?> MB.</small>
                        </label>
                    <?php endif; ?>

                    <button type="submit" class="btn btn--primary"><?= $status === 'pendente' ? 'Enviar' : 'Reenviar' ?></button>
                </form>
            <?php endif; ?>
        </div>
    </aside>
</div>
