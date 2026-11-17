    <div class="page-header">
        <div>
            <h1>Banco de relatórios</h1>
            <p class="muted">Relatórios individuais gerados, guardados só na sua conta.</p>
        </div>
        <a href="/creche/relatorios" class="btn btn--primary">✦ Novo relatório</a>
    </div>

    <div class="subnav">
        <a href="/creche" class="subnav__item">Atividades</a>
        <a href="/creche/cronograma" class="subnav__item">Cronograma</a>
        <a href="/creche/pacotes" class="subnav__item">Pacotes</a>
        <a href="/creche/relatorios" class="subnav__item">Relatórios dos alunos</a>
        <a href="/creche/relatorios/banco" class="subnav__item is-active">Banco de relatórios</a>
    </div>

    <form method="get" action="/creche/relatorios/banco" class="form--inline" style="margin-bottom:16px;">
        <label style="flex:1 1 280px;">Buscar
            <input type="search" name="q" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome do aluno, turma ou período…">
        </label>
        <button type="submit" class="btn btn--ghost">Buscar</button>
    </form>

    <?php if (empty($relatorios)): ?>
        <div class="card card--empty">
            <p><?= $busca !== '' ? 'Nenhum relatório encontrado para essa busca.' : 'Nenhum relatório salvo ainda. Gere o primeiro em “Relatórios dos alunos”.' ?></p>
        </div>
    <?php else: ?>
        <div class="pkg-grid">
            <?php foreach ($relatorios as $i => $r):
                $c = $i % 5;
                $partes = preg_split('/\s+/', trim($r['aluno_nome']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $ini = mb_strtoupper(mb_substr($partes[0] ?? '?', 0, 1) . (count($partes) > 1 ? mb_substr(end($partes), 0, 1) : ''));
            ?>
                <a class="pkg-card pkg-card--<?= $c ?>" href="/creche/relatorios/<?= (int) $r['id'] ?>" style="--i:<?= $i % 12 ?>">
                    <span class="pkg-card__top">
                        <span class="pkg-avatar" aria-hidden="true"><?= htmlspecialchars($ini) ?></span>
                        <span class="pkg-card__tema"><?= htmlspecialchars($r['aluno_nome']) ?></span>
                        <span class="pkg-card__faixa"><?= htmlspecialchars($r['turma']) ?> · <?= htmlspecialchars($r['idade']) ?></span>
                    </span>
                    <span class="pkg-card__foot">
                        <span><strong><?= htmlspecialchars($r['periodo']) ?></strong></span>
                        <small>Criado em <?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                        <b class="pkg-card__cta">Abrir relatório ›</b>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
