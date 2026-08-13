<?php
    $tipos = ['multipla_escolha' => 'Múltipla escolha', 'verdadeiro_falso' => 'V ou F', 'dissertativa' => 'Dissertativa'];
    $sel = fn(string $campo, string $valor): string => ($filtros[$campo] ?? '') === $valor ? 'selected' : '';
?>
    <p class="breadcrumb">
        <a href="/turmas">Turmas</a> /
        <a href="/disciplinas/<?= (int) $disciplina['id'] ?>"><?= htmlspecialchars($disciplina['nome']) ?></a> /
        Banco de questões
    </p>

    <div class="page-header">
        <h1>Banco de questões</h1>
        <p class="muted"><?= count($questoes) ?> questão(ões)</p>
    </div>

    <form method="get" action="/disciplinas/<?= (int) $disciplina['id'] ?>/questoes" class="card form--inline form--filtros">
        <label>Dificuldade
            <select name="dificuldade">
                <option value="">Todas</option>
                <option value="facil" <?= $sel('dificuldade', 'facil') ?>>Fácil</option>
                <option value="media" <?= $sel('dificuldade', 'media') ?>>Média</option>
                <option value="dificil" <?= $sel('dificuldade', 'dificil') ?>>Difícil</option>
            </select>
        </label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <option value="rascunho" <?= $sel('status', 'rascunho') ?>>Rascunho</option>
                <option value="aprovado" <?= $sel('status', 'aprovado') ?>>Aprovado</option>
            </select>
        </label>
        <label>Tipo
            <select name="tipo">
                <option value="">Todos</option>
                <?php foreach ($tipos as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= $sel('tipo', $val) ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>BNCC
            <input type="text" name="bncc" value="<?= htmlspecialchars($filtros['bncc'] ?? '') ?>" placeholder="Ex: EF06MA">
        </label>
        <button type="submit" class="btn btn--primary">Filtrar</button>
    </form>

    <?php if (empty($questoes)): ?>
        <div class="card card--empty"><p>Nenhuma questão com esses filtros.</p></div>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($questoes as $q): ?>
                <li>
                    <a href="/questoes/<?= (int) $q['id'] ?>" class="list__main">
                        <?= htmlspecialchars(mb_strimwidth($q['enunciado'], 0, 90, '…')) ?>
                    </a>
                    <?php if (!empty($q['modulo_titulo'])): ?>
                        <span class="tag tag--origem"><?= htmlspecialchars($q['modulo_titulo']) ?></span>
                    <?php endif; ?>
                    <span class="tag"><?= htmlspecialchars($tipos[$q['tipo']] ?? $q['tipo']) ?></span>
                    <span class="badge badge--dif-<?= htmlspecialchars($q['dificuldade']) ?>"><?= htmlspecialchars($q['dificuldade']) ?></span>
                    <span class="badge badge--<?= htmlspecialchars($q['status']) ?>"><?= htmlspecialchars($q['status']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
