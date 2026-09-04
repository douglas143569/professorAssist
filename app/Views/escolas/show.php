    <p class="breadcrumb"><a href="/escolas">Escolas</a> / <?= htmlspecialchars($escola['nome']) ?></p>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($escola['nome']) ?></h1>
            <p class="muted">
                <?php if (!empty($escola['rede'])): ?>
                    <span class="tag"><?= htmlspecialchars($redes[$escola['rede']] ?? $escola['rede']) ?></span>
                <?php endif; ?>
                <?php if (!empty($escola['cidade'])): ?>
                    <?= htmlspecialchars($escola['cidade']) ?><?= !empty($escola['uf']) ? '/' . htmlspecialchars($escola['uf']) : '' ?>
                <?php endif; ?>
                <?php if (!empty($escola['telefone'])): ?>
                    · <?= htmlspecialchars($escola['telefone']) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="split">
        <section>
            <h2>Turmas</h2>
            <?php if (empty($turmas)): ?>
                <div class="card card--empty">
                    <p>Nenhuma turma nesta escola ainda. Crie a primeira ao lado (ex: 6º ano A).</p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($turmas as $t): ?>
                        <div class="card turma-card">
                            <a class="turma-card__main" href="/turmas/<?= (int) $t['id'] ?>">
                                <?php if (!empty($t['etapa'])): ?><span class="tag"><?= htmlspecialchars($t['etapa']) ?></span><?php endif; ?>
                                <h3><?= htmlspecialchars($t['nome']) ?></h3>
                                <p class="muted">
                                    <?php if (!empty($t['ano_serie'])): ?><?= htmlspecialchars($t['ano_serie']) ?> · <?php endif; ?>
                                    <?= (int) $t['n_materias'] ?> matéria(s)
                                </p>
                            </a>
                            <div class="turma-card__foot">
                                <a href="/turmas/<?= (int) $t['id'] ?>" class="btn btn--ghost btn--sm">Abrir</a>
                                <form method="post" action="/turmas/<?= (int) $t['id'] ?>/excluir"
                                      onsubmit="return confirm('Excluir a turma &quot;<?= htmlspecialchars($t['nome'], ENT_QUOTES) ?>&quot; e tudo dentro dela (matérias, temas, conteúdos e questões)?');">
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
                <h3>Nova turma</h3>
                <form method="post" action="/escolas/<?= (int) $escola['id'] ?>/turmas" class="form">
            <?= \App\Services\Csrf::campo() ?>
                    <label>Nome da turma
                        <input type="text" name="nome" placeholder="Ex: 6º ano A" required>
                    </label>
                    <label>Etapa
                        <select name="etapa">
                            <option value="EF">Ensino Fundamental</option>
                            <option value="EM">Ensino Médio</option>
                        </select>
                    </label>
                    <label>Ano / Série <span class="muted">(opcional)</span>
                        <input type="text" name="ano_serie" placeholder="Ex: 6º ano">
                    </label>
                    <button type="submit" class="btn btn--primary">Criar turma</button>
                </form>
            </div>

            <div class="card" style="margin-top:12px;">
                <h3>Dados da escola</h3>
                <form method="post" action="/escolas/<?= (int) $escola['id'] ?>" class="form">
            <?= \App\Services\Csrf::campo() ?>
                    <label>Nome
                        <input type="text" name="nome" value="<?= htmlspecialchars($escola['nome']) ?>" required>
                    </label>
                    <label>Rede
                        <select name="rede">
                            <option value="">— não informar —</option>
                            <?php foreach ($redes as $valor => $rotulo): ?>
                                <option value="<?= htmlspecialchars($valor) ?>" <?= ($escola['rede'] ?? '') === $valor ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($rotulo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Cidade
                        <input type="text" name="cidade" value="<?= htmlspecialchars($escola['cidade'] ?? '') ?>">
                    </label>
                    <label>UF
                        <input type="text" name="uf" maxlength="2" value="<?= htmlspecialchars($escola['uf'] ?? '') ?>">
                    </label>
                    <label>Endereço
                        <input type="text" name="endereco" value="<?= htmlspecialchars($escola['endereco'] ?? '') ?>">
                    </label>
                    <label>Telefone
                        <input type="text" name="telefone" value="<?= htmlspecialchars($escola['telefone'] ?? '') ?>">
                    </label>
                    <button type="submit" class="btn btn--ghost">Salvar dados</button>
                </form>
            </div>

            <form method="post" action="/escolas/<?= (int) $escola['id'] ?>/excluir"
                  onsubmit="return confirm('Excluir a escola e tudo dentro dela?');" style="margin-top:12px;">
            <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--danger btn--sm">Excluir escola</button>
            </form>
        </aside>
    </div>
