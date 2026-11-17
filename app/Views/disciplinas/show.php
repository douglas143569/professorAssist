<?php
    $alunosAtivos = array_values(array_filter($alunos ?? [], static fn ($a) => ($a['situacao'] ?? '') === 'ativa'));
    $tarefasRascunho = array_values(array_filter($tarefas ?? [], static fn ($t) => (int) $t['publicada'] === 0));
    $tarefasPublicadas = array_values(array_filter($tarefas ?? [], static fn ($t) => (int) $t['publicada'] === 1 && (int) $t['n_entregas'] === 0));
    $tarefasComEntregas = array_values(array_filter($tarefas ?? [], static fn ($t) => (int) $t['publicada'] === 1 && (int) $t['n_entregas'] > 0));
    $progresso = min(100, ($resumo['n_temas'] > 0 ? 25 : 0) + ($resumo['n_conteudos'] > 0 ? 25 : 0) + ($resumo['n_questoes'] > 0 ? 25 : 0) + (($resumo['n_atividades'] + $resumo['n_planos'] + $resumo['n_apresentacoes']) > 0 ? 25 : 0));
    $iniciais = static function (string $nome): string { $p = preg_split('/\s+/', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: []; return mb_strtoupper(mb_substr($p[0] ?? '?', 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : '')); };
?>
<div class="subject-page">
    <p class="breadcrumb"><a href="/escolas">Escolas</a><?php if (!empty($disciplina['escola_id'])): ?> <span>›</span> <a href="/escolas/<?= (int) $disciplina['escola_id'] ?>"><?= htmlspecialchars($disciplina['escola_nome'] ?? 'Minha escola') ?></a><?php endif; ?><?php if (!empty($disciplina['turma_id'])): ?> <span>›</span> <a href="/turmas/<?= (int) $disciplina['turma_id'] ?>"><?= htmlspecialchars($disciplina['turma_nome'] ?? 'Turma') ?></a><?php endif; ?> <span>›</span> <?= htmlspecialchars($disciplina['nome']) ?></p>
    <header class="subject-hero">
        <span class="subject-hero__icon">⚗</span>
        <div class="subject-hero__copy"><h1><?= htmlspecialchars($disciplina['nome']) ?></h1><p><strong><?= htmlspecialchars($disciplina['turma_nome'] ?? '') ?></strong><span>•</span><?= htmlspecialchars($disciplina['etapa'] ?? '') ?><?= !empty($disciplina['ano_serie']) ? ' · ' . htmlspecialchars($disciplina['ano_serie']) : '' ?></p><small>Explore os temas e acompanhe os recursos de aprendizagem desta matéria.</small></div>
        <div class="subject-hero__actions"><a href="/disciplinas/<?= (int) $disciplina['id'] ?>/questoes" class="btn btn--ghost">▤ &nbsp; Banco de questões</a><?php if (!empty($disciplina['turma_id'])): ?><a href="/turmas/<?= (int) $disciplina['turma_id'] ?>" class="subject-parent-link">Ações da turma ↗</a><?php endif; ?></div>
    </header>

    <section class="subject-stats">
        <?php foreach ([['♧','Alunos',count($alunosAtivos),'blue'],['▣','Tarefas',count($tarefas ?? []),'purple'],['◧','Conteúdos',$resumo['n_conteudos'],'indigo'],['◉','Avisos',count($avisos ?? []),'orange']] as [$icon,$label,$value,$color]): ?>
            <a class="subject-stat" href="<?= $label === 'Alunos' || $label === 'Tarefas' || $label === 'Avisos' ? (!empty($disciplina['turma_id']) ? '/turmas/' . (int) $disciplina['turma_id'] : '#') : '#temas' ?>"><span class="subject-stat__icon is-<?= $color ?>"><?= $icon ?></span><span><strong><?= (int) $value ?></strong><small><?= $label ?></small></span><em>Ver <?= strtolower($label) ?> →</em></a>
        <?php endforeach; ?>
        <div class="subject-planning"><div><strong>Planejamento da matéria</strong><b><?= $progresso ?>%</b></div><span class="subject-planning__bar"><i style="width:<?= $progresso ?>%"></i></span><small>Temas, conteúdos e materiais criados.</small></div>
    </section>

    <div class="subject-layout">
        <main class="subject-main">
            <section class="subject-ai-panel">
                <div class="subject-ai-illustration" aria-hidden="true"><span>✦</span><b>AP</b></div>
                <div class="subject-ai-content"><p class="subject-kicker">ASSISTENTE DE PLANEJAMENTO</p><h2>O que você quer preparar para esta matéria?</h2><p>Crie um tema primeiro. Dentro dele, você encontra as ferramentas de IA para conteúdo, plano de aula, atividades e apresentações.</p>
                    <div class="subject-ai-actions"><a href="#novo-tema">＋ Criar tema da aula</a><a href="/disciplinas/<?= (int) $disciplina['id'] ?>/questoes">▤ Abrir banco de questões</a><?php if (!empty($modulos)): ?><a href="/modulos/<?= (int) $modulos[0]['id'] ?>">✦ Ferramentas do tema mais recente</a><?php endif; ?></div>
                </div>
            </section>

            <section class="subject-tasks subject-box"><div class="subject-section-head"><div><h2>Tarefas da turma</h2><p>Tarefas, publicações e entregas registradas na turma.</p></div><?php if (!empty($disciplina['turma_id'])): ?><a href="/turmas/<?= (int) $disciplina['turma_id'] ?>">Ver turma →</a><?php endif; ?></div>
                <?php if (empty($tarefas)): ?><div class="subject-empty">Ainda não há tarefas nesta turma. Você pode criá-las pela página da turma.</div><?php else: ?>
                <div class="subject-task-columns">
                    <?php foreach ([['Rascunhos',$tarefasRascunho,'is-draft'],['Publicadas',$tarefasPublicadas,'is-live'],['Com entregas',$tarefasComEntregas,'is-done']] as [$coluna,$lista,$classe]): ?>
                        <div class="subject-task-column"><h3><i class="<?= $classe ?>"></i><?= $coluna ?><span><?= count($lista) ?></span></h3>
                            <?php if (empty($lista)): ?><p class="subject-task-empty">Nenhuma tarefa neste grupo.</p><?php else: foreach (array_slice($lista,0,4) as $t): ?><a class="subject-task" href="/tarefas/<?= (int) $t['id'] ?>"><span class="subject-task__dot"></span><strong><?= htmlspecialchars($t['titulo']) ?></strong><small><?= (int) $t['n_entregas'] ?> entrega(s) · <?= (int) $t['n_corrigidas'] ?> corrigida(s)</small></a><?php endforeach; endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div><?php endif; ?>
            </section>

            <section class="subject-people subject-box"><div class="subject-section-head"><div><h2>Alunos da turma <span><?= count($alunosAtivos) ?></span></h2><p>Alunos com matrícula ativa nesta turma.</p></div><?php if (!empty($disciplina['turma_id'])): ?><a href="/turmas/<?= (int) $disciplina['turma_id'] ?>">Ver todos →</a><?php endif; ?></div>
                <?php if (empty($alunosAtivos)): ?><div class="subject-empty">Nenhum aluno ativo nesta turma no momento.</div><?php else: ?><div class="subject-student-list"><?php foreach (array_slice($alunosAtivos,0,10) as $i => $al): ?><div class="subject-student"><span class="subject-student__avatar tone-<?= $i % 5 ?>"><?= htmlspecialchars($iniciais($al['aluno_nome'])) ?></span><span><?= htmlspecialchars($al['aluno_nome']) ?></span></div><?php endforeach; ?><?php if (count($alunosAtivos) > 10): ?><a class="subject-student-more" href="/turmas/<?= (int) $disciplina['turma_id'] ?>">+<?= count($alunosAtivos) - 10 ?><small>Ver todos</small></a><?php endif; ?></div><?php endif; ?>
            </section>

            <section class="subject-notices subject-box"><div class="subject-section-head"><div><h2>Avisos recentes</h2><p>Mural da turma.</p></div><?php if (!empty($disciplina['turma_id'])): ?><a href="/turmas/<?= (int) $disciplina['turma_id'] ?>">Ver mural →</a><?php endif; ?></div>
                <?php if (empty($avisos)): ?><div class="subject-empty">Ainda não há avisos publicados.</div><?php else: ?><div class="subject-notice-list"><?php foreach (array_slice($avisos,0,4) as $av): ?><article><span class="subject-notice-dot"></span><div><small><?= date('d/m H:i', strtotime($av['created_at'])) ?><?= !empty($av['fixado']) ? ' · Fixado' : '' ?></small><strong><?= htmlspecialchars($av['titulo']) ?></strong><p><?= htmlspecialchars(mb_strimwidth($av['corpo'],0,150,'…')) ?></p></div></article><?php endforeach; ?></div><?php endif; ?>
            </section>
        </main>

        <aside class="subject-side">
            <?php
                $cenas = [
                    ['M0 120V70l30-34 22 22 34-46 40 50 26-22 38 40 30-30 40 34 40-28v82Z', 'M0 120V92l40-18 36 22 44-30 48 28 52-24 80 20v30Z'],
                    ['M0 120V62h18v-14h16v30h14V40h20v38h16V54h14v66ZM150 120V50h14V30h10v20h14v32h12V58h18v62ZM240 120V70h20V44h12v26h28v50Z', 'M0 120V96h26V80h20v16h22V84h18v36ZM130 120V90h20V76h16v14h26v30ZM230 120V88h24V72h14v16h32v32Z'],
                    ['M0 120V84c40-30 80-30 120-6 40 22 80 16 110-8 26-14 50-10 70 2v48Z', 'M0 120V100c46-22 90-14 130 4 40 14 86 6 170-12v28Z'],
                    ['M0 120V80l20-30 14 18 16-34 16 40 18-24 14 30 20-40 18 44 24-22 22 30 18-26 20 34 20-18v86Z', 'M0 120V96l24-16 20 20 28-24 24 28 30-20 30 26 34-22 34 24 40-12v36Z'],
                ];
                $cena = static fn(int $t) => '<svg class="rec-scene" viewBox="0 0 300 120" preserveAspectRatio="xMidYMax slice" aria-hidden="true"><circle class="rec-sun" cx="250" cy="30" r="14"/><path class="rec-back" d="' . $cenas[$t][0] . '"/><path class="rec-front" d="' . $cenas[$t][1] . '"/></svg>';
            ?>
            <section class="subject-suggestions"><p class="subject-kicker">RECURSOS DA MATÉRIA</p><h2>Continue seu planejamento</h2><p>Escolha um tema para abrir as ferramentas disponíveis.</p>
                <?php if (!empty($modulos)): ?><div class="rec-list"><?php foreach (array_slice($modulos,0,4) as $n => $m): $t = $n % 4; ?><a class="subject-suggestion rec-card tone-<?= $t ?>" style="--i:<?= $n ?>" href="/modulos/<?= (int) $m['id'] ?>"><?= $cena($t) ?><span class="rec-card__num"><?= $n + 1 ?></span><span class="rec-card__body"><small class="rec-card__kicker">Tema da aula</small><strong><?= htmlspecialchars($m['titulo']) ?></strong><small class="rec-card__sub"><?= !empty($m['codigos_bncc']) ? 'BNCC ' . htmlspecialchars($m['codigos_bncc']) : 'Abrir conteúdo, plano e atividades' ?></small></span><b class="rec-card__go">Abrir <span>→</span></b></a><?php endforeach; ?></div><?php else: ?><div class="subject-suggestion-empty">Crie o primeiro tema para acessar geração de conteúdos, planos, atividades e apresentações com IA.</div><?php endif; ?>
                <a href="/disciplinas/<?= (int) $disciplina['id'] ?>/questoes" class="subject-question-link rec-card rec-card--bank tone-4" style="--i:5"><?= $cena(2) ?><span class="rec-card__body"><small class="rec-card__kicker">Banco da matéria</small><strong>Abrir banco de questões</strong></span><b class="rec-card__go">Abrir <span>→</span></b></a>
            </section>
            <section class="subject-new-topic" id="novo-tema"><div class="subject-new-topic__heading"><span>＋</span><div><h2>Novo tema da aula</h2><p>Adicione um tópico para planejar.</p></div></div>
                <form method="post" action="/disciplinas/<?= (int) $disciplina['id'] ?>/modulos" class="form subject-topic-form">
                    <?= \App\Services\Csrf::campo() ?>
                    <label>Título<input type="text" name="titulo" placeholder="Ex: Ligações químicas" required></label>
                    <label>Habilidades BNCC <span>(opcional)</span><input type="text" name="codigos_bncc" placeholder="Ex: EF09CI01"></label>
                    <label>Objetivos <span>(opcional)</span><textarea name="objetivos" rows="3" placeholder="Objetivos de aprendizagem"></textarea></label>
                    <button type="submit" class="btn btn--primary">Criar tema</button>
                </form>
            </section>
            <section class="subject-counts"><h2>Esta matéria</h2><div><span>Temas</span><strong><?= $resumo['n_temas'] ?></strong></div><div><span>Conteúdos</span><strong><?= $resumo['n_conteudos'] ?></strong></div><div><span>Questões</span><strong><?= $resumo['n_questoes'] ?></strong></div><div><span>Atividades</span><strong><?= $resumo['n_atividades'] ?></strong></div><div><span>Planos de aula</span><strong><?= $resumo['n_planos'] ?></strong></div><div><span>Apresentações</span><strong><?= $resumo['n_apresentacoes'] ?></strong></div></section>
            <form method="post" action="/disciplinas/<?= (int) $disciplina['id'] ?>/excluir" class="subject-delete-form" onsubmit="return confirm('Excluir a matéria &quot;<?= htmlspecialchars($disciplina['nome'], ENT_QUOTES) ?>&quot; e todos os seus temas, conteúdos e questões?');">
                <?= \App\Services\Csrf::campo() ?><button type="submit" class="btn btn--danger btn--sm">Excluir matéria</button>
            </form>
        </aside>
    </div>
</div>
