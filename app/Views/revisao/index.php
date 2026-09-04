    <div class="page-header">
        <div>
            <h1>Revisar</h1>
            <p class="muted">
                Tudo que a IA rascunhou e ainda espera você. Nada vira prova ou folha impressa
                antes de passar por aqui.
            </p>
        </div>
    </div>

    <?php if ($contagem['total'] === 0): ?>
        <div class="card card--empty">
            <p>
                <strong>Nada pendente.</strong><br>
                Quando você gerar conteúdo, planos, questões ou atividades com IA,
                eles aparecem aqui para revisar e aprovar.
            </p>
        </div>
    <?php else: ?>

        <div class="filtros-revisao">
            <a href="/revisar" class="chip <?= $tipoAtivo === null ? 'is-active' : '' ?>">
                Tudo <span><?= $contagem['total'] ?></span>
            </a>
            <?php foreach ($tipos as $chave => $info): ?>
                <?php if (($contagem['por_tipo'][$chave] ?? 0) > 0): ?>
                    <a href="/revisar?tipo=<?= urlencode($chave) ?>"
                       class="chip <?= $tipoAtivo === $chave ? 'is-active' : '' ?>">
                        <?= htmlspecialchars($info['rotulo']) ?>
                        <span><?= (int) $contagem['por_tipo'][$chave] ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <form method="post" action="/revisar/aprovar" id="form-revisao">
            <?= \App\Services\Csrf::campo() ?>
            <input type="hidden" name="voltar" value="/revisar<?= $tipoAtivo ? '?tipo=' . urlencode($tipoAtivo) : '' ?>">

            <div class="barra-revisao">
                <label class="marcar-todos">
                    <input type="checkbox" id="marcar-todos">
                    Marcar todos os <?= count($itens) ?> desta lista
                </label>
                <button type="submit" class="btn btn--primary">✓ Aprovar selecionados</button>
            </div>

            <ul class="lista-revisao">
                <?php foreach ($itens as $item): ?>
                    <?php $info = $tipos[$item['tipo']]; ?>
                    <li>
                        <label class="revisao-item">
                            <input type="checkbox" name="itens[]"
                                   value="<?= htmlspecialchars($item['tipo'] . ':' . $item['id']) ?>">
                            <span class="revisao-item__corpo">
                                <span class="revisao-item__titulo"><?= htmlspecialchars($item['titulo']) ?></span>
                                <span class="revisao-item__meta">
                                    <span class="tag"><?= htmlspecialchars($info['singular']) ?></span>
                                    <?php if (!empty($item['contexto'])): ?>
                                        <?= htmlspecialchars($item['contexto']) ?>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </label>
                        <a href="<?= htmlspecialchars($info['url'] . $item['id']) ?>" class="btn btn--ghost btn--sm">
                            Abrir ↗
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="barra-revisao barra-revisao--rodape">
                <button type="submit" class="btn btn--primary">✓ Aprovar selecionados</button>
            </div>
        </form>

        <p class="muted" style="font-size:0.84rem;">
            Aprovar libera o item para uso — questões aprovadas passam a valer para o
            <a href="/provas">gerador de provas</a>. Abra o item se quiser ler ou editar antes.
        </p>
    <?php endif; ?>

    <script>
        (function () {
            var todos = document.getElementById('marcar-todos');
            if (!todos) return;
            var caixas = document.querySelectorAll('#form-revisao input[name="itens[]"]');
            todos.addEventListener('change', function () {
                caixas.forEach(function (c) { c.checked = todos.checked; });
            });
        })();
    </script>
