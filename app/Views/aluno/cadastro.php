<?php
    $escolas = [];
    foreach ($turmas as $t) {
        $escolas[$t['escola_id']] = $t['escola_nome'] ?? 'Escola';
    }
    asort($escolas);
    $sel = $dados ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar acesso — Área do Aluno</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-body">
    <main class="login-wrap">
        <div class="login-card login-card--largo">
            <div class="login-brand">
                <span class="login-logo">AA</span>
                <span class="login-brandtext">Área do <strong>Aluno</strong></span>
            </div>

            <h1 class="login-title">Criar meu acesso</h1>
            <p class="login-sub">Escolha sua escola e turma e defina uma senha.</p>

            <?php if (!empty($erro)): ?>
                <div class="login-erro" role="alert"><span><?= htmlspecialchars($erro) ?></span></div>
            <?php endif; ?>

            <?php if (empty($turmas)): ?>
                <p class="login-sub">Ainda não há turmas cadastradas. Peça ao seu professor para criar a turma primeiro.</p>
            <?php else: ?>
            <form method="post" action="/aluno/cadastro" class="form login-form">
                <?= \App\Services\Csrf::campo() ?>

                <label>Nome completo
                    <input type="text" name="nome" value="<?= htmlspecialchars($sel['nome'] ?? '') ?>"
                           placeholder="Ex: Maria Oliveira Souza" required autofocus autocomplete="name">
                    <small class="campo-dica">É com ele que você vai entrar. Use o nome completo.</small>
                </label>

                <label>Escola
                    <select id="escola" required>
                        <option value="">Selecione…</option>
                        <?php foreach ($escolas as $eid => $enome): ?>
                            <option value="<?= (int) $eid ?>"><?= htmlspecialchars($enome) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Turma
                    <select name="turma_id" id="turma" required>
                        <option value="">Escolha a escola primeiro</option>
                        <?php foreach ($turmas as $t): ?>
                            <option value="<?= (int) $t['id'] ?>" data-escola="<?= (int) $t['escola_id'] ?>"
                                <?= (int) ($sel['turma_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Senha
                    <input type="password" name="senha" placeholder="mínimo 8 caracteres"
                           required minlength="8" autocomplete="new-password">
                </label>

                <label>Repita a senha
                    <input type="password" name="senha_confirmacao" placeholder="digite a senha de novo"
                           required minlength="8" autocomplete="new-password">
                </label>

                <label class="aceite-lgpd">
                    <input type="checkbox" name="aceite" value="1" required>
                    <span>Li e concordo com a <a href="/privacidade" target="_blank" rel="noopener">Política de Privacidade</a>.</span>
                </label>
                <label class="aceite-lgpd">
                    <input type="checkbox" name="responsavel" value="1" required>
                    <span><strong>Menores de 18 anos:</strong> declaro que um dos meus pais ou responsável legal autorizou este cadastro e o uso dos meus dados pela escola/professor, ou que tenho 18 anos ou mais.</span>
                </label>

                <button type="submit" class="btn btn--primary login-btn">Criar acesso</button>
            </form>
            <?php endif; ?>

            <p class="login-rodape">Já tem acesso? <a href="/aluno/login">Entrar</a></p>
        </div>
        <p class="login-nota">Assistente para Professores · <a href="/privacidade">Privacidade</a> · <a href="/cookies">Cookies</a></p>
    </main>

    <script>
        (function () {
            var escola = document.getElementById('escola');
            var turma = document.getElementById('turma');
            if (!escola || !turma) return;

            function filtrar() {
                var eid = escola.value;
                var algum = false;
                Array.prototype.forEach.call(turma.options, function (opt) {
                    if (!opt.value) return;
                    var ok = opt.getAttribute('data-escola') === eid;
                    opt.hidden = !ok;
                    if (!ok && opt.selected) turma.value = '';
                    if (ok) algum = true;
                });
                turma.options[0].textContent = algum ? 'Selecione a turma…' : 'Escolha a escola primeiro';
            }

            escola.addEventListener('change', filtrar);
            var marcada = turma.querySelector('option[selected]');
            if (marcada) { escola.value = marcada.getAttribute('data-escola'); }
            filtrar();
            if (marcada) { turma.value = marcada.value; }
        })();
    </script>
    <?php require __DIR__ . '/../layouts/cookie_banner.php'; ?>
</body>
</html>
