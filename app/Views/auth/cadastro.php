<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta — Assistente do Professor</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-body">
    <main class="login-wrap">
        <div class="login-card login-card--largo">
            <div class="login-brand">
                <span class="login-logo">AP</span>
                <span class="login-brandtext">
                    Assistente
                    <strong>do Professor</strong>
                </span>
            </div>

            <h1 class="login-title">Criar conta</h1>
            <p class="login-sub">Preencha seus dados para começar a usar o sistema.</p>

            <?php if (!empty($erro)): ?>
                <div class="login-erro" role="alert">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/></svg>
                    <span><?= htmlspecialchars($erro) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="/cadastro" class="form login-form">
                <?= \App\Services\Csrf::campo() ?>
                <label>
                    Nome
                    <input type="text" name="name" value="<?= htmlspecialchars($dados['name'] ?? '') ?>"
                           placeholder="Como você quer ser chamado(a)" required autofocus autocomplete="name">
                </label>

                <label>
                    E-mail
                    <input type="email" name="email" value="<?= htmlspecialchars($dados['email'] ?? '') ?>"
                           placeholder="voce@escola.com.br" required autocomplete="email">
                    <small class="campo-dica">É com ele que você vai entrar no sistema.</small>
                </label>

                <label>
                    Celular
                    <input type="tel" name="celular" value="<?= htmlspecialchars($dados['celular'] ?? '') ?>"
                           placeholder="(11) 98765-4321" required autocomplete="tel" inputmode="tel">
                    <small class="campo-dica">Com DDD. Usado só para contato.</small>
                </label>

                <label>
                    Senha
                    <input type="password" name="senha" placeholder="mínimo 8 caracteres"
                           required minlength="8" autocomplete="new-password">
                </label>

                <label>
                    Repita a senha
                    <input type="password" name="senha_confirmacao" placeholder="digite a senha de novo"
                           required minlength="8" autocomplete="new-password">
                </label>

                <button type="submit" class="btn btn--primary login-btn">Criar conta</button>
            </form>

            <p class="login-rodape">
                Já tem conta? <a href="/login">Entrar</a>
            </p>
        </div>

        <p class="login-nota">Assistente para Professores</p>
    </main>
</body>
</html>
