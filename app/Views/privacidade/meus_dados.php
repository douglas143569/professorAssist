<?php $e = fn($v) => htmlspecialchars((string) $v); ?>
    <div class="page-header">
        <div>
            <h1>Meus dados e privacidade</h1>
            <p class="muted">Seus direitos como titular de dados (LGPD, art. 18).</p>
        </div>
        <div class="actions">
            <a href="/privacidade" class="btn btn--ghost btn--sm" target="_blank" rel="noopener">Política de Privacidade</a>
            <a href="/cookies" class="btn btn--ghost btn--sm" target="_blank" rel="noopener">Política de Cookies</a>
        </div>
    </div>

    <div class="card">
        <h3>Dados da sua conta</h3>
        <dl class="lgpd-dados">
            <div><dt>Nome</dt><dd><?= $e($conta['name']) ?></dd></div>
            <div><dt>E-mail</dt><dd><?= $e($conta['email']) ?></dd></div>
            <div><dt>Celular</dt><dd><?= $e($conta['celular'] ?? '—') ?></dd></div>
            <div><dt>Conta criada em</dt><dd><?= !empty($conta['created_at']) ? date('d/m/Y', strtotime($conta['created_at'])) : '—' ?></dd></div>
            <div><dt>Política aceita</dt><dd><?= !empty($conta['aceite_privacidade_em']) ? date('d/m/Y H:i', strtotime($conta['aceite_privacidade_em'])) . ' (versão ' . $e($conta['aceite_versao'] ?? '') . ')' : 'Não registrado' ?></dd></div>
        </dl>
    </div>

    <div class="card">
        <h3>Baixar uma cópia dos meus dados</h3>
        <p class="muted" style="margin-top:-4px;">
            Gera um arquivo <strong>JSON</strong> com os dados da sua conta e tudo o que você criou
            (escolas, turmas, temas, conteúdos, provas, tarefas, relatórios da creche, assinatura e registros de acesso).
            A senha nunca é incluída.
        </p>
        <a href="/meus-dados/exportar" class="btn btn--primary">⬇ Baixar meus dados</a>
    </div>

    <div class="card">
        <h3>Outros direitos</h3>
        <p style="margin:0;">
            Para correção de dados, informação sobre compartilhamento, oposição ou revogação de consentimento, e para pedidos de
            <strong>alunos e responsáveis</strong>, fale com a gente
            <?php if ($contato): ?>pelo e-mail <a href="mailto:<?= $e($contato) ?>"><?= $e($contato) ?></a><?php else: ?>pelo canal de suporte informado na contratação<?php endif; ?>.
            Respondemos em até 15 dias.
        </p>
    </div>

    <div class="card lgpd-perigo">
        <h3>Excluir minha conta</h3>
        <?php if (($conta['role'] ?? '') === 'admin'): ?>
            <p style="margin:0;">Contas de administrador não podem ser excluídas por aqui. Peça a outro administrador para rebaixar a sua conta antes.</p>
        <?php else: ?>
            <p class="muted" style="margin-top:-4px;">
                <strong>Isto é permanente.</strong> Apagamos tudo o que você criou (escolas, turmas, matérias, conteúdos, provas, tarefas, relatórios da creche)
                e os acessos dos alunos que só estavam nas suas turmas, inclusive as entregas enviadas.
                Sua conta é anonimizada e não pode mais entrar. Por lei, mantemos apenas os registros de pagamento (prazo fiscal)
                e de acesso (6 meses), sem seu nome, e-mail ou celular. <a href="/meus-dados/exportar">Baixe uma cópia</a> antes, se quiser guardar algo.
            </p>
            <form method="post" action="/meus-dados/excluir" class="form" onsubmit="return confirm('Excluir a conta e TODO o conteúdo? Não dá para desfazer.');">
                <?= \App\Services\Csrf::campo() ?>
                <div class="form--inline">
                    <label style="flex:1 1 220px;">Sua senha
                        <input type="password" name="senha" required autocomplete="current-password">
                    </label>
                    <label style="flex:1 1 220px;">Digite <strong>EXCLUIR</strong> para confirmar
                        <input type="text" name="confirmacao" required autocomplete="off" placeholder="EXCLUIR">
                    </label>
                </div>
                <button type="submit" class="btn btn--danger">Excluir minha conta definitivamente</button>
            </form>
        <?php endif; ?>
    </div>
