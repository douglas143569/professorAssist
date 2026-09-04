    <div class="page-header">
        <div>
            <h1>Contas</h1>
            <p class="muted">Quem entra no sistema e com que permissão.</p>
        </div>
    </div>

    <div class="card">
        <h3>Nova conta</h3>
        <form method="post" action="/admin/contas" class="form">
            <?= \App\Services\Csrf::campo() ?>
            <div class="form--inline">
                <label style="flex:1 1 220px;">Nome
                    <input type="text" name="name" required placeholder="Ex: Maria Silva">
                </label>
                <label style="flex:1 1 240px;">E-mail
                    <input type="email" name="email" required placeholder="maria@escola.com.br">
                </label>
                <label style="flex:0 1 180px;">Celular <span class="muted">(opcional)</span>
                    <input type="tel" name="celular" placeholder="(11) 98765-4321">
                </label>
                <label style="flex:1 1 180px;">Senha inicial
                    <input type="text" name="senha" required minlength="8" placeholder="mínimo 8 caracteres">
                </label>
                <label style="flex:0 1 170px;">Permissão
                    <select name="role">
                        <option value="professor">Professor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </label>
            </div>
            <p class="muted" style="font-size:0.82rem; margin:-4px 0 0;">
                <strong>Professor</strong> usa o sistema e vê apenas o próprio conteúdo.
                <strong>Administrador</strong> faz o mesmo e ainda gerencia contas nesta tela.
            </p>
            <div class="actions">
                <button type="submit" class="btn btn--primary">Criar conta</button>
            </div>
        </form>
    </div>

    <?php
        // Geracoes antigas ou de contas ja removidas ficam sem dono (a FK usa
        // ON DELETE SET NULL). O total continua certo -- e dinheiro gasto --
        // mas nao aparece na soma das contas abaixo. Sem esta nota, os numeros
        // parecem nao fechar.
        $semDono = round($gasto_total - array_sum($gastos), 2);
    ?>
    <?php
        $restante = $caixa > 0 ? max(0, $caixa - $gasto_total) : 0;
        $pctCaixa = $caixa > 0 ? min(100, (int) round($gasto_total / $caixa * 100)) : 0;
        $acabando = $caixa > 0 && $pctCaixa >= 80;
    ?>
    <div class="card card--ia <?= $acabando ? 'card--ia-alerta' : '' ?>">
        <h3>Caixa de IA</h3>

        <?php if ($caixa > 0): ?>
            <p class="ia-total">
                US$ <?= number_format($restante, 2, ',', '.') ?>
                <span class="ia-total__resto">
                    restantes do caixa de US$ <?= number_format($caixa, 2, ',', '.') ?>
                    que você definiu · valores estimados
                </span>
            </p>
            <div class="barra-teto barra-teto--grande"><span style="width:<?= $pctCaixa ?>%"></span></div>
            <p class="muted" style="margin:8px 0 0; font-size:0.85rem; line-height:1.6;">
                Já gastos <strong>US$ <?= number_format($gasto_total, 2, ',', '.') ?></strong> (<?= $pctCaixa ?>%).
                Todas as contas gastam deste mesmo caixa — existe uma única chave de API, a sua.
                Quando ele zerar, <strong>ninguém gera</strong> até você aumentar o
                <code>AI_TETO_TOTAL_USD</code> no <code>.env</code>.
            </p>

            <div class="ia-aviso">
                <strong>Estes números são deste sistema, não da Anthropic.</strong>
                O caixa é o limite que <em>você</em> definiu no <code>.env</code> — não é o saldo
                da sua conta. E o gasto é <em>estimado</em> aqui, multiplicando os tokens de cada
                geração pela tabela de preços do modelo.
                O saldo e a fatura reais ficam em
                <a href="https://platform.claude.com/cost" target="_blank" rel="noopener">platform.claude.com/cost</a>
                — não há API que devolva saldo restante, só o Console mostra.
            </div>
        <?php else: ?>
            <p class="ia-total">US$ <?= number_format($gasto_total, 2, ',', '.') ?></p>
            <p class="muted" style="margin:0; font-size:0.85rem; line-height:1.6;">
                Gasto de todas as contas. <strong>Não há caixa configurado</strong>
                (<code>AI_TETO_TOTAL_USD=0</code>), então não existe limite de gasto —
                a fatura cresce sem travas.
            </p>
        <?php endif; ?>

        <?php if ($semDono >= 0.01): ?>
            <p class="muted" style="margin:8px 0 0; font-size:0.8rem;">
                Inclui US$ <?= number_format($semDono, 2, ',', '.') ?> de gerações sem conta
                vinculada (feitas antes do login existir ou por contas já removidas).
            </p>
        <?php endif; ?>

        <?php if ($teto > 0): ?>
            <p class="muted" style="margin:8px 0 0; font-size:0.8rem;">
                Além do caixa, cada conta tem teto próprio de
                US$ <?= number_format($teto, 2, ',', '.') ?> (<code>AI_TETO_USD</code>).
            </p>
        <?php endif; ?>
    </div>

    <h2 style="margin-top:32px;"><?= count($contas) ?> conta(s)</h2>

    <div class="contas">
        <?php foreach ($contas as $c): ?>
            <?php
                $souEu = (int) $c['id'] === (int) $eu['id'];
                $ehAdmin = $c['role'] === \App\Models\User::ROLE_ADMIN;
                $ativo = (int) $c['ativo'] === 1;
            ?>
            <div class="card conta <?= $ativo ? '' : 'conta--inativa' ?>">
                <div class="conta__topo">
                    <div>
                        <h3>
                            <?= htmlspecialchars($c['name']) ?>
                            <?php if ($ehAdmin): ?><span class="badge-admin">admin</span><?php endif; ?>
                            <?php if ($souEu): ?><span class="tag tag--origem">você</span><?php endif; ?>
                            <?php if (!$ativo): ?><span class="badge badge--rascunho">desativada</span><?php endif; ?>
                        </h3>
                        <p class="muted" style="margin:0; font-size:0.86rem;">
                            <?= htmlspecialchars($c['email']) ?>
                            <?php if (!empty($c['celular'])): ?>
                                · <?= htmlspecialchars(\App\Models\User::formatarCelular($c['celular'])) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php
                        $gasto = $gastos[(int) $c['id']] ?? 0.0;
                        // Sem teto por conta, a barra mostra a fatia do CAIXA que esta
                        // pessoa consumiu -- e o que interessa num caixa compartilhado.
                        $base = $teto > 0 ? $teto : $caixa;
                        $pct = $base > 0 ? min(100, (int) round($gasto / $base * 100)) : 0;
                    ?>
                    <p class="muted conta__uso">
                        <?= (int) $c['n_escolas'] ?> escola(s) · <?= (int) $c['n_materias'] ?> matéria(s)<br>
                        <?php if (!empty($c['last_login_at'])): ?>
                            último acesso em <?= date('d/m/Y H:i', strtotime($c['last_login_at'])) ?>
                        <?php else: ?>
                            nunca entrou
                        <?php endif; ?>
                        <span class="conta__ia">
                            IA: <strong>US$ <?= number_format($gasto, 2, ',', '.') ?></strong>
                            <?php if ($base > 0): ?>
                                <?= $teto > 0 ? 'do teto de' : 'do caixa de' ?>
                                US$ <?= number_format($base, 2, ',', '.') ?>
                                <span class="barra-teto barra-teto--mini"><span style="width:<?= $pct ?>%"></span></span>
                            <?php endif; ?>
                        </span>
                    </p>
                </div>

                <div class="conta__acoes">
                    <form method="post" action="/admin/contas/<?= (int) $c['id'] ?>/ativo">
                        <?= \App\Services\Csrf::campo() ?>
                        <button type="submit" class="btn btn--sm <?= $ativo ? 'btn--danger' : 'btn--ghost' ?>"
                                <?= $souEu ? 'disabled title="Você não pode desativar a sua própria conta"' : '' ?>
                                <?= $ativo ? 'onclick="return confirm(\'Desativar esta conta? A pessoa deixa de entrar, mas o conteúdo dela é preservado.\');"' : '' ?>>
                            <?= $ativo ? 'Desativar acesso' : 'Reativar acesso' ?>
                        </button>
                    </form>

                    <form method="post" action="/admin/contas/<?= (int) $c['id'] ?>/papel">
                        <?= \App\Services\Csrf::campo() ?>
                        <button type="submit" class="btn btn--ghost btn--sm"
                                <?= $souEu ? 'disabled title="Você não pode mudar o seu próprio papel"' : '' ?>>
                            <?= $ehAdmin ? 'Tornar professor' : 'Tornar administrador' ?>
                        </button>
                    </form>

                    <form method="post" action="/admin/contas/<?= (int) $c['id'] ?>/senha" class="conta__senha">
                        <?= \App\Services\Csrf::campo() ?>
                        <input type="text" name="senha" minlength="8" required placeholder="nova senha (mín. 8)">
                        <button type="submit" class="btn btn--ghost btn--sm">Redefinir</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card" style="margin-top:24px; background:var(--surface-2);">
        <h3 style="font-size:0.92rem;">Por que não existe "excluir conta"</h3>
        <p class="muted" style="margin:0; font-size:0.86rem; line-height:1.6;">
            Escolas, matérias, temas, conteúdos, questões e provas pertencem ao professor no banco.
            Apagar a conta apagaria junto todo o trabalho dela, sem volta.
            <strong>Desativar</strong> resolve o mesmo problema: o login para na hora, a sessão aberta
            cai na página seguinte e nada do conteúdo se perde — se a pessoa voltar, é só reativar.
        </p>
    </div>
