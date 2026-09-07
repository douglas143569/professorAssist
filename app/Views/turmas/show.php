    <p class="breadcrumb">
        <a href="/escolas">Escolas</a> /
        <a href="/escolas/<?= (int) $turma['escola_id'] ?>"><?= htmlspecialchars($turma['escola_nome'] ?? 'Escola') ?></a> /
        <?= htmlspecialchars($turma['nome']) ?>
    </p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($turma['nome']) ?></h1>
            <p class="muted">
                <?php if (!empty($turma['etapa'])): ?><span class="tag"><?= htmlspecialchars($turma['etapa']) ?></span><?php endif; ?>
                <?= htmlspecialchars($turma['ano_serie'] ?? '') ?>
            </p>
        </div>
    </div>

    <?php
        $ativos = 0;
        foreach (($alunos ?? []) as $__a) {
            if (($__a['situacao'] ?? '') === 'ativa') {
                $ativos++;
            }
        }
        $tarefasPub = array_values(array_filter($tarefas ?? [], fn ($t) => (int) $t['publicada'] === 1));
        $graf = ['labels' => [], 'pendente' => [], 'entregue' => [], 'corrigida' => []];
        foreach ($tarefasPub as $t) {
            $ent = (int) $t['n_entregas'];
            $cor = (int) $t['n_corrigidas'];
            $graf['labels'][] = mb_strimwidth($t['titulo'], 0, 22, '…');
            $graf['pendente'][] = max(0, $ativos - $ent);
            $graf['entregue'][] = max(0, $ent - $cor);
            $graf['corrigida'][] = $cor;
        }
    ?>
    <?php if ($tarefasPub && $ativos > 0): ?>
        <div class="chart-card js-reveal" style="margin-bottom:24px;">
            <h3>Entregas por tarefa</h3>
            <div class="chart-box" id="chart-entregas"></div>
        </div>
        <script src="/assets/js/frappe-charts.min.umd.js"></script>
        <script>
            (function () {
                if (!window.frappe || !frappe.Chart) return;
                var semAnim = window.matchMedia
                    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                new frappe.Chart('#chart-entregas', {
                    type: 'bar', height: 240, animate: !semAnim,
                    barOptions: { stacked: true, spaceRatio: 0.4 },
                    colors: ['#c8912f', '#2f6bd8', '#0f7a54'],
                    data: {
                        labels: <?= json_encode($graf['labels']) ?>,
                        datasets: [
                            { name: 'Pendente', values: <?= json_encode($graf['pendente']) ?> },
                            { name: 'Entregue', values: <?= json_encode($graf['entregue']) ?> },
                            { name: 'Corrigida', values: <?= json_encode($graf['corrigida']) ?> }
                        ]
                    }
                });
            })();
        </script>
    <?php endif; ?>

    <div class="split">
        <section>
            <h2>Matérias</h2>
            <?php if (empty($materias)): ?>
                <div class="card card--empty">
                    <p>Nenhuma matéria ainda. Adicione uma ao lado (ex: Matemática, Português).</p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($materias as $m): ?>
                        <div class="card turma-card">
                            <a class="turma-card__main" href="/disciplinas/<?= (int) $m['id'] ?>">
                                <span class="tag"><?= htmlspecialchars($m['etapa']) ?></span>
                                <h3><?= htmlspecialchars($m['nome']) ?></h3>
                            </a>
                            <div class="turma-card__foot">
                                <a href="/disciplinas/<?= (int) $m['id'] ?>" class="btn btn--ghost btn--sm">Abrir</a>
                                <form method="post" action="/disciplinas/<?= (int) $m['id'] ?>/excluir"
                                      onsubmit="return confirm('Excluir a matéria &quot;<?= htmlspecialchars($m['nome'], ENT_QUOTES) ?>&quot; e todos os seus temas, conteúdos e questões?');">
            <?= \App\Services\Csrf::campo() ?>
                                    <button type="submit" class="btn btn--danger btn--sm">Excluir</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside>
            <div class="card">
                <h3>Nova matéria</h3>
                <form method="post" action="/turmas/<?= (int) $turma['id'] ?>/materias" class="form">
            <?= \App\Services\Csrf::campo() ?>
                    <label>Nome da matéria
                        <input type="text" name="nome" placeholder="Ex: Matemática" required>
                    </label>
                    <p class="muted" style="font-size:0.82rem;">A matéria herda a etapa/ano da turma.</p>
                    <button type="submit" class="btn btn--primary">Criar matéria</button>
                </form>
            </div>

            <form method="post" action="/turmas/<?= (int) $turma['id'] ?>/excluir"
                  onsubmit="return confirm('Excluir a turma e tudo dentro dela?');" style="margin-top:12px;">
            <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--danger btn--sm">Excluir turma</button>
            </form>
        </aside>
    </div>

    <div class="split" style="margin-top:8px;">
        <section>
            <h2>Alunos <span class="muted" style="font-weight:400;">(<?= count($alunos ?? []) ?>)</span></h2>
            <?php if (empty($alunos)): ?>
                <div class="card card--empty">
                    <p>Nenhum aluno matriculado. Os alunos se cadastram em
                       <strong>/aluno/cadastro</strong> escolhendo esta turma.</p>
                </div>
            <?php else: ?>
                <div class="card">
                    <table class="tabela">
                        <thead>
                            <tr><th>Nome</th><th>Situação</th><th>Último acesso</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($alunos as $al): ?>
                            <tr>
                                <td><?= htmlspecialchars($al['aluno_nome']) ?></td>
                                <td>
                                    <?php if ($al['situacao'] === 'ativa'): ?>
                                        <span class="badge badge--corrigida">Ativa</span>
                                    <?php else: ?>
                                        <span class="badge badge--rascunho">Inativa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="muted"><?= $al['last_login_at'] ? date('d/m/Y H:i', strtotime($al['last_login_at'])) : '—' ?></td>
                                <td style="text-align:right;">
                                    <form method="post" action="/turmas/<?= (int) $turma['id'] ?>/alunos/<?= (int) $al['matricula_id'] ?>/situacao">
                                        <?= \App\Services\Csrf::campo() ?>
                                        <button type="submit" class="btn btn--ghost btn--sm">
                                            <?= $al['situacao'] === 'ativa' ? 'Inativar' : 'Reativar' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h2>Tarefas <span class="muted" style="font-weight:400;">(<?= count($tarefas ?? []) ?>)</span></h2>
            <?php if (empty($tarefas)): ?>
                <div class="card card--empty"><p>Nenhuma tarefa criada para esta turma.</p></div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($tarefas as $tf): ?>
                        <a class="card card--link" href="/tarefas/<?= (int) $tf['id'] ?>">
                            <?php if ((int) $tf['publicada'] === 1): ?>
                                <span class="badge badge--corrigida">Publicada</span>
                            <?php else: ?>
                                <span class="badge badge--rascunho">Rascunho</span>
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($tf['titulo']) ?></h3>
                            <p class="muted">
                                <?= (int) $tf['n_entregas'] ?> entrega(s) · <?= (int) $tf['n_corrigidas'] ?> corrigida(s)
                                <?php if (!empty($tf['entrega_ate'])): ?>
                                    · até <?= date('d/m/Y H:i', strtotime($tf['entrega_ate'])) ?>
                                <?php endif; ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h2>Mural de avisos</h2>
            <?php if (empty($avisos)): ?>
                <div class="card card--empty"><p>Nenhum aviso publicado.</p></div>
            <?php else: ?>
                <?php foreach ($avisos as $av): ?>
                    <div class="card">
                        <p class="muted" style="margin-bottom:4px;">
                            <?php if (!empty($av['fixado'])): ?><span class="badge badge--entregue">Fixado</span><?php endif; ?>
                            <small><?= date('d/m/Y H:i', strtotime($av['created_at'])) ?></small>
                        </p>
                        <h3><?= htmlspecialchars($av['titulo']) ?></h3>
                        <p style="white-space:pre-wrap;"><?= htmlspecialchars($av['corpo']) ?></p>
                        <form method="post" action="/avisos/<?= (int) $av['id'] ?>/excluir"
                              onsubmit="return confirm('Excluir este aviso?');">
                            <?= \App\Services\Csrf::campo() ?>
                            <button type="submit" class="btn btn--danger btn--sm">Excluir</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <aside>
            <div class="card">
                <h3>Nova tarefa</h3>
                <form method="post" action="/turmas/<?= (int) $turma['id'] ?>/tarefas" class="form">
                    <?= \App\Services\Csrf::campo() ?>
                    <label>Título
                        <input type="text" name="titulo" placeholder="Ex: Lista de exercícios 3" required>
                    </label>
                    <label>Enunciado / instruções
                        <textarea name="descricao" rows="4" placeholder="O que o aluno deve fazer"></textarea>
                    </label>
                    <label>Tipo de entrega
                        <select name="tipo_entrega">
                            <?php foreach (($tiposEntrega ?? []) as $k => $rotulo): ?>
                                <option value="<?= $k ?>" <?= $k === 'ambos' ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Prazo de entrega (opcional)
                        <input type="datetime-local" name="entrega_ate">
                    </label>
                    <button type="submit" class="btn btn--primary">Criar tarefa</button>
                </form>
                <p class="muted" style="font-size:.82rem;">A tarefa nasce como rascunho. Publique na tela dela.</p>
            </div>

            <div class="card" style="margin-top:12px;">
                <h3>Novo aviso</h3>
                <form method="post" action="/turmas/<?= (int) $turma['id'] ?>/avisos" class="form">
                    <?= \App\Services\Csrf::campo() ?>
                    <label>Título
                        <input type="text" name="titulo" placeholder="Ex: Prova na próxima semana" required>
                    </label>
                    <label>Texto
                        <textarea name="corpo" rows="4" required></textarea>
                    </label>
                    <label class="check-inline">
                        <input type="checkbox" name="fixado" value="1"> Fixar no topo / painel do aluno
                    </label>
                    <button type="submit" class="btn btn--primary">Publicar aviso</button>
                </form>
            </div>
        </aside>
    </div>
