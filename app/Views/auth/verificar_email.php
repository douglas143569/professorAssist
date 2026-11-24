<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar e-mail — Assistente do Professor</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-body">
    <main class="login-wrap">
        <div class="login-card">
            <div class="login-brand">
                <span class="login-logo">AP</span>
                <span class="login-brandtext">Assistente<strong>do Professor</strong></span>
            </div>

            <h1 class="login-title">Confirme seu e-mail</h1>
            <p class="login-sub">Digite o código de 6 dígitos para concluir seu cadastro.</p>

            <?php if (!empty($mensagem)): ?>
                <p role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if ($emailConfigurado): ?>
                <p>Enviamos o código para <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>. Verifique também a pasta de spam.</p>
            <?php else: ?>
                <p class="login-erro" role="status">
                    O código não foi enviado por e-mail porque o SMTP não está configurado. Em modo local, ele foi salvo em
                    <code><?= htmlspecialchars($arquivoLogEmail, ENT_QUOTES, 'UTF-8') ?></code>. Configure as variáveis <code>MAIL_*</code> no arquivo <code>.env</code> para enviar e-mails.
                </p>
            <?php endif; ?>

            <?php if (!empty($erro)): ?>
                <div class="login-erro" role="alert">
                    <span><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="/verificar-email" class="form login-form">
                <?= \App\Services\Csrf::campo() ?>
                <label>
                    Código de verificação
                    <input type="text" name="codigo" inputmode="numeric" autocomplete="one-time-code"
                           pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus>
                </label>
                <button type="submit" class="btn btn--primary login-btn">Confirmar e continuar</button>
            </form>

            <form method="post" action="/verificar-email/reenviar" class="form login-form">
                <?= \App\Services\Csrf::campo() ?>
                <button type="submit" class="btn btn--primary login-btn">Reenviar código</button>
            </form>

            <p class="login-rodape">O código expira em 15 minutos. Você poderá pedir outro após 60 segundos.</p>
            <p class="login-rodape"><a href="/cadastro">Voltar ao cadastro</a></p>
        </div>

        <p class="login-nota">Assistente para Professores · <a href="/privacidade">Privacidade</a> · <a href="/cookies">Cookies</a></p>
    </main>
    <?php require __DIR__ . '/../layouts/cookie_banner.php'; ?>
</body>
</html>
