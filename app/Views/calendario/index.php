<?php
    $meses = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
              'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $tiposLabel = ['prova' => 'Prova', 'trabalho' => 'Trabalho', 'lembrete' => 'Lembrete', 'aula' => 'Aula'];

    $primeiro = mktime(0, 0, 0, $mes, 1, $ano);
    $offset = (int) date('w', $primeiro);        // 0=Dom
    $totalDias = (int) date('t', $primeiro);

    $hojeY = (int) date('Y'); $hojeM = (int) date('n'); $hojeD = (int) date('j');
    $hojeStr = date('Y-m-d');

    $prevM = $mes - 1; $prevA = $ano; if ($prevM < 1) { $prevM = 12; $prevA--; }
    $nextM = $mes + 1; $nextA = $ano; if ($nextM > 12) { $nextM = 1; $nextA++; }

    $celulas = $offset + $totalDias;
    $trailing = (7 - ($celulas % 7)) % 7;
?>
    <div class="page-header">
        <div>
            <h1>Calendário</h1>
            <p class="muted">Provas, trabalhos e lembretes num só lugar.</p>
        </div>
        <div class="cal__nav">
            <a class="btn btn--ghost" href="/calendario?ano=<?= $prevA ?>&mes=<?= $prevM ?>" aria-label="Mês anterior">‹</a>
            <strong style="min-width:150px;text-align:center;"><?= $meses[$mes] ?> <?= $ano ?></strong>
            <a class="btn btn--ghost" href="/calendario?ano=<?= $nextA ?>&mes=<?= $nextM ?>" aria-label="Próximo mês">›</a>
            <a class="btn btn--ghost" href="/calendario">Hoje</a>
        </div>
    </div>

    <div class="cal-resumo">
        <span class="ev ev--prova"><?= $resumo['prova'] ?> prova(s)</span>
        <span class="ev ev--trabalho"><?= $resumo['trabalho'] ?> trabalho(s)</span>
        <span class="ev ev--lembrete"><?= $resumo['lembrete'] ?> lembrete(s)</span>
        <span class="ev ev--aula"><?= $resumo['aula'] ?> aula(s)</span>
    </div>

    <div class="cal">
        <div class="cal__head">
            <?php foreach ($diasSemana as $ds): ?><span><?= $ds ?></span><?php endforeach; ?>
        </div>
        <div class="cal__grid">
            <?php for ($i = 0; $i < $offset; $i++): ?>
                <div class="cal__cell cal__cell--empty"></div>
            <?php endfor; ?>

            <?php for ($d = 1; $d <= $totalDias; $d++):
                $isHoje = ($ano === $hojeY && $mes === $hojeM && $d === $hojeD);
                $evs = $porDia[$d] ?? [];
            ?>
                <div class="cal__cell <?= $isHoje ? 'is-today' : '' ?>">
                    <span class="cal__day"><?= $d ?></span>
                    <?php foreach (array_slice($evs, 0, 3) as $e): ?>
                        <span class="ev ev--<?= $e['tipo'] ?> <?= $e['concluido'] ? 'ev--done' : '' ?>"
                              title="<?= htmlspecialchars($e['titulo']) ?>">
                            <?php if (!empty($e['hora'])): ?><?= substr($e['hora'], 0, 5) ?> <?php endif; ?><?= htmlspecialchars($e['titulo']) ?>
                        </span>
                    <?php endforeach; ?>
                    <?php if (count($evs) > 3): ?>
                        <span class="cal__more">+<?= count($evs) - 3 ?> mais</span>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>

            <?php for ($i = 0; $i < $trailing; $i++): ?>
                <div class="cal__cell cal__cell--empty"></div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="split">
        <section>
            <h2>Próximos eventos</h2>
            <?php if (empty($proximos)): ?>
                <div class="card card--empty"><p>Nada agendado à frente. Adicione um evento ao lado.</p></div>
            <?php else: ?>
                <ul class="agenda">
                    <?php foreach ($proximos as $e):
                        $ts = strtotime($e['data_evento']);
                        $atrasado = ($e['data_evento'] < $hojeStr);
                    ?>
                        <li class="agenda-item <?= $atrasado ? 'is-overdue' : '' ?>">
                            <span class="date-chip">
                                <span class="d"><?= date('d', $ts) ?></span>
                                <span class="m"><?= mb_substr($meses[(int) date('n', $ts)], 0, 3) ?></span>
                            </span>
                            <span class="agenda-main">
                                <strong><?= htmlspecialchars($e['titulo']) ?></strong>
                                <small class="muted">
                                    <span class="ev ev--<?= $e['tipo'] ?>"><?= $tiposLabel[$e['tipo']] ?></span>
                                    <?php if (!empty($e['hora'])): ?> · <?= substr($e['hora'], 0, 5) ?><?php endif; ?>
                                    <?php if (!empty($e['disciplina_nome'])): ?> · <?= htmlspecialchars($e['disciplina_nome']) ?><?php endif; ?>
                                </small>
                            </span>
                            <span class="agenda-actions">
                                <form method="post" action="/eventos/<?= (int) $e['id'] ?>/concluir">
                                    <input type="hidden" name="ano" value="<?= $ano ?>"><input type="hidden" name="mes" value="<?= $mes ?>">
                                    <button class="btn btn--ghost btn--sm" type="submit" title="Marcar como concluído">✓</button>
                                </form>
                                <form method="post" action="/eventos/<?= (int) $e['id'] ?>/excluir" onsubmit="return confirm('Excluir este evento?');">
                                    <input type="hidden" name="ano" value="<?= $ano ?>"><input type="hidden" name="mes" value="<?= $mes ?>">
                                    <button class="btn btn--danger btn--sm" type="submit" title="Excluir">✕</button>
                                </form>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <aside>
            <div class="card">
                <h3>Novo evento</h3>
                <form method="post" action="/calendario" class="form">
                    <label>Título
                        <input type="text" name="titulo" placeholder="Ex: Prova de frações" required>
                    </label>
                    <label>Tipo
                        <select name="tipo">
                            <option value="prova">Prova</option>
                            <option value="trabalho">Trabalho</option>
                            <option value="lembrete" selected>Lembrete</option>
                            <option value="aula">Aula</option>
                        </select>
                    </label>
                    <div class="form--inline">
                        <label style="flex:1 1 140px;">Data
                            <input type="date" name="data_evento" value="<?= $hojeStr ?>" required>
                        </label>
                        <label style="flex:0 1 110px;">Hora
                            <input type="time" name="hora">
                        </label>
                    </div>
                    <label>Disciplina <span class="muted">(opcional)</span>
                        <select name="disciplina_id">
                            <option value="">—</option>
                            <?php foreach ($disciplinas as $d): ?>
                                <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Descrição <span class="muted">(opcional)</span>
                        <textarea name="descricao" rows="2"></textarea>
                    </label>
                    <button type="submit" class="btn btn--primary">Adicionar evento</button>
                </form>
            </div>
        </aside>
    </div>
