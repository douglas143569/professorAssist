<?php
    $progresso = 25 + ($resumo['n_turmas'] > 0 ? 25 : 0) + ($resumo['n_materias'] > 0 ? 25 : 0) + (($resumo['n_conteudos'] + $resumo['n_questoes'] + $resumo['n_atividades']) > 0 ? 25 : 0);
    $proxPasso = $resumo['n_turmas'] === 0 ? 1 : ($resumo['n_materias'] === 0 ? 2 : (($resumo['n_conteudos'] + $resumo['n_questoes'] + $resumo['n_atividades']) === 0 ? 3 : 4));
?>
<div class="school-detail">
    <p class="breadcrumb"><a href="/escolas">Escolas</a> / <span><?= htmlspecialchars($escola['nome']) ?></span></p>
    <header class="school-detail-hero">
        <div class="school-detail-crest" aria-hidden="true">▦</div>
        <div class="school-detail-title"><h1><?= htmlspecialchars($escola['nome']) ?></h1><span class="school-detail-status"><i></i> Escola cadastrada</span>
            <p>Juntos por uma educação melhor!</p>
            <div class="school-detail-meta">
                <?php if (!empty($escola['rede'])): ?><span>⌖ <?= htmlspecialchars($redes[$escola['rede']] ?? $escola['rede']) ?></span><?php endif; ?>
                <?php if (!empty($escola['cidade']) || !empty($escola['uf'])): ?><span>⌖ <?= htmlspecialchars(trim(($escola['cidade'] ?? '') . (!empty($escola['uf']) ? ' - ' . $escola['uf'] : ''))) ?></span><?php endif; ?>
                <?php if (!empty($escola['telefone'])): ?><span>☎ <?= htmlspecialchars($escola['telefone']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="school-detail-quote"><span aria-hidden="true">🏫</span><strong>Educação transforma<br>realidades.</strong><small><?= htmlspecialchars($escola['nome']) ?></small></div>
    </header>

    <section class="school-stats" aria-label="Resumo da escola">
        <?php foreach ([['▣','Turmas','n_turmas','blue'],['▤','Matérias','n_materias','indigo'],['♧','Alunos','n_alunos','teal'],['▤','Conteúdos','n_conteudos','blue'],['✎','Questões','n_questoes','purple'],['▧','Atividades','n_atividades','yellow']] as [$icon,$label,$field,$color]): ?>
            <div class="school-stat"><span class="school-stat__icon is-<?= $color ?>"><?= $icon ?></span><div><strong><?= (int) $resumo[$field] ?></strong><small><?= $label ?></small></div></div>
        <?php endforeach; ?>
    </section>

    <div class="school-detail-columns">
        <div class="school-detail-main">
            <section class="school-classes">
                <div class="school-section-head"><div><h2>Turmas da escola</h2><p>Gerencie suas turmas e acesse seus recursos.</p></div><a class="btn btn--primary" href="#nova-turma">＋ &nbsp; Criar turma</a></div>
                <?php if (empty($turmas)): ?>
                    <div class="school-empty"><span>♧</span><div><strong>Comece criando uma turma</strong><p>Depois, você poderá adicionar matérias, alunos e conteúdos.</p></div><a href="#nova-turma">Criar primeira turma →</a></div>
                <?php else: ?>
                    <div class="school-class-grid">
                        <?php foreach ($turmas as $indice => $t): ?>
                            <article class="school-class-card school-class-card--<?= $indice % 3 ?>">
                                <div class="school-class-card__top"><span class="school-stage"><?= htmlspecialchars($t['etapa'] ?? 'Turma') ?></span><span class="school-class-card__number"><?= sprintf('%02d', $indice + 1) ?></span></div>
                                <h3><?= htmlspecialchars($t['nome']) ?></h3>
                                <?php if (!empty($t['ano_serie'])): ?><p class="school-class-grade"><?= htmlspecialchars($t['ano_serie']) ?></p><?php endif; ?>
                                <div class="school-class-facts"><span>♧ &nbsp;<?= (int) $t['n_alunos'] ?> alunos</span><span>▤ &nbsp;<?= (int) $t['n_materias'] ?> matérias</span><span>✎ &nbsp;<?= (int) $t['n_materias'] ?> <?= (int) $t['n_materias'] === 1 ? 'área de estudo' : 'áreas de estudo' ?></span></div>
                                <span class="school-class-ready"><i></i><?= (int) $t['n_materias'] > 0 ? 'Matérias cadastradas' : 'Pronta para configurar' ?></span>
                                <a href="/turmas/<?= (int) $t['id'] ?>" class="school-class-open">Entrar na turma <span>→</span></a>
                                <form class="school-class-delete" method="post" action="/turmas/<?= (int) $t['id'] ?>/excluir" onsubmit="return confirm('Excluir a turma &quot;<?= htmlspecialchars($t['nome'], ENT_QUOTES) ?>&quot; e tudo dentro dela (matérias, temas, conteúdos e questões)?');">
                                    <?= \App\Services\Csrf::campo() ?><button type="submit" aria-label="Excluir turma" title="Excluir turma">⋮</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="school-next">
                <div class="school-section-head"><div><h2>Próximos passos</h2><p>Continue organizando esta escola para aproveitar seus recursos.</p></div></div>
                <div class="school-progress-steps">
                    <?php foreach ([['Escola cadastrada','Concluído'],['Criar turmas',$resumo['n_turmas'] > 0 ? 'Concluído' : 'Pendente'],['Cadastrar matérias',$resumo['n_materias'] > 0 ? 'Concluído' : 'Pendente'],['Criar conteúdos',$resumo['n_conteudos'] + $resumo['n_questoes'] + $resumo['n_atividades'] > 0 ? 'Em andamento' : 'Pendente']] as $i => [$step,$state]): $done = $i === 0 || ($i === 1 && $resumo['n_turmas'] > 0) || ($i === 2 && $resumo['n_materias'] > 0); ?>
                        <div class="school-progress-step <?= $done ? 'is-done' : ($i + 1 === $proxPasso ? 'is-current' : '') ?>"><b><?= $done ? '✓' : $i + 1 ?></b><span><strong><?= $step ?></strong><small><?= $state ?></small></span></div><?php if ($i < 3): ?><i aria-hidden="true">→</i><?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="school-action-cards">
                    <article><span class="school-action-icon is-green">✦</span><h3>Criar turma</h3><p>Adicione uma turma e organize os alunos e as matérias.</p><a href="#nova-turma" class="btn btn--primary">Criar turma →</a></article>
                    <article><span class="school-action-icon is-blue">▣</span><h3>Organizar matérias</h3><p>Entre em uma turma para cadastrar matérias e temas.</p><?php if (!empty($turmas)): ?><a href="/turmas/<?= (int) $turmas[0]['id'] ?>" class="btn btn--ghost">Ver turma →</a><?php else: ?><a href="#nova-turma" class="btn btn--ghost">Criar turma primeiro</a><?php endif; ?></article>
                </div>
            </section>
        </div>

        <aside class="school-detail-side">
            <section class="school-setup-card"><div class="school-side-heading"><div><h2>Configuração da escola</h2><p>Progresso da organização</p></div><strong><?= $progresso ?>%</strong></div><div class="school-progress"><span style="width:<?= $progresso ?>%"></span></div><small>Complete as etapas para organizar turmas, matérias e conteúdos.</small></section>
            <section class="school-settings-card"><div class="school-side-heading"><div><h2>Dados da escola</h2><p>Atualize as informações cadastradas.</p></div><span>⚙</span></div>
                <form method="post" action="/escolas/<?= (int) $escola['id'] ?>" class="form school-edit-form">
                    <?= \App\Services\Csrf::campo() ?>
                    <label>Nome da escola<input type="text" name="nome" value="<?= htmlspecialchars($escola['nome']) ?>" required></label>
                    <label>Rede de ensino<select name="rede"><option value="">Não informar</option><?php foreach ($redes as $valor => $rotulo): ?><option value="<?= htmlspecialchars($valor) ?>" <?= ($escola['rede'] ?? '') === $valor ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option><?php endforeach; ?></select></label>
                    <div class="school-edit-row"><label>Cidade<input type="text" name="cidade" value="<?= htmlspecialchars($escola['cidade'] ?? '') ?>"></label><label>UF<input type="text" name="uf" maxlength="2" value="<?= htmlspecialchars($escola['uf'] ?? '') ?>"></label></div>
                    <label>Endereço<input type="text" name="endereco" value="<?= htmlspecialchars($escola['endereco'] ?? '') ?>"></label>
                    <label>Telefone<input type="tel" name="telefone" value="<?= htmlspecialchars($escola['telefone'] ?? '') ?>"></label>
                    <button type="submit" class="btn btn--primary">Salvar dados da escola</button>
                </form>
                <form method="post" action="/escolas/<?= (int) $escola['id'] ?>/excluir" onsubmit="return confirm('Excluir esta escola e tudo dentro dela?');" class="school-delete-form">
                    <?= \App\Services\Csrf::campo() ?><button type="submit" class="btn btn--danger btn--sm">Excluir escola</button>
                </form>
            </section>
            <section class="school-impact"><div class="school-side-heading"><div><h2>Resumo da escola</h2><p>Recursos cadastrados</p></div><span>▥</span></div>
                <div><span>♧</span><strong><?= (int) $resumo['n_alunos'] ?> alunos ativos</strong></div><div><span>▤</span><strong><?= (int) $resumo['n_conteudos'] ?> conteúdos</strong></div><div><span>✎</span><strong><?= (int) $resumo['n_questoes'] ?> questões</strong></div><div><span>▧</span><strong><?= (int) $resumo['n_atividades'] ?> atividades</strong></div>
            </section>
        </aside>
    </div>

    <section class="school-new-class card" id="nova-turma"><div><span>＋</span><div><h2>Criar turma nesta escola</h2><p>Cadastre a próxima turma para continuar a organização.</p></div></div>
        <form method="post" action="/escolas/<?= (int) $escola['id'] ?>/turmas" class="form school-new-class-form">
            <?= \App\Services\Csrf::campo() ?>
            <label>Nome da turma<input type="text" name="nome" placeholder="Ex: 6º ano A" required></label>
            <label>Etapa<select name="etapa"><option value="EF">Ensino Fundamental</option><option value="EM">Ensino Médio</option></select></label>
            <label>Ano / Série <span>(opcional)</span><input type="text" name="ano_serie" placeholder="Ex: 6º ano"></label>
            <button type="submit" class="btn btn--primary">Criar turma</button>
        </form>
    </section>
</div>
