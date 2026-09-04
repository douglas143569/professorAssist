<?php
    $diasSem = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $tiposLabel = ['prova' => 'Prova', 'trabalho' => 'Trabalho', 'lembrete' => 'Lembrete', 'aula' => 'Aula'];
    $fmt = function (string $d) use ($diasSem): string {
        $ts = strtotime($d);
        return $diasSem[(int) date('w', $ts)] . ', ' . date('d/m/Y', $ts);
    };
    $sel = fn(string $v): string => ($filtroTipo ?? '') === $v ? 'selected' : '';

    $card = function (array $e) use ($tiposLabel, $fmt): void {
        $done = !empty($e['concluido']);
        ?>
        <div class="ev-card ev-card--<?= htmlspecialchars($e['tipo']) ?> <?= $done ? 'ev-card--done' : '' ?>">
            <div class="ev-card__top">
                <span class="ev-card__date">
                    <?= $fmt($e['data_evento']) ?><?php if (!empty($e['hora'])): ?> · <?= substr($e['hora'], 0, 5) ?><?php endif; ?>
                </span>
                <span class="ev ev--<?= htmlspecialchars($e['tipo']) ?>"><?= $tiposLabel[$e['tipo']] ?? $e['tipo'] ?></span>
            </div>
            <h3 class="ev-card__title"><?= htmlspecialchars($e['titulo']) ?></h3>
            <?php if (!empty($e['disciplina_nome'])): ?>
                <p class="muted ev-card__disc"><?= htmlspecialchars($e['disciplina_nome']) ?></p>
            <?php endif; ?>
            <?php if (!empty($e['descricao'])): ?>
                <p class="ev-card__desc"><?= nl2br(htmlspecialchars($e['descricao'])) ?></p>
            <?php endif; ?>
            <div class="ev-card__foot">
                <form method="post" action="/eventos/<?= (int) $e['id'] ?>/concluir">
            <?= \App\Services\Csrf::campo() ?>
                    <input type="hidden" name="voltar" value="/calendario/eventos">
                    <button class="btn btn--ghost btn--sm" type="submit"><?= $done ? '↺ Reabrir' : '✓ Concluir' ?></button>
                </form>
                <form method="post" action="/eventos/<?= (int) $e['id'] ?>/excluir" onsubmit="return confirm('Excluir este evento?');">
            <?= \App\Services\Csrf::campo() ?>
                    <input type="hidden" name="voltar" value="/calendario/eventos">
                    <button class="btn btn--danger btn--sm" type="submit">Excluir</button>
                </form>
            </div>
        </div>
        <?php
    };
?>
    <div class="subnav">
        <a href="/calendario" class="subnav__item">Calendário</a>
        <a href="/calendario/eventos" class="subnav__item is-active">Eventos criados</a>
    </div>

    <div class="page-header">
        <div>
            <h1>Eventos criados</h1>
            <p class="muted"><?= (int) $total ?> evento(s) no total.</p>
        </div>
        <form method="get" action="/calendario/eventos" class="form--inline">
            <label style="flex:0 1 200px;">Filtrar por tipo
                <select name="tipo" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="prova" <?= $sel('prova') ?>>Prova</option>
                    <option value="trabalho" <?= $sel('trabalho') ?>>Trabalho</option>
                    <option value="lembrete" <?= $sel('lembrete') ?>>Lembrete</option>
                    <option value="aula" <?= $sel('aula') ?>>Aula</option>
                </select>
            </label>
        </form>
    </div>

    <div class="cal-resumo">
        <span class="ev ev--prova"><?= $resumo['prova'] ?> prova(s)</span>
        <span class="ev ev--trabalho"><?= $resumo['trabalho'] ?> trabalho(s)</span>
        <span class="ev ev--lembrete"><?= $resumo['lembrete'] ?> lembrete(s)</span>
        <span class="ev ev--aula"><?= $resumo['aula'] ?> aula(s)</span>
    </div>

    <?php if ((int) $total === 0): ?>
        <div class="card card--empty">
            <p>Nenhum evento ainda. Crie eventos no <a href="/calendario">calendário</a>.</p>
        </div>
    <?php else: ?>
        <?php
        $secoes = [
            'atrasados' => ['⚠ Atrasados', 'ev-sec--atrasados'],
            'hoje' => ['📌 Hoje', 'ev-sec--hoje'],
            'proximos' => ['Próximos', ''],
            'concluidos' => ['Concluídos', 'ev-sec--done'],
        ];
        foreach ($secoes as $key => [$titulo, $cls]):
            $lista = $grupos[$key];
            if (empty($lista)) {
                continue;
            }
        ?>
            <h2 class="ev-sec <?= $cls ?>"><?= $titulo ?> <span class="ev-sec__n"><?= count($lista) ?></span></h2>
            <div class="ev-board">
                <?php foreach ($lista as $e) {
                    $card($e);
                } ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
