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
    $__isProva = str_starts_with($__path, '/provas');
    $__isConta = str_starts_with($__path, '/admin/contas');
    $__isRevisar = str_starts_with($__path, '/revisar');

    // Quem esta logado (para o rodape da sidebar).
    $__user = \App\Services\Auth::usuario() ?? ['name' => '', 'email' => '', 'role' => ''];
    $__ehAdmin = ($__user['role'] ?? '') === \App\Models\User::ROLE_ADMIN;

    // Quantos rascunhos esperam revisao: o numero fica visivel em toda tela,
    // porque e o passo que estava sendo esquecido.
    // (Depende de $__user -- precisa vir depois da linha acima.)
    $__pendentes = !empty($__user['id'])
        ? (new \App\Models\Revisao())->contar((int) $__user['id'])['total']
        : 0;

    $__partes = preg_split('/\s+/', trim((string) $__user['name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $__iniciais = mb_strtoupper(
        mb_substr($__partes[0] ?? '?', 0, 1) . (count($__partes) > 1 ? mb_substr(end($__partes), 0, 1) : '')
    );
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
                <a href="/revisar" class="navitem <?= $__isRevisar ? 'is-active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span>Revisar</span>
                    <?php if ($__pendentes > 0): ?>
                        <span class="contador-nav"><?= $__pendentes ?></span>
                    <?php endif; ?>
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
                <a href="/provas" class="navitem <?= $__isProva ? 'is-active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/></svg>
                    <span>Provas</span>
                </a>
                <?php if ($__ehAdmin): ?>
                    <a href="/admin/contas" class="navitem <?= $__isConta ? 'is-active' : '' ?>">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                        <span>Contas</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar__foot">
                <span class="avatar"><?= htmlspecialchars($__iniciais) ?></span>
                <span class="sidebar__user">
                    <strong><?= htmlspecialchars($__user['name']) ?><?php if ($__ehAdmin): ?><span class="badge-admin">admin</span><?php endif; ?></strong>
                    <small class="muted"><?= htmlspecialchars($__user['email']) ?></small>
                </span>
                <form method="post" action="/logout">
            <?= \App\Services\Csrf::campo() ?>
                    <button type="submit" class="btn-sair" title="Sair" aria-label="Sair">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                    </button>
                </form>
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
