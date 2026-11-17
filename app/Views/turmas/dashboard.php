<?php
    $alunosAtivos = array_values(array_filter($alunos ?? [], static fn ($a) => ($a['situacao'] ?? '') === 'ativa'));
    $tarefasRascunho = array_values(array_filter($tarefas ?? [], static fn ($t) => (int) $t['publicada'] === 0));
    $tarefasPublicadas = array_values(array_filter($tarefas ?? [], static fn ($t) => (int) $t['publicada'] === 1 && (int) $t['n_entregas'] === 0));
    $tarefasEntregues = array_values(array_filter($tarefas ?? [], static fn ($t) => (int) $t['publicada'] === 1 && (int) $t['n_entregas'] > 0));
    $progressoTurma = 25 + (!empty($materias) ? 25 : 0) + (!empty($alunosAtivos) ? 25 : 0) + (!empty($tarefas) ? 25 : 0);
    $iniciais = static function (string $nome): string { $p = preg_split('/\s+/', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: []; return mb_strtoupper(mb_substr($p[0] ?? '?', 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : '')); };
?>
<div class="class-dashboard">
    <p class="breadcrumb"><a href="/escolas">Escolas</a><span>›</span><a href="/escolas/<?= (int) $turma['escola_id'] ?>"><?= htmlspecialchars($turma['escola_nome'] ?? 'Minha escola') ?></a><span>›</span><?= htmlspecialchars($turma['nome']) ?></p>

    <header class="class-hero">
        <span class="class-hero__icon">♧</span>
        <div class="class-hero__copy"><h1><?= htmlspecialchars($turma['nome']) ?></h1><p><?php if (!empty($turma['ano_serie'])): ?><strong><?= htmlspecialchars($turma['ano_serie']) ?></strong><span>•</span><?php endif; ?><span class="class-stage"><?= htmlspecialchars($turma['etapa'] ?? 'Turma') ?></span><?php if (($turma['etapa'] ?? '') === 'EM'): ?><span>Ensino Médio</span><?php elseif (($turma['etapa'] ?? '') === 'EF'): ?><span>Ensino Fundamental</span><?php endif; ?></p><small>Organize matérias, acompanhe seus alunos e planeje as atividades da turma.</small></div>
        <a href="#nova-tarefa" class="btn btn--ghost class-delete-top">＋ &nbsp; Criar tarefa</a>
    </header>

    <section class="class-stats" aria-label="Resumo da turma">
        <a href="#alunos" class="class-stat"><span class="class-stat__icon tone-mint">♧</span><strong><?= count($alunosAtivos) ?></strong><small>Alunos ativos</small><em>Ver alunos →</em></a>
        <a href="#tarefas" class="class-stat"><span class="class-stat__icon tone-gold">▣</span><strong><?= count($tarefas ?? []) ?></strong><small>Tarefas</small><em>Ver tarefas →</em></a>
        <a href="#materias" class="class-stat"><span class="class-stat__icon tone-blue">▤</span><strong><?= count($materias ?? []) ?></strong><small>Matérias</small><em>Ver matérias →</em></a>
        <a href="#avisos" class="class-stat"><span class="class-stat__icon tone-rose">◉</span><strong><?= count($avisos ?? []) ?></strong><small>Avisos</small><em>Ver avisos →</em></a>
        <div class="class-planning"><div><strong>Organização da turma</strong><b><?= $progressoTurma ?>%</b></div><span><i style="width:<?= $progressoTurma ?>%"></i></span><small>Matérias, alunos e tarefas cadastrados.</small></div>
    </section>

    <div class="class-dashboard-grid">
        <main class="class-dashboard-main">
            <section class="class-ai-banner">
                <div class="class-ai-bot" aria-hidden="true"><span>✦</span><b>AP</b></div>
                <div class="class-ai-copy"><p>ASSISTENTE DE PLANEJAMENTO</p><h2>O que você quer preparar para esta turma?</h2><small>Abra uma matéria para criar conteúdos, planos de aula, atividades e apresentações com as ferramentas disponíveis.</small>
                    <div class="class-ai-links">
                        <?php if (!empty($materias)): ?><?php foreach (array_slice($materias, 0, 4) as $m): ?><a href="/disciplinas/<?= (int) $m['id'] ?>">✦ &nbsp; <?= htmlspecialchars($m['nome']) ?> <span>→</span></a><?php endforeach; ?><?php else: ?><a href="#nova-materia">＋ &nbsp; Cadastre uma matéria para começar <span>→</span></a><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="class-subjects-panel class-panel" id="materias"><div class="class-panel-head"><div><h2>Matérias da turma</h2><p>Abra uma matéria para acessar os temas e recursos.</p></div><a href="#nova-materia" class="btn btn--primary">＋ &nbsp; Nova matéria</a></div>
                <?php if (empty($materias)): ?><div class="class-empty"><span>▤</span><div><strong>Nenhuma matéria cadastrada</strong><p>Crie a primeira matéria para organizar temas e conteúdos.</p></div></div>
                <?php else: ?><?php
                    // Silhuetas em camadas (fundo / frente) para cada paleta de cartao.
                    $paisagens = [
                        ['M0 120V70l30-34 22 22 34-46 40 50 26-22 38 40 30-30 40 34 40-28v82Z', 'M0 120V92l40-18 36 22 44-30 48 28 52-24 80 20v30Z'],
                        ['M0 120V62h18v-14h16v30h14V40h20v38h16V54h14v66ZM150 120V50h14V30h10v20h14v32h12V58h18v62ZM240 120V70h20V44h12v26h28v50Z', 'M0 120V96h26V80h20v16h22V84h18v36ZM130 120V90h20V76h16v14h26v30ZM230 120V88h24V72h14v16h32v32Z'],
                        ['M0 120V84c40-30 80-30 120-6 40 22 80 16 110-8 26-14 50-10 70 2v48Z', 'M0 120V100c46-22 90-14 130 4 40 14 86 6 170-12v28Z'],
                        ['M0 120V80l20-30 14 18 16-34 16 40 18-24 14 30 20-40 18 44 24-22 22 30 18-26 20 34 20-18v86Z', 'M0 120V96l24-16 20 20 28-24 24 28 30-20 30 26 34-22 34 24 40-12v36Z'],
                    ];
                ?><div class="class-subject-grid"><?php foreach ($materias as $i => $m): $tone = $i % 4; ?><article class="class-subject-card tone-<?= $tone ?>" style="--i:<?= $i % 12 ?>">
                    <svg class="subject-scene" viewBox="0 0 300 120" preserveAspectRatio="xMidYMax slice" aria-hidden="true"><circle class="subject-sun" cx="236" cy="34" r="16"/><path class="subject-back" d="<?= $paisagens[$tone][0] ?>"/><path class="subject-front" d="<?= $paisagens[$tone][1] ?>"/></svg>
                    <div class="class-subject-card__top"><span><?= htmlspecialchars($m['etapa']) ?></span><form method="post" action="/disciplinas/<?= (int) $m['id'] ?>/excluir" onsubmit="return confirm('Excluir a matéria &quot;<?= htmlspecialchars($m['nome'], ENT_QUOTES) ?>&quot; e todos os temas, conteúdos e questões?');"><?= \App\Services\Csrf::campo() ?><button type="submit" title="Excluir matéria" aria-label="Excluir <?= htmlspecialchars($m['nome']) ?>">⋮</button></form></div>
                    <small class="subject-kicker">Matéria</small>
                    <h3><?= htmlspecialchars($m['nome']) ?></h3>
                    <p><?= htmlspecialchars($turma['ano_serie'] ?? $turma['nome']) ?></p>
                    <a href="/disciplinas/<?= (int) $m['id'] ?>">Abrir matéria <span>→</span></a>
                </article><?php endforeach; ?></div><?php endif; ?>
            </section>

            <section class="class-panel class-tasks-panel" id="tarefas"><div class="class-panel-head"><div><h2>Tarefas da turma</h2><p>Rascunhos, publicações e entregas.</p></div><a href="#nova-tarefa">＋ Nova tarefa</a></div>
                <?php if (empty($tarefas)): ?><div class="class-empty"><span>▣</span><div><strong>Nenhuma tarefa criada</strong><p>Use o formulário de nova tarefa para começar.</p></div></div><?php else: ?><div class="class-task-columns">
                    <?php foreach ([['Rascunhos',$tarefasRascunho,'gold'],['Publicadas',$tarefasPublicadas,'green'],['Com entregas',$tarefasEntregues,'blue']] as [$titulo,$lista,$tone]): ?><section class="class-task-column"><h3><i class="tone-<?= $tone ?>"></i><?= $titulo ?><span><?= count($lista) ?></span></h3><?php if (empty($lista)): ?><p class="class-task-empty">Nenhuma tarefa</p><?php else: foreach (array_slice($lista,0,4) as $tf): ?><a href="/tarefas/<?= (int) $tf['id'] ?>" class="class-task-card"><span class="class-task-card__icon tone-<?= $tone ?>">▣</span><span class="class-task-card__body"><strong><?= htmlspecialchars($tf['titulo']) ?></strong><?php if (!empty($tf['entrega_ate'])): ?><small>Prazo <?= date('d/m', strtotime($tf['entrega_ate'])) ?></small><?php else: ?><small><?= (int) $tf['n_entregas'] ?> entrega(s) · <?= (int) $tf['n_corrigidas'] ?> corrigida(s)</small><?php endif; ?><?php if ((int) $tf['n_entregas'] > 0): ?><em><?= (int) $tf['n_entregas'] ?> entregas</em><?php endif; ?></span><b>›</b></a><?php endforeach; endif; ?></section><?php endforeach; ?>
                </div><?php endif; ?>
            </section>

            <div class="class-lower-grid">
                <section class="class-panel class-students-panel" id="alunos"><div class="class-panel-head"><div><h2>Alunos <span><?= count($alunosAtivos) ?></span></h2><p>Matrículas ativas nesta turma.</p></div><a href="/aluno/cadastro">Como entrar →</a></div>
                    <?php if (empty($alunosAtivos)): ?><div class="class-empty class-empty--small"><p>Nenhum aluno matriculado. Os alunos podem se cadastrar em <strong>/aluno/cadastro</strong> e escolher esta turma.</p></div><?php else: ?><div class="class-student-grid"><?php foreach ($alunosAtivos as $i => $al): ?><div class="class-student"><span class="class-student__avatar tone-<?= $i % 5 ?>"><?= htmlspecialchars($iniciais($al['aluno_nome'])) ?></span><strong><?= htmlspecialchars($al['aluno_nome']) ?></strong><small><?= $al['last_login_at'] ? 'Acesso ' . date('d/m', strtotime($al['last_login_at'])) : 'Sem acesso recente' ?></small><form method="post" action="/turmas/<?= (int) $turma['id'] ?>/alunos/<?= (int) $al['matricula_id'] ?>/situacao"><?= \App\Services\Csrf::campo() ?><button type="submit">Inativar</button></form></div><?php endforeach; ?></div><?php endif; ?>
                    <?php $inativos = array_values(array_filter($alunos ?? [], static fn ($a) => ($a['situacao'] ?? '') !== 'ativa')); if ($inativos): ?><details class="class-inactive-list"><summary>Ver matrículas inativas (<?= count($inativos) ?>)</summary><?php foreach ($inativos as $al): ?><div class="class-inactive-student"><span><?= htmlspecialchars($al['aluno_nome']) ?></span><form method="post" action="/turmas/<?= (int) $turma['id'] ?>/alunos/<?= (int) $al['matricula_id'] ?>/situacao"><?= \App\Services\Csrf::campo() ?><button type="submit" class="btn btn--ghost btn--sm">Reativar</button></form></div><?php endforeach; ?></details><?php endif; ?>
                </section>
                <section class="class-panel class-notices-panel" id="avisos"><div class="class-panel-head"><div><h2>Mural de avisos</h2><p>Comunicados recentes da turma.</p></div><a href="#novo-aviso">＋ Novo aviso</a></div>
                    <?php if (empty($avisos)): ?><div class="class-empty class-empty--small"><p>Nenhum aviso publicado.</p></div><?php else: ?><div class="class-notice-list"><?php foreach (array_slice($avisos,0,5) as $av): ?><article><span class="class-notice-dot"></span><div><small><?= date('d/m · H:i', strtotime($av['created_at'])) ?><?= !empty($av['fixado']) ? ' · Fixado' : '' ?></small><strong><?= htmlspecialchars($av['titulo']) ?></strong><p><?= htmlspecialchars(mb_strimwidth($av['corpo'],0,125,'…')) ?></p></div><form method="post" action="/avisos/<?= (int) $av['id'] ?>/excluir" onsubmit="return confirm('Excluir este aviso?');"><?= \App\Services\Csrf::campo() ?><button type="submit" aria-label="Excluir aviso">×</button></form></article><?php endforeach; ?></div><?php endif; ?>
                </section>
            </div>
        </main>

        <aside class="class-dashboard-side">
            <section class="class-side-card class-suggestions"><p class="class-side-kicker">ATALHOS</p><h2>Planeje com suas matérias</h2><p>Abra uma matéria para usar os recursos ligados aos temas da aula.</p><?php if ($materias): foreach (array_slice($materias,0,5) as $m): ?><a class="class-suggestion" href="/disciplinas/<?= (int) $m['id'] ?>"><span>✦</span><strong><?= htmlspecialchars($m['nome']) ?></strong><b>›</b></a><?php endforeach; else: ?><div class="class-side-empty">Cadastre uma matéria para começar seu planejamento.</div><?php endif; ?><a href="#nova-materia" class="class-side-primary">＋ Criar matéria</a></section>
            <section class="class-side-card class-summary"><div class="class-side-heading"><span>▦</span><div><h2>Resumo da turma</h2><small>Dados cadastrados</small></div></div><div class="class-summary-grid"><div><i class="tone-green">✓</i><strong><?= count($tarefas ?? []) ?></strong><small>Tarefas</small></div><div><i class="tone-blue">▤</i><strong><?= count($materias ?? []) ?></strong><small>Matérias</small></div><div><i class="tone-purple">♧</i><strong><?= count($alunosAtivos) ?></strong><small>Alunos ativos</small></div><div><i class="tone-gold">◉</i><strong><?= count($avisos ?? []) ?></strong><small>Avisos</small></div></div><div class="class-summary-note"><span>✦</span><p>O painel reúne as informações cadastradas para esta turma.</p></div></section>
            <details class="class-side-card class-form-details" id="nova-materia"><summary><span>＋</span><strong>Nova matéria</strong><small>Adicionar uma disciplina</small></summary><form method="post" action="/turmas/<?= (int) $turma['id'] ?>/materias" class="form"><?= \App\Services\Csrf::campo() ?><label>Nome da matéria<input type="text" name="nome" placeholder="Ex: Matemática" required></label><small>A matéria herda a etapa e o ano da turma.</small><button type="submit" class="btn btn--primary">Criar matéria</button></form></details>
            <details class="class-side-card class-form-details" id="nova-tarefa"><summary><span>▣</span><strong>Nova tarefa</strong><small>Criar como rascunho</small></summary><form method="post" action="/turmas/<?= (int) $turma['id'] ?>/tarefas" class="form"><?= \App\Services\Csrf::campo() ?><label>Título<input type="text" name="titulo" placeholder="Ex: Lista de exercícios" required></label><label>Enunciado / instruções<textarea name="descricao" rows="3" placeholder="O que o aluno deve fazer"></textarea></label><label>Tipo de entrega<select name="tipo_entrega"><?php foreach (($tiposEntrega ?? []) as $k => $rotulo): ?><option value="<?= htmlspecialchars($k) ?>" <?= $k === 'ambos' ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option><?php endforeach; ?></select></label><label>Prazo (opcional)<input type="datetime-local" name="entrega_ate"></label><button type="submit" class="btn btn--primary">Criar tarefa</button><small>A tarefa começa como rascunho. Publique na tela dela.</small></form></details>
            <details class="class-side-card class-form-details" id="novo-aviso"><summary><span>◉</span><strong>Novo aviso</strong><small>Publicar no mural</small></summary><form method="post" action="/turmas/<?= (int) $turma['id'] ?>/avisos" class="form"><?= \App\Services\Csrf::campo() ?><label>Título<input type="text" name="titulo" placeholder="Ex: Prova na próxima semana" required></label><label>Texto<textarea name="corpo" rows="3" required></textarea></label><label class="check-inline"><input type="checkbox" name="fixado" value="1"> Fixar no topo / painel do aluno</label><button type="submit" class="btn btn--primary">Publicar aviso</button></form></details>
            <form method="post" action="/turmas/<?= (int) $turma['id'] ?>/excluir" class="class-delete-bottom" onsubmit="return confirm('Excluir a turma e tudo dentro dela?');"><?= \App\Services\Csrf::campo() ?><button type="submit" class="btn btn--danger btn--sm">Excluir turma</button></form>
        </aside>
    </div>
</div>
<script>
(() => {
    const openPanelFromHash = () => {
        const panel = document.querySelector('.class-form-details' + location.hash);
        if (panel) panel.open = true;
    };
    document.querySelectorAll('.class-dashboard a[href^="#"]').forEach(link => link.addEventListener('click', () => setTimeout(openPanelFromHash, 0)));
    window.addEventListener('hashchange', openPanelFromHash);
    openPanelFromHash();
})();
</script>
