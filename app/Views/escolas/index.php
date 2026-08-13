    <div class="page-header">
        <div>
            <h1>Escolas</h1>
            <p class="muted">Comece pela escola → turma → matéria → tema da aula.</p>
        </div>
    </div>

    <div class="split">
        <section>
            <?php if (empty($escolas)): ?>
                <div class="card card--empty">
                    <p>Nenhuma escola cadastrada. Cadastre a primeira ao lado — as turmas ficam dentro dela.</p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($escolas as $e): ?>
                        <div class="card turma-card">
                            <a class="turma-card__main" href="/escolas/<?= (int) $e['id'] ?>">
                                <?php if (!empty($e['rede'])): ?>
                                    <span class="tag"><?= htmlspecialchars($redes[$e['rede']] ?? $e['rede']) ?></span>
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($e['nome']) ?></h3>
                                <p class="muted">
                                    <?php if (!empty($e['cidade'])): ?>
                                        <?= htmlspecialchars($e['cidade']) ?><?= !empty($e['uf']) ? '/' . htmlspecialchars($e['uf']) : '' ?> ·
                                    <?php endif; ?>
                                    <?= (int) $e['n_turmas'] ?> turma(s)
                                </p>
                            </a>
                            <div class="turma-card__foot">
                                <a href="/escolas/<?= (int) $e['id'] ?>" class="btn btn--ghost btn--sm">Abrir</a>
                                <form method="post" action="/escolas/<?= (int) $e['id'] ?>/excluir"
                                      onsubmit="return confirm('Excluir a escola &quot;<?= htmlspecialchars($e['nome'], ENT_QUOTES) ?>&quot; e tudo dentro dela (turmas, matérias, temas, conteúdos e questões)?');">
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
                <h3>Nova escola</h3>
                <form method="post" action="/escolas" class="form">
                    <label>Nome da escola
                        <input type="text" name="nome" placeholder="Ex: EMEF Machado de Assis" required>
                    </label>
                    <label>Rede
                        <select name="rede">
                            <option value="">— não informar —</option>
                            <?php foreach ($redes as $valor => $rotulo): ?>
                                <option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($rotulo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Cidade <span class="muted">(opcional)</span>
                        <input type="text" name="cidade" placeholder="Ex: Campinas">
                    </label>
                    <label>UF <span class="muted">(opcional)</span>
                        <input type="text" name="uf" maxlength="2" placeholder="SP">
                    </label>
                    <label>Endereço <span class="muted">(opcional)</span>
                        <input type="text" name="endereco" placeholder="Rua, número, bairro">
                    </label>
                    <label>Telefone <span class="muted">(opcional)</span>
                        <input type="text" name="telefone" placeholder="(19) 3333-0000">
                    </label>
                    <button type="submit" class="btn btn--primary">Cadastrar escola</button>
                </form>
            </div>
        </aside>
    </div>
