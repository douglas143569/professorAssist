    <p class="breadcrumb">
        <a href="/turmas">Turmas</a> /
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
        <div class="actions no-print">
            <button type="button" class="btn btn--ghost" onclick="imprimirConteudo()">🖨 Imprimir</button>
            <button type="button" class="btn btn--ghost" onclick="imprimirConteudo()" title="Na janela de impressão, escolha 'Salvar como PDF'">⬇ Salvar PDF</button>
            <?php if ($conteudo['status'] !== 'aprovado'): ?>
                <form method="post" action="/conteudos/<?= (int) $conteudo['id'] ?>/aprovar">
                <?= \App\Services\Csrf::campo() ?>
                    <button type="submit" class="btn btn--primary">✓ Aprovar</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // O navegador oferece "Salvar como PDF" na janela de impressao; o titulo vira o nome do arquivo.
        function imprimirConteudo() {
            var antes = document.title;
            document.title = <?= json_encode((string) $conteudo['titulo'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
            window.print();
            document.title = antes;
        }
    </script>

    <?php if ($conteudo['origem'] === 'ia' && $conteudo['status'] === 'rascunho'): ?>
        <div class="notice no-print">
            Rascunho gerado por IA. <strong>Revise e ajuste</strong> antes de aprovar — você é responsável pelo conteúdo final.
        </div>
    <?php endif; ?>

    <div class="prosa-card">
        <div class="prosa"><?= \App\Services\Markdown::render($conteudo['corpo'] ?? '') ?></div>
    </div>

    <details class="bloco-toggle no-print" style="margin-top:16px;">
        <summary>
            <span class="bloco-toggle__titulo">Editar texto</span>
            <span class="bloco-toggle__acao">abrir editor</span>
        </summary>
        <div class="bloco-toggle__corpo">
            <form method="post" action="/conteudos/<?= (int) $conteudo['id'] ?>" class="form">
                    <?= \App\Services\Csrf::campo() ?>
                <label>Título
                    <input type="text" name="titulo" value="<?= htmlspecialchars($conteudo['titulo']) ?>" required>
                </label>
                <label>Texto <span class="muted">(markdown)</span>
                    <textarea name="corpo" rows="24" class="mono"><?= htmlspecialchars($conteudo['corpo'] ?? '') ?></textarea>
                </label>
                <div class="actions">
                    <button type="submit" class="btn btn--primary">Salvar alterações</button>
                    <a href="/modulos/<?= (int) $conteudo['modulo_id'] ?>" class="btn btn--ghost">Voltar ao módulo</a>
                </div>
            </form>
        </div>
    </details>
