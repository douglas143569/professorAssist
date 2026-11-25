    <p class="breadcrumb">
        <a href="/turmas">Turmas</a> /
        <a href="/disciplinas/<?= (int) $modulo['disciplina_id'] ?>"><?= htmlspecialchars($modulo['disciplina_nome']) ?></a> /
        <?= htmlspecialchars($modulo['titulo']) ?>
    </p>

    <section class="tema-hero">
        <div class="tema-hero__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/></svg>
        </div>
        <div class="tema-hero__texto">
            <span class="tema-hero__eyebrow">Tema da aula</span>
            <h1><?= htmlspecialchars($modulo['titulo']) ?></h1>
            <p class="muted">
                <span class="tag"><?= htmlspecialchars($modulo['disciplina_etapa']) ?></span>
                <?php if (!empty($modulo['codigos_bncc'])): ?>
                    <span class="tag tag--bncc"><?= htmlspecialchars($modulo['codigos_bncc']) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <?php if (empty($conteudos)): ?>
            <form method="post" action="/modulos/<?= (int) $modulo['id'] ?>/conteudos/gerar" class="js-ai tema-hero__acao">
            <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--primary" data-loading="Gerando conteúdo…">✦ Gerar conteúdo com IA</button>
            </form>
        <?php endif; ?>
        <dl class="tema-stats">
            <div><dt>Planos</dt><dd><?= count($planos ?? []) ?></dd></div>
            <div><dt>Apresentações</dt><dd><?= count($apresentacoes ?? []) ?></dd></div>
            <div><dt>Conteúdos</dt><dd><?= count($conteudos ?? []) ?></dd></div>
            <div><dt>Atividades</dt><dd><?= count($atividades ?? []) ?></dd></div>
            <div><dt>Questões</dt><dd><?= count($questoes ?? []) ?></dd></div>
        </dl>
    </section>

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

    <div class="topic-tools-grid">
    <details class="bloco-toggle topic-tool-card topic-tool-card--lesson">
        <summary>
            <span class="tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2V5Z"/><path d="M8 7h6M8 11h6"/></svg></span>
            <span class="tool-copy">
                <span class="tool-title">Plano de aula<?php if (!empty($planos)): ?> <span class="tool-count"><?= count($planos) ?></span><?php endif; ?></span>
                <span class="tool-desc">Sequência didática gerada pela IA com base na BNCC</span>
            </span>
            <span class="tool-cta">gerar / ver planos <b>›</b></span>
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

    <details class="bloco-toggle topic-tool-card topic-tool-card--slides">
        <summary>
            <span class="tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/><path d="m10 8 4 2-4 2V8Z"/></svg></span>
            <span class="tool-copy">
                <span class="tool-title">Apresentações e vídeos<?php if (!empty($apresentacoes)): ?> <span class="tool-count"><?= count($apresentacoes) ?></span><?php endif; ?></span>
                <span class="tool-desc">Slides, resumo e vídeos para apoiar a aula</span>
            </span>
            <span class="tool-cta">criar / ver <b>›</b></span>
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

    <details class="bloco-toggle topic-tool-card topic-tool-card--content">
        <summary>
            <span class="tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v4h4M10 12h6M10 16h6"/></svg></span>
            <span class="tool-copy">
                <span class="tool-title">Conteúdos<?php if (!empty($conteudos)): ?> <span class="tool-count"><?= count($conteudos) ?></span><?php endif; ?></span>
                <span class="tool-desc">Textos e materiais de estudo para os alunos</span>
            </span>
            <span class="tool-cta">gerar / ver conteúdos <b>›</b></span>
        </summary>
        <div class="bloco-toggle__corpo">
            <?php if (empty($conteudos)): ?>
                <div class="card card--empty" style="margin-top:12px;">
                    <p>Nenhum conteúdo ainda. Clique em <strong>Gerar conteúdo com IA</strong> para criar um rascunho.</p>
                </div>
            <?php else: ?>
                <div class="gl-grid" style="margin-top:14px;">
                    <?php foreach ($conteudos as $n => $c): ?>
                        <a class="gl-card" href="/conteudos/<?= (int) $c['id'] ?>" style="--i:<?= $n % 12 ?>">
                            <span class="gl-tile" aria-hidden="true"><svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v4h4M10 12h6M10 16h6"/></svg></span>
                            <span class="gl-waves" aria-hidden="true"><svg viewBox="0 0 400 80" preserveAspectRatio="none"><path class="gl-w1" d="M0 40Q50 0 100 40T200 40T300 40T400 40V80H0Z"/></svg></span>
                            <strong class="gl-pill"><?= htmlspecialchars($c['titulo']) ?></strong>
                            <span class="gl-meta">
                                <span class="badge badge--<?= htmlspecialchars($c['status']) ?>"><?= htmlspecialchars($c['status']) ?></span>
                                <span class="tag tag--origem"><?= $c['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                            </span>
                            <span class="gl-open">Abrir <span aria-hidden="true">↗</span></span>
                        </a>
                    <?php endforeach; ?>
                </div>
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
    <details class="bloco-toggle topic-tool-card topic-tool-card--activities">
        <summary>
            <span class="tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/><circle cx="12" cy="12" r="2.5"/></svg></span>
            <span class="tool-copy">
                <span class="tool-title">Atividades sugeridas<?php if (!empty($atividades)): ?> <span class="tool-count"><?= count($atividades) ?></span><?php endif; ?></span>
                <span class="tool-desc">Dinâmicas em grupo, práticas e lúdicas</span>
            </span>
            <span class="tool-cta">sugerir / ver atividades <b>›</b></span>
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
    <details class="bloco-toggle topic-tool-card topic-tool-card--questions">
        <summary>
            <span class="tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 9a3 3 0 1 1 4.2 2.7c-.8.4-1.2 1-1.2 1.8"/><path d="M12 17.5v.01"/><rect x="3" y="3" width="18" height="18" rx="4"/></svg></span>
            <span class="tool-copy">
                <span class="tool-title">Gerador de atividades<?php if (!empty($questoes)): ?> <span class="tool-count"><?= count($questoes) ?></span><?php endif; ?></span>
                <span class="tool-desc">Questões de múltipla escolha, V/F e dissertativas</span>
            </span>
            <span class="tool-cta">gerar / ver atividades <b>›</b></span>
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

            <?php if (empty($atividades) && empty($questoes)): ?>
                <div class="card card--empty">
                    <p>Nenhuma atividade gerada ainda. Gere questões acima ou peça sugestões em “Atividades sugeridas”.</p>
                </div>
            <?php else: ?>
                <?php $corFormato = ['grupo' => 0, 'individual' => 1, 'ludica' => 2]; ?>
                <div class="pkg-grid pkg-grid--wide">
                    <?php if (!empty($questoes)): ?>
                        <?php $porTipo = array_count_values(array_column($questoes, 'tipo')); ?>
                        <a class="pkg-card pkg-card--3" href="/modulos/<?= (int) $modulo['id'] ?>/atividade" style="--i:0">
                            <span class="pkg-card__top pkg-card__top--item">
                                <span class="pkg-q-num"><?= count($questoes) ?></span>
                                <span class="pkg-tags">
                                    <?php foreach ($porTipo as $t => $qtd): ?><span class="pkg-tag"><?= (int) $qtd ?> · <?= htmlspecialchars($tiposLabel[$t] ?? $t) ?></span><?php endforeach; ?>
                                </span>
                                <span class="pkg-q"><strong>Atividade do tema</strong><br>Todas as questões geradas reunidas numa atividade para aplicar, imprimir ou salvar em PDF.</span>
                            </span>
                            <span class="pkg-card__foot pkg-card__foot--row">
                                <span><span class="tag tag--origem"><?= count($questoes) ?> questões</span></span>
                                <b class="pkg-card__cta">Entrar ›</b>
                            </span>
                        </a>
                    <?php endif; ?>
                    <?php foreach ($atividades as $n => $a): $c = $corFormato[$a['formato'] ?? ''] ?? ($n % 4); ?>
                        <a class="pkg-card pkg-card--<?= $c ?>" href="/atividades/<?= (int) $a['id'] ?>" style="--i:<?= $n % 12 ?>">
                            <span class="pkg-card__top pkg-card__top--item">
                                <span class="pkg-q-num"><?= $n + 1 ?></span>
                                <span class="pkg-tags">
                                    <?php if (!empty($a['formato'])): ?><span class="pkg-tag"><?= htmlspecialchars($formatosLabel[$a['formato']] ?? $a['formato']) ?></span><?php endif; ?>
                                    <?php if (!empty($a['duracao'])): ?><span class="pkg-tag"><?= htmlspecialchars($a['duracao']) ?></span><?php endif; ?>
                                </span>
                                <span class="pkg-q"><strong><?= htmlspecialchars($a['titulo']) ?></strong><?php if (!empty($a['descricao'])): ?><br><?= htmlspecialchars(mb_strimwidth(strip_tags((string) $a['descricao']), 0, 110, '…')) ?><?php endif; ?></span>
                            </span>
                            <span class="pkg-card__foot pkg-card__foot--row">
                                <span>
                                    <span class="badge badge--<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span>
                                    <span class="tag tag--origem"><?= $a['origem'] === 'ia' ? 'IA' : 'manual' ?></span>
                                </span>
                                <b class="pkg-card__cta">Abrir ›</b>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </details>

    <a class="bloco-toggle topic-tool-card topic-tool-card--bank" href="/banco-atividades?modulo=<?= (int) $modulo['id'] ?>">
        <span class="bank-sum">
            <span class="tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7c0-1.1 3.6-2 8-2s8 .9 8 2-3.6 2-8 2-8-.9-8-2Z"/><path d="M4 7v10c0 1.1 3.6 2 8 2s8-.9 8-2V7"/><path d="M4 12c0 1.1 3.6 2 8 2s8-.9 8-2"/></svg></span>
            <span class="tool-copy">
                <span class="tool-title">Banco de atividades<?php if (!empty($atividades)): ?> <span class="tool-count"><?= count($atividades) ?></span><?php endif; ?></span>
                <span class="tool-desc">Todas as atividades geradas e salvas, num só lugar</span>
            </span>
            <span class="tool-cta">abrir banco <b>›</b></span>
        </span>
    </a>
    </div>
