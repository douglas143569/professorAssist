<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha — Assistente do Professor</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-body">
    <main class="login-wrap">
        <div class="login-card">
            <div class="login-brand">
                <span class="login-logo">AP</span>
                <span class="login-brandtext">Assistente <strong>do Professor</strong></span>
            </div>

            <h1 class="login-title">Esqueci minha senha</h1>

            <?php if ($enviado): ?>
                <div class="login-aviso" role="status">
                    <strong>Pronto!</strong> Se existir uma conta com o e-mail <strong><?= htmlspecialchars($email) ?></strong>,
                    enviamos um link para criar uma nova senha. Ele vale por 60 minutos.
                </div>
                <p class="login-sub">Não chegou? Confira a caixa de spam e aguarde alguns minutos antes de pedir de novo.</p>
                <p class="login-rodape"><a href="/login">← Voltar para o login</a></p>
            <?php else: ?>
                <p class="login-sub">Informe o e-mail da sua conta e enviaremos um link para você criar uma nova senha.</p>

                <?php if (!empty($erro)): ?>
                    <div class="login-erro" role="alert"><span><?= htmlspecialchars($erro) ?></span></div>
                <?php endif; ?>

                <form method="post" action="/esqueci-senha" class="form login-form">
                    <?= \App\Services\Csrf::campo() ?>
                    <label>
                        E-mail
                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>"
                               placeholder="voce@escola.com.br" required autofocus autocomplete="username">
                    </label>
                    <button type="submit" class="btn btn--primary login-btn">Enviar link de recuperação</button>
                </form>

                <p class="login-rodape"><a href="/login">← Voltar para o login</a></p>
                <p class="login-nota" style="margin-top:10px;">É aluno? Peça ao seu professor para redefinir a sua senha.</p>
            <?php endif; ?>
        </div>
        <p class="login-nota">Assistente para Professores · <a href="/privacidade">Privacidade</a> · <a href="/cookies">Cookies</a></p>
    </main>
    <?php require __DIR__ . '/../layouts/cookie_banner.php'; ?>
</body>
</html>
