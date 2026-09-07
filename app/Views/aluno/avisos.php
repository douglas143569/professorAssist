<div class="page-header"><h1>Mural de avisos</h1></div>

<?php if (empty($avisos)): ?>
    <div class="card card--empty"><p>Nenhum aviso publicado.</p></div>
<?php else: ?>
    <?php foreach ($avisos as $a): ?>
        <div class="card">
            <p class="muted" style="margin-bottom:4px;">
                <span class="tag"><?= htmlspecialchars($a['turma_nome']) ?></span>
                <?php if (!empty($a['fixado'])): ?><span class="badge badge--entregue">Fixado</span><?php endif; ?>
                <small><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></small>
            </p>
            <h3><?= htmlspecialchars($a['titulo']) ?></h3>
            <p style="white-space:pre-wrap;"><?= htmlspecialchars($a['corpo']) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
