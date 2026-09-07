    <p class="breadcrumb">
        <a href="/turmas">Turmas</a> /
        <a href="/disciplinas/<?= (int) $modulo['disciplina_id'] ?>"><?= htmlspecialchars($modulo['disciplina_nome']) ?></a> /
        <?= htmlspecialchars($modulo['titulo']) ?>
    </p>

    <p class="muted" style="margin:-6px 0 14px;font-size:0.85rem;">Tema da aula</p>

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
        <?php if (empty($conteudos)): ?>
            <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/conteudos/gerar" class="js-ai">
            <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--primary" data-loading="Gerando conteúdo…">✦ Gerar conteúdo com IA</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($modulo['objetivos'])): ?>
        <details class="bloco-toggle" style="margin-top:16px;">
            <summary>
                <span class="bloco-toggle__titulo">Objetivos</span>
                <span class="bloco-toggle__acao">ver objetivos</span>
            </summary>
            <div class="bloco-toggle__corpo">
                <p style="margin-top:12px;"><?= nl2br(htmlspecialchars($modulo['objetivos'])) ?></p>
            </div>
        </details>
    <?php endif; ?>

    <details class="bloco-toggle" style="margin-top:16px;">
        <summary>
            <span class="bloco-toggle__titulo">Plano de aula<?php if (!empty($planos)): ?> <span class="tag"><?= count($planos) ?></span><?php endif; ?></span>
            <span class="bloco-toggle__acao">gerar / ver planos</span>
        </summary>
        <div class="bloco-toggle__corpo">
            <div class="card">
                <h3><?= empty($planos) ? 'Gerar plano de aula com IA' : 'Criar mais um plano para este tema' ?></h3>
                <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/planos/gerar" class="form form--inline js-ai">
                    <?= \App\Services\Csrf::campo() ?>
                    <label>Duração
                        <select name="duracao">
                            <option value="1 aula de 50 min">1 aula de 50 min</option>
                            <option value="2 aulas de 50 min">2 aulas de 50 min</option>
                            <option value="1 aula de 100 min">1 aula de 100 min</option>
                            <option value="Sequência de 3 aulas">Sequência de 3 aulas</option>
                        </select>
                    </label>
                    <button type="submit" class="btn btn--primary" data-loading="<?= empty($planos) ? 'Gerando plano…' : 'Criando novo plano…' ?>">
                        <?= empty($planos) ? '✦ Gerar plano' : '✦ Criar mais um plano de aula' ?>
                    </button>
                </form>
                <?php if (!empty($planos)): ?>
                    <p class="muted" style="font-size:0.82rem; margin:10px 0 0;">
                        A IA considera os planos que já existem nesta mesma duração e propõe outra abordagem.
                        Trocar a duração gera um plano novo do zero.
                    </p>
                <?php endif; ?>
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
        </div>
    </details>

    <details class="bloco-toggle" style="margin-top:16px;">
        <summary>
            <span class="bloco-toggle__titulo">Gerador de Slide<?php if (!empty($apresentacoes)): ?> <span class="tag"><?= count($apresentacoes) ?></span><?php endif; ?></span>
            <span class="bloco-toggle__acao">slides + resumo</span>
        </summary>
        <div class="bloco-toggle__corpo">
            <div class="card">
                <h3><?= empty($apresentacoes) ? 'Gerar apresentação com IA' : 'Criar mais uma apresentação' ?></h3>
                <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/apresentacoes/gerar" class="form form--inline js-ai">
                    <?= \App\Services\Csrf::campo() ?>
                    <label>Nº de slides
                        <select name="n_slides">
                            <option value="6">6</option>
                            <option value="8" selected>8</option>
                            <option value="10">10</option>
                            <option value="12">12</option>
                            <option value="16">16</option>
                        </select>
                    </label>
                    <label style="flex:1 1 260px;">Foco <span class="muted">(opcional)</span>
                        <input type="text" name="foco" placeholder="Ex: exemplos do cotidiano, viés histórico…">
                    </label>
                    <button type="submit" class="btn btn--primary" data-loading="<?= empty($apresentacoes) ? 'Gerando apresentação…' : 'Criando nova…' ?>">
                        <?= empty($apresentacoes) ? '✦ Gerar apresentação' : '✦ Criar mais uma' ?>
                    </button>
                </form>
                <?php if (!empty($apresentacoes)): ?>
                    <p class="muted" style="font-size:0.82rem; margin:10px 0 0;">
                        A IA recebe os títulos das apresentações que já existem e propõe outro recorte do tema.
                    </p>
                <?php endif; ?>
            </div>

            <?php if (empty($apresentacoes)): ?>
                <div class="card card--empty">
                    <p>Nenhuma apresentação ainda. Gere um roteiro de slides e um resumo para a aula expositiva.</p>
                </div>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($apresentacoes as $a): ?>
                        <li>
                            <a href="/apresentacoes/<?= (int) $a['id'] ?>" class="list__main"><?= htmlspecialchars($a['titulo']) ?></a>
                            <?php if (!empty($a['foco'])): ?>
                                <span class="tag tag--origem"><?= htmlspecialchars($a['foco']) ?></span>
                            <?php endif; ?>
                            <span class="badge badge--<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span>
                            <span class="tag tag--origem"><?= $a['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                            <?php if (!empty($a['material_id'])): ?>
                                <span class="tag">resumo publicado</span>
                            <?php endif; ?>
                            <a href="/apresentacoes/<?= (int) $a['id'] ?>/apresentar" target="_blank" rel="noopener">▶ apresentar</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </details>

    <details class="bloco-toggle" style="margin-top:16px;">
        <summary>
            <span class="bloco-toggle__titulo">Conteúdos<?php if (!empty($conteudos)): ?> <span class="tag"><?= count($conteudos) ?></span><?php endif; ?></span>
            <span class="bloco-toggle__acao">gerar / ver conteúdos</span>
        </summary>
        <div class="bloco-toggle__corpo">
            <?php if (empty($conteudos)): ?>
                <div class="card card--empty" style="margin-top:12px;">
                    <p>Nenhum conteúdo ainda. Clique em <strong>Gerar conteúdo com IA</strong> para criar um rascunho.</p>
                </div>
            <?php else: ?>
                <ul class="list" style="margin-top:12px;">
                    <?php foreach ($conteudos as $c): ?>
                        <li>
                            <a href="/conteudos/<?= (int) $c['id'] ?>"><strong><?= htmlspecialchars($c['titulo']) ?></strong></a>
                            <span class="badge badge--<?= htmlspecialchars($c['status']) ?>"><?= htmlspecialchars($c['status']) ?></span>
                            <span class="tag tag--origem"><?= $c['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/conteudos/gerar" class="js-ai" style="margin-top:14px;">
                <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--primary" data-loading="Criando novo material…">
                    <?= empty($conteudos) ? '✦ Gerar conteúdo com IA' : '✦ Criar mais conteúdo com este tema' ?>
                </button>
                <?php if (!empty($conteudos)): ?>
                    <p class="muted" style="font-size:0.82rem; margin:8px 0 0;">
                        A IA recebe o que já existe aqui e produz um material diferente, com outro recorte do tema.
                    </p>
                <?php endif; ?>
            </form>
        </div>
    </details>

    <?php
        $formatosLabel = ['individual' => 'Individual', 'grupo' => 'Em grupo', 'discussao' => 'Discussão', 'pratica' => 'Prática', 'projeto' => 'Projeto', 'jogo' => 'Jogo'];
    ?>
    <details class="bloco-toggle" style="margin-top:16px;">
        <summary>
            <span class="bloco-toggle__titulo">Atividades sugeridas<?php if (!empty($atividades)): ?> <span class="tag"><?= count($atividades) ?></span><?php endif; ?></span>
            <span class="bloco-toggle__acao">sugerir / ver atividades</span>
        </summary>

        <div class="bloco-toggle__corpo">
            <div class="card">
                <h3>Sugerir atividades com IA</h3>
                <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/atividades/gerar" class="form form--inline js-ai">
                    <?= \App\Services\Csrf::campo() ?>
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
        </div>
    </details>

    <?php
        $tiposLabel = ['multipla_escolha' => 'Múltipla escolha', 'verdadeiro_falso' => 'V ou F', 'dissertativa' => 'Dissertativa'];
    ?>
    <details class="bloco-toggle" style="margin-top:16px;">
        <summary>
            <span class="bloco-toggle__titulo">Gerador de atividades<?php if (!empty($questoes)): ?> <span class="tag"><?= count($questoes) ?></span><?php endif; ?></span>
            <span class="bloco-toggle__acao">gerar / ver questões</span>
        </summary>

        <div class="bloco-toggle__corpo">
            <div class="actions" style="margin:14px 0;">
                <a href="/modulos/<?= (int) $modulo['id'] ?>/questoes/nova" class="btn btn--ghost">+ Adicionar manual</a>
                <a href="/disciplinas/<?= (int) $modulo['disciplina_id'] ?>/questoes" class="btn btn--ghost">Banco da disciplina</a>
                <?php if (!empty($questoes)): ?>
                    <a href="/modulos/<?= (int) $modulo['id'] ?>/atividade" class="btn btn--primary">Entrar na atividade →</a>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Gerar questões com IA</h3>
                <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/questoes/gerar" class="form form--inline js-ai">
                    <?= \App\Services\Csrf::campo() ?>
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
        </div>
    </details>
