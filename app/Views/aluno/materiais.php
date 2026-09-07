<div class="page-header"><h1>Materiais de apoio</h1></div>

<?php if (empty($materiais)): ?>
    <div class="card card--empty"><p>Nenhum material publicado pelo professor ainda.</p></div>
<?php else: ?>
    <div class="materiais-lista">
        <?php foreach ($materiais as $m): ?>
            <div class="card">
                <p class="muted" style="margin-bottom:4px;">
                    <span class="tag"><?= htmlspecialchars($m['turma_nome']) ?></span>
                    <?php if (!empty($m['publicado_em'])): ?>
                        <small><?= date('d/m/Y', strtotime($m['publicado_em'])) ?></small>
                    <?php endif; ?>
                </p>
                <h3><?= htmlspecialchars($m['titulo']) ?></h3>
                <div class="prosa"><?= \App\Services\Markdown::render($m['corpo']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
