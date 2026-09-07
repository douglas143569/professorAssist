<?php
    /** @var array $turmas  linhas com id, nome, escola_id, escola_nome */
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
    <title>Entrar — Área do Aluno</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-body">
    <main class="login-wrap">
        <div class="login-card">
            <div class="login-brand">
                <span class="login-logo">AA</span>
                <span class="login-brandtext">Área do <strong>Aluno</strong></span>
            </div>

            <h1 class="login-title">Entrar</h1>
            <p class="login-sub">Escolha sua turma e entre com seu nome e senha.</p>

            <?php if (!empty($erro)): ?>
                <div class="login-erro" role="alert"><span><?= htmlspecialchars($erro) ?></span></div>
            <?php endif; ?>

            <?php if (empty($turmas)): ?>
                <p class="login-sub">Ainda não há turmas cadastradas. Fale com seu professor.</p>
            <?php else: ?>
            <form method="post" action="/aluno/login" class="form login-form">
                <?= \App\Services\Csrf::campo() ?>

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

                <label>Seu nome completo
                    <input type="text" name="nome" value="<?= htmlspecialchars($sel['nome'] ?? '') ?>"
                           placeholder="Ex: Maria Oliveira Souza" required autocomplete="name">
                </label>

                <label>Senha
                    <input type="password" name="senha" placeholder="••••••••" required autocomplete="current-password">
                </label>

                <button type="submit" class="btn btn--primary login-btn">Entrar</button>
            </form>
            <?php endif; ?>

            <p class="login-rodape">
                Ainda não tem cadastro? <a href="/aluno/cadastro"><strong>Criar meu acesso</strong></a><br>
                <a href="/login">Sou professor</a>
            </p>
        </div>
        <p class="login-nota">Assistente para Professores</p>
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

            // Pre-seleciona a escola da turma marcada (volta de erro).
            var marcada = turma.querySelector('option[selected]');
            if (marcada) { escola.value = marcada.getAttribute('data-escola'); }
            filtrar();
            if (marcada) { turma.value = marcada.value; }
        })();
    </script>
</body>
</html>
