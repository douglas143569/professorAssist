<?php
    $__path = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $__is = fn (string $p) => $__path === $p || ($p !== '/aluno' && str_starts_with($__path, $p));

    $__aluno = \App\Services\Auth::usuario() ?? ['name' => ''];
    $__turmas = !empty($__aluno['id'])
        ? (new \App\Models\Matricula())->turmasDoAluno((int) $__aluno['id'])
        : [];
    $__turmaLabel = $__turmas
        ? implode(' · ', array_map(fn ($t) => $t['nome'], array_slice($__turmas, 0, 2)))
        : 'Sem turma';

    $__partes = preg_split('/\s+/', trim((string) $__aluno['name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $__iniciais = mb_strtoupper(
        mb_substr($__partes[0] ?? '?', 0, 1) . (count($__partes) > 1 ? mb_substr(end($__partes), 0, 1) : '')
    );
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Área do aluno') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="aluno-body">
    <div class="aluno-shell">
        <header class="aluno-top">
            <a class="aluno-brand" href="/aluno">
                <span class="aluno-logo">AA</span>
                <span class="aluno-brandtext">Área do <strong>Aluno</strong></span>
            </a>

            <nav class="aluno-nav">
                <a href="/aluno" class="<?= $__path === '/aluno' ? 'is-active' : '' ?>">Início</a>
                <a href="/aluno/tarefas" class="<?= $__is('/aluno/tarefas') ? 'is-active' : '' ?>">Tarefas</a>
                <a href="/aluno/materiais" class="<?= $__is('/aluno/materiais') ? 'is-active' : '' ?>">Materiais</a>
                <a href="/aluno/notas" class="<?= $__is('/aluno/notas') ? 'is-active' : '' ?>">Notas</a>
                <a href="/aluno/avisos" class="<?= $__is('/aluno/avisos') ? 'is-active' : '' ?>">Avisos</a>
            </nav>

            <div class="aluno-user">
                <span class="avatar"><?= htmlspecialchars($__iniciais) ?></span>
                <span class="aluno-user__txt">
                    <strong><?= htmlspecialchars($__aluno['name']) ?></strong>
                    <small class="muted"><?= htmlspecialchars($__turmaLabel) ?></small>
                </span>
                <form method="post" action="/aluno/logout">
                    <?= \App\Services\Csrf::campo() ?>
                    <button type="submit" class="btn btn--ghost btn--sm">Sair</button>
                </form>
            </div>
        </header>

        <main class="aluno-main">
            <?php if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['flash'])): ?>
                <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>
