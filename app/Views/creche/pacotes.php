    <div class="page-header">
        <div>
            <h1>Creche · Educação Infantil</h1>
            <p class="muted">Pacotes temáticos com atividades variadas.</p>
        </div>
    </div>

    <div class="subnav">
        <a href="/creche" class="subnav__item">Atividades</a>
        <a href="/creche/cronograma" class="subnav__item">Cronograma</a>
        <a href="/creche/pacotes" class="subnav__item is-active">Pacotes</a>
    </div>

    <div class="card">
        <h3>Criar pacote de atividades com IA</h3>
        <p class="muted" style="margin-top:-4px;">Digite um tema e a IA monta um pacote completo com atividades variadas (memória, pintura, música, movimento…).</p>
        <form method="post" action="/creche/pacotes/criar" class="form js-ai">
            <?= \App\Services\Csrf::campo() ?>
            <label>Tema do pacote
                <input type="text" name="tema" placeholder="Ex: fundo do mar, dinossauros, outono…" required>
            </label>
            <div class="form--inline">
                <label style="flex:1 1 220px;">Faixa etária
                    <select name="faixa_etaria" required>
                        <?php foreach ($faixas as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label style="flex:0 1 150px;">Nº de atividades
                    <input type="number" name="quantidade" value="6" min="3" max="12">
                </label>
            </div>
            <button type="submit" class="btn btn--primary" data-loading="Criando o pacote…">✦ Criar</button>
        </form>
    </div>

    <h2>Meus pacotes</h2>
    <?php if (empty($pacotes)): ?>
        <div class="card card--empty"><p>Nenhum pacote ainda. Crie o primeiro acima informando um tema.</p></div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($pacotes as $p): ?>
                <a class="card card--link" href="/creche/pacotes/<?= (int) $p['id'] ?>">
                    <span class="tag"><?= htmlspecialchars($p['faixa_etaria']) ?></span>
                    <h3><?= htmlspecialchars($p['tema']) ?></h3>
                    <p class="muted"><?= (int) $p['n_itens'] ?> atividade(s)</p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
