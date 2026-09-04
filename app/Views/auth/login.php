<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Assistente do Professor</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-body">
    <main class="login-wrap">
        <div class="login-card">
            <div class="login-brand">
                <span class="login-logo">AP</span>
                <span class="login-brandtext">
                    Assistente
                    <strong>do Professor</strong>
                </span>
            </div>

            <h1 class="login-title">Entrar</h1>
            <p class="login-sub">Use suas credenciais de professor para acessar.</p>

            <?php if (!empty($erro)): ?>
                <div class="login-erro" role="alert">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/></svg>
                    <span><?= htmlspecialchars($erro) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="/login" class="form login-form">
            <?= \App\Services\Csrf::campo() ?>
                <label>
                    E-mail
                    <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>"
                           placeholder="voce@escola.com.br" required autofocus autocomplete="username">
                </label>

                <label>
                    Senha
                    <input type="password" name="senha" placeholder="••••••••"
                           required autocomplete="current-password">
                </label>

                <button type="submit" class="btn btn--primary login-btn">Entrar</button>
            </form>

            <p class="login-rodape">
                Esqueceu a senha? Fale com o administrador do sistema.
            </p>
        </div>

        <p class="login-nota">Assistente para Professores</p>
    </main>
</body>
</html>
