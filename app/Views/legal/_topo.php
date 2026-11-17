<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <script>document.documentElement.className += ' js';</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> — Assistente do Professor</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="legal-body">
    <header class="legal-top">
        <a href="/" class="legal-brand"><span class="login-logo">AP</span><span>Assistente <strong>do Professor</strong></span></a>
        <nav class="legal-nav">
            <a href="/privacidade" <?= $ativa === 'privacidade' ? 'class="is-active"' : '' ?>>Política de Privacidade</a>
            <a href="/cookies" <?= $ativa === 'cookies' ? 'class="is-active"' : '' ?>>Política de Cookies</a>
            <a href="/">Voltar ao início</a>
        </nav>
    </header>
    <main class="legal-wrap">
        <article class="legal-doc">
            <h1><?= htmlspecialchars($titulo) ?></h1>
            <p class="legal-meta">Versão <?= htmlspecialchars(\App\Services\Legal::VERSAO) ?> · atualizada em <?= htmlspecialchars(\App\Services\Legal::ATUALIZADO_EM) ?></p>
