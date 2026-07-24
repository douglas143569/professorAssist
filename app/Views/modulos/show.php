    <p class="breadcrumb">
        <a href="/disciplinas">Disciplinas</a> /
        <a href="/disciplinas/<?= (int) $modulo['disciplina_id'] ?>"><?= htmlspecialchars($modulo['disciplina_nome']) ?></a> /
        <?= htmlspecialchars($modulo['titulo']) ?>
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($modulo['titulo']) ?></h1>
            <p class="muted">
                <span class="tag"><?= htmlspecialchars($modulo['disciplina_etapa']) ?></span>
                <?php if (!empty($modulo['codigos_bncc'])): ?>
                    <span class="tag tag--bncc"><?= htmlspecialchars($modulo['codigos_bncc']) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/conteudos/gerar" class="js-ai">
            <button type="submit" class="btn btn--primary" data-loading="Gerando conteúdo…">✦ Gerar conteúdo com IA</button>
        </form>
    </div>

    <?php if (!empty($modulo['objetivos'])): ?>
        <div class="card">
            <h3>Objetivos</h3>
            <p><?= nl2br(htmlspecialchars($modulo['objetivos'])) ?></p>
        </div>
    <?php endif; ?>

    <h2>Plano de aula</h2>
    <div class="card">
        <h3>Gerar plano de aula com IA</h3>
        <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/planos/gerar" class="form form--inline js-ai">
            <label>Duração
                <select name="duracao">
                    <option value="1 aula de 50 min">1 aula de 50 min</option>
                    <option value="2 aulas de 50 min">2 aulas de 50 min</option>
                    <option value="1 aula de 100 min">1 aula de 100 min</option>
                    <option value="Sequência de 3 aulas">Sequência de 3 aulas</option>
                </select>
            </label>
            <button type="submit" class="btn btn--primary" data-loading="Gerando plano…">✦ Gerar plano</button>
        </form>
    </div>

    <?php if (empty($planos)): ?>
        <div class="card card--empty">
            <p>Nenhum plano de aula ainda. Gere um com IA a partir dos objetivos e da BNCC do módulo.</p>
        </div>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($planos as $p): ?>
                <li>
                    <a href="/planos/<?= (int) $p['id'] ?>" class="list__main"><?= htmlspecialchars($p['titulo']) ?></a>
                    <?php if (!empty($p['duracao'])): ?>
                        <span class="tag tag--origem"><?= htmlspecialchars($p['duracao']) ?></span>
                    <?php endif; ?>
                    <span class="badge badge--<?= htmlspecialchars($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span>
                    <span class="tag tag--origem"><?= $p['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h2>Conteúdos</h2>
    <?php if (empty($conteudos)): ?>
        <div class="card card--empty">
            <p>Nenhum conteúdo ainda. Clique em <strong>Gerar conteúdo com IA</strong> para criar um rascunho.</p>
        </div>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($conteudos as $c): ?>
                <li>
                    <a href="/conteudos/<?= (int) $c['id'] ?>"><strong><?= htmlspecialchars($c['titulo']) ?></strong></a>
                    <span class="badge badge--<?= htmlspecialchars($c['status']) ?>"><?= htmlspecialchars($c['status']) ?></span>
                    <span class="tag tag--origem"><?= $c['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php
        $formatosLabel = ['individual' => 'Individual', 'grupo' => 'Em grupo', 'discussao' => 'Discussão', 'pratica' => 'Prática', 'projeto' => 'Projeto', 'jogo' => 'Jogo'];
    ?>
    <h2 style="margin-top:32px;">Atividades sugeridas</h2>
    <div class="card">
        <h3>Sugerir atividades com IA</h3>
        <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/atividades/gerar" class="form form--inline js-ai">
            <label>Quantidade
                <input type="number" name="quantidade" value="4" min="1" max="10">
            </label>
            <label style="flex:1 1 260px;">Foco <span class="muted">(opcional)</span>
                <input type="text" name="foco" placeholder="Ex: atividades em grupo, lúdicas…">
            </label>
            <button type="submit" class="btn btn--primary" data-loading="Sugerindo…">✦ Sugerir</button>
        </form>
    </div>

    <?php if (empty($atividades)): ?>
        <div class="card card--empty">
            <p>Nenhuma atividade ainda. Peça sugestões à IA alinhadas ao tema e ao nível.</p>
        </div>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($atividades as $a): ?>
                <li>
                    <a href="/atividades/<?= (int) $a['id'] ?>" class="list__main"><?= htmlspecialchars($a['titulo']) ?></a>
                    <?php if (!empty($a['formato'])): ?>
                        <span class="tag"><?= htmlspecialchars($formatosLabel[$a['formato']] ?? $a['formato']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($a['duracao'])): ?>
                        <span class="tag tag--origem"><?= htmlspecialchars($a['duracao']) ?></span>
                    <?php endif; ?>
                    <span class="badge badge--<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span>
                    <span class="tag tag--origem"><?= $a['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php
        $tiposLabel = ['multipla_escolha' => 'Múltipla escolha', 'verdadeiro_falso' => 'V ou F', 'dissertativa' => 'Dissertativa'];
    ?>
    <div class="page-header" style="margin-top:32px;">
        <h2>Questões</h2>
        <div class="actions">
            <a href="/modulos/<?= (int) $modulo['id'] ?>/questoes/nova" class="btn btn--ghost">+ Adicionar manual</a>
            <a href="/disciplinas/<?= (int) $modulo['disciplina_id'] ?>/questoes" class="btn btn--ghost">Banco da disciplina</a>
        </div>
    </div>

    <div class="card">
        <h3>Gerar questões com IA</h3>
        <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/questoes/gerar" class="form form--inline js-ai">
            <label>Quantidade
                <input type="number" name="quantidade" value="5" min="1" max="15">
            </label>
            <label>Dificuldade
                <select name="dificuldade">
                    <option value="facil">Fácil</option>
                    <option value="media" selected>Média</option>
                    <option value="dificil">Difícil</option>
                </select>
            </label>
            <label>Tipo
                <select name="tipo">
                    <option value="multipla_escolha">Múltipla escolha</option>
                    <option value="verdadeiro_falso">Verdadeiro / Falso</option>
                    <option value="dissertativa">Dissertativa</option>
                    <option value="mista">Mistas</option>
                </select>
            </label>
            <button type="submit" class="btn btn--primary" data-loading="Gerando…">✦ Gerar</button>
        </form>
    </div>

    <?php if (empty($questoes)): ?>
        <div class="card card--empty">
            <p>Nenhuma questão ainda. Gere com IA ou adicione manualmente.</p>
        </div>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($questoes as $q): ?>
                <li>
                    <a href="/questoes/<?= (int) $q['id'] ?>" class="list__main">
                        <?= htmlspecialchars(mb_strimwidth($q['enunciado'], 0, 90, '…')) ?>
                    </a>
                    <span class="tag"><?= htmlspecialchars($tiposLabel[$q['tipo']] ?? $q['tipo']) ?></span>
                    <span class="badge badge--dif-<?= htmlspecialchars($q['dificuldade']) ?>"><?= htmlspecialchars($q['dificuldade']) ?></span>
                    <span class="badge badge--<?= htmlspecialchars($q['status']) ?>"><?= htmlspecialchars($q['status']) ?></span>
                    <span class="tag tag--origem"><?= $q['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
