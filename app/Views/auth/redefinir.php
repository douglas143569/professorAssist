<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Criar nova senha — Assistente do Professor</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-body">
    <main class="login-wrap">
        <div class="login-card">
            <div class="login-brand">
                <span class="login-logo">AP</span>
                <span class="login-brandtext">Assistente <strong>do Professor</strong></span>
            </div>

            <?php if (!$valido): ?>
                <h1 class="login-title">Link inválido ou expirado</h1>
                <p class="login-sub">Este link de recuperação já foi usado ou passou do prazo de 60 minutos. Peça um novo.</p>
                <a href="/esqueci-senha" class="btn btn--primary login-btn" style="text-align:center;">Pedir novo link</a>
                <p class="login-rodape"><a href="/login">← Voltar para o login</a></p>
            <?php else: ?>
                <h1 class="login-title">Criar nova senha</h1>
                <p class="login-sub">Escolha uma senha nova para a sua conta.</p>

                <?php if (!empty($erro)): ?>
                    <div class="login-erro" role="alert"><span><?= htmlspecialchars($erro) ?></span></div>
                <?php endif; ?>

                <form method="post" action="/redefinir-senha" class="form login-form">
                    <?= \App\Services\Csrf::campo() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <label>
                        Nova senha
                        <input type="password" name="senha" placeholder="mínimo 8 caracteres"
                               required minlength="8" autofocus autocomplete="new-password">
                    </label>
                    <label>
                        Repita a nova senha
                        <input type="password" name="senha_confirmacao" placeholder="digite a senha de novo"
                               required minlength="8" autocomplete="new-password">
                    </label>
                    <button type="submit" class="btn btn--primary login-btn">Salvar nova senha</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
    <?php require __DIR__ . '/../layouts/cookie_banner.php'; ?>
</body>
</html>
