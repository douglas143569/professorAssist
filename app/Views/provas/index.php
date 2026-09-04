    <div class="page-header">
        <div>
            <h1>Provas</h1>
            <p class="muted">Monte a prova sorteando do seu banco de questões aprovadas.</p>
        </div>
    </div>

    <div class="card">
        <h3>Nova prova</h3>

        <?php if (empty($materias)): ?>
            <p class="muted">
                Você ainda não tem matérias. Crie uma turma e uma matéria em
                <a href="/escolas">Escolas</a> antes de montar uma prova.
            </p>
        <?php else: ?>
            <?php
                $totalAprovadas = 0;
                foreach ($aprovadas as $contagem) {
                    $totalAprovadas += $contagem['total'];
                }
            ?>

            <?php if ($totalAprovadas === 0): ?>
                <div class="aviso-vazio">
                    <strong>Você ainda não tem nenhuma questão aprovada.</strong>
                    <p>
                        A prova só usa questões que você já revisou e aprovou — é o que impede
                        uma questão gerada por IA, com erro de conteúdo ou de gabarito, chegar
                        impressa na mão do aluno.
                    </p>
                    <p style="margin:0;">
                        Abra o banco de uma matéria em <a href="/escolas">Escolas → matéria → Banco de questões</a>,
                        revise as questões e clique em <strong>Aprovar</strong>.
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="/provas" class="form">
            <?= \App\Services\Csrf::campo() ?>
                <div class="form--inline">
                    <label style="flex:1 1 260px;">Matéria
                        <select name="disciplina_id" required>
                            <?php foreach ($materias as $m): ?>
                                <?php $c = $aprovadas[(int) $m['id']] ?? ['facil' => 0, 'media' => 0, 'dificil' => 0, 'total' => 0]; ?>
                                <option value="<?= (int) $m['id'] ?>">
                                    <?= htmlspecialchars($m['nome']) ?>
                                    — <?= $c['total'] ?> aprovada(s):
                                    <?= $c['facil'] ?>F / <?= $c['media'] ?>M / <?= $c['dificil'] ?>D
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label style="flex:2 1 300px;">Título da prova
                        <input type="text" name="titulo" placeholder="Ex: Avaliação bimestral — 2º bimestre">
                    </label>
                </div>

                <fieldset class="prova-criterios">
                    <legend>Quantas questões de cada dificuldade?</legend>
                    <div class="form--inline">
                        <label style="flex:0 1 120px;">Fáceis
                            <input type="number" name="facil" value="3" min="0" max="30">
                        </label>
                        <label style="flex:0 1 120px;">Médias
                            <input type="number" name="media" value="5" min="0" max="30">
                        </label>
                        <label style="flex:0 1 120px;">Difíceis
                            <input type="number" name="dificil" value="2" min="0" max="30">
                        </label>
                    </div>
                    <p class="muted" style="font-size:0.82rem; margin:6px 0 0;">
                        Só entram questões <strong>aprovadas</strong>. Se faltar de alguma dificuldade,
                        a prova é completada com as disponíveis e você é avisado.
                    </p>
                </fieldset>

                <label>Instruções para o aluno <span class="muted">(opcional)</span>
                    <textarea name="instrucoes" rows="3" placeholder="Ex: Leia com atenção. Não é permitido consulta. Marque apenas uma alternativa por questão."></textarea>
                </label>

                <div class="actions">
                    <button type="submit" class="btn btn--primary">Montar prova</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <h2 style="margin-top:32px;">Suas provas</h2>

    <?php if (empty($provas)): ?>
        <div class="card card--empty">
            <p>Nenhuma prova ainda. Monte a primeira no formulário acima.</p>
        </div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($provas as $p): ?>
                <a class="card card--link" href="/provas/<?= (int) $p['id'] ?>">
                    <h3><?= htmlspecialchars($p['titulo']) ?></h3>
                    <p class="muted">
                        <?= htmlspecialchars($p['disciplina_nome']) ?>
                        <?php if (!empty($p['turma_nome'])): ?>
                            · <?= htmlspecialchars($p['turma_nome']) ?>
                        <?php endif; ?>
                    </p>
                    <p class="muted" style="font-size:0.82rem;">
                        <?= (int) $p['n_questoes'] ?> questão(ões) ·
                        <?= (int) $p['n_versoes'] ?> versão(ões)
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
