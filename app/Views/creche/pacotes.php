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
        <a href="/creche/relatorios" class="subnav__item">Relatórios dos alunos</a>
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
                <label style="flex:1 1 220px;">Campo de experiência (BNCC)
                    <select name="campo_experiencia">
                        <option value="">A IA escolhe conforme o tema</option>
                        <?php foreach ($campos as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
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
        <?php
            // Cada card ganha uma cor/icone da paleta, em rodizio.
            $icones = [
                '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5Z"/><path d="M9 7h6M9 11h6"/>',
                '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
                '<path d="M12 21s-8-5.2-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.8-8 11-8 11Z"/>',
                '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z"/>',
                '<circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9.5v.01M15 9.5v.01"/>',
            ];
        ?>
        <div class="pkg-grid">
            <?php foreach ($pacotes as $i => $p): $c = $i % 5; ?>
                <a class="pkg-card pkg-card--<?= $c ?>" href="/creche/pacotes/<?= (int) $p['id'] ?>" style="--i:<?= $i ?>">
                    <span class="pkg-card__top">
                        <span class="pkg-card__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="46" height="46" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><?= $icones[$c] ?></svg></span>
                        <span class="pkg-card__tema"><?= htmlspecialchars($p['tema']) ?></span>
                        <span class="pkg-card__dots" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
                        <span class="pkg-card__faixa"><?= htmlspecialchars($p['faixa_etaria']) ?></span>
                    </span>
                    <span class="pkg-card__foot">
                        <span><strong><?= (int) $p['n_itens'] ?></strong> atividade(s)</span>
                        <?php if (!empty($p['campo_experiencia'])): ?>
                            <small><?= htmlspecialchars($p['campo_experiencia']) ?></small>
                        <?php endif; ?>
                        <b class="pkg-card__cta">Abrir pacote ›</b>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
