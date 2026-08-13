<?php
    $__path = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $__isHome = ($__path === '/');
    // Escolas cobre toda a hierarquia abaixo dela: turmas > materias > temas.
    $__isEscola = !$__isHome && (
        str_starts_with($__path, '/escolas') ||
        str_starts_with($__path, '/turmas') ||
        str_starts_with($__path, '/disciplinas') ||
        str_starts_with($__path, '/modulos') ||
        str_starts_with($__path, '/conteudos') ||
        str_starts_with($__path, '/questoes') ||
        str_starts_with($__path, '/planos') ||
        str_starts_with($__path, '/atividades')
    );
    $__isCal = str_starts_with($__path, '/calendario') || str_starts_with($__path, '/eventos');
    $__isCreche = str_starts_with($__path, '/creche');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Assistente do Professor') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <input type="checkbox" id="navtoggle" class="navtoggle" hidden>
    <div class="app-shell">
        <label for="navtoggle" class="nav-overlay" aria-hidden="true"></label>
        <aside class="sidebar">
            <a class="sidebar__brand" href="/">
                <span class="sidebar__logo">AP</span>
                <span class="sidebar__brandtext">Assistente<br><strong>do Professor</strong></span>
            </a>

            <nav class="sidebar__nav">
                <a href="/" class="navitem <?= $__isHome ? 'is-active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/></svg>
                    <span>Início</span>
                </a>
                <a href="/escolas" class="navitem <?= $__isEscola ? 'is-active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8h20L12 3Z"/><path d="M4 8v11h16V8"/><path d="M9 19v-5h6v5"/></svg>
                    <span>Escolas</span>
                </a>
                <a href="/calendario" class="navitem <?= $__isCal ? 'is-active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg>
                    <span>Calendário</span>
                </a>
                <a href="/creche" class="navitem <?= $__isCreche ? 'is-active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a3 3 0 0 1 3 3c0 1.5-1 2-1 3h-4c0-1-1-1.5-1-3a3 3 0 0 1 3-3Z"/><path d="M5 21v-3a7 7 0 0 1 14 0v3"/><path d="M9 13c1 1 5 1 6 0"/></svg>
                    <span>Creche</span>
                </a>
                <span class="navitem navitem--disabled">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/></svg>
                    <span>Provas</span>
                    <span class="soon">em breve</span>
                </span>
            </nav>

            <div class="sidebar__foot">
                <span class="avatar">PD</span>
                <span class="sidebar__user">
                    <strong>Professor Demo</strong>
                    <small class="muted">professor@demo.local</small>
                </span>
            </div>
        </aside>

        <div class="main">
            <header class="topbar2">
                <label for="navtoggle" class="menu-toggle" aria-label="Abrir menu">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </label>
                <h1 class="topbar2__title"><?= htmlspecialchars($title ?? 'Assistente do Professor') ?></h1>
            </header>

            <main class="content">
                <?php if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['flash'])): ?>
                    <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>
