<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Assistente do Professor</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-body login-body--split">
    <main class="login-screen">
        <section class="login-showcase" aria-label="Assistente do Professor">
            <header class="login-showcase__header">
                <div class="login-brand"><span class="login-logo">AP</span><span class="login-brandtext">Assistente<strong>do Professor</strong></span></div>
                <span class="login-pill">✦ Planejamento com inteligência artificial</span>
            </header>
            <div class="login-showcase__copy">
                <p class="login-eyebrow">MAIS TEMPO PARA ENSINAR</p>
                <h1>Planeje aulas em <span>minutos</span>, não em horas.</h1>
                <p class="login-showcase__lead">Crie atividades, provas e planos de aula com praticidade, e organize sua rotina pedagógica em um só lugar.</p>
            </div>
            <div class="login-benefits">
                <div><span class="login-benefit-icon">▤</span><span>Conteúdos alinhados<br>à BNCC</span></div>
                <div><span class="login-benefit-icon">ϟ</span><span>Menos tempo<br>no planejamento</span></div>
                <div><span class="login-benefit-icon">◎</span><span>Organização para<br>suas turmas</span></div>
            </div>
            <div class="login-preview" aria-hidden="true">
                <div class="login-preview__top"><span>AP&nbsp; Assistente do Professor</span><span>Seu espaço de trabalho</span></div>
                <div class="login-preview__body"><aside>⌂ &nbsp; Início<br><br>✦ &nbsp; Criar com IA<br><br>▣ &nbsp; Minhas turmas<br><br>▤ &nbsp; Planos de aula<br><br>☷ &nbsp; Atividades</aside>
                    <div class="login-preview__content"><small>SEU PAINEL</small><h2>Olá, Professor! 👋</h2><p>O que você deseja criar hoje?</p><div class="login-preview__prompt">Descreva uma aula, atividade ou avaliação...</div><div class="login-preview__tiles"><span>✦ Criar atividade</span><span>▣ Criar prova</span><span>▤ Plano de aula</span></div></div>
                </div>
            </div>
            <p class="login-showcase__foot">Educação hoje. Um futuro melhor amanhã.</p>
        </section>
        <section class="login-panel">
        <div class="login-card">
            <div class="login-brand">
                <span class="login-logo">AP</span>
                <span class="login-brandtext">
                    Assistente
                    <strong>do Professor</strong>
                </span>
            </div>

            <h2 class="login-title">Bem-vindo de volta!</h2>
            <p class="login-sub">Entre para continuar sua jornada educacional.</p>
            <?php if (!empty($_SESSION['flash'])): ?>
                <div class="login-aviso" role="status"><?= htmlspecialchars($_SESSION['flash']) ?></div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

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
                <a href="/esqueci-senha" class="login-esqueci">Esqueci minha senha</a>

                <button type="submit" class="btn btn--primary login-btn">➜ &nbsp; Entrar e continuar</button>
            </form>

            <p class="login-rodape">
                <span class="login-new-label">Novo por aqui?</span>
                <span>Crie sua conta e comece a organizar sua rotina.</span>
                <a href="/cadastro" class="login-create-link"><strong>Criar conta gratuita</strong> <span aria-hidden="true">→</span></a>
            </p>

            <div class="login-aluno">
                <span>É aluno?</span>
                <a href="/aluno/login" class="login-aluno__entrar">Entrar na área do aluno</a>
                <a href="/aluno/cadastro" class="login-aluno__entrar">Criar acesso de aluno</a>
            </div>
        </div>

        <p class="login-panel__note">Um espaço feito para quem transforma o futuro.</p>
        <?php $__contato = \App\Services\Legal::contatoEmail(); ?>
        <?php if ($__contato): ?>
            <p class="login-panel__note login-panel__contato">Dúvidas ou suporte? Fale com a gente: <a href="mailto:<?= htmlspecialchars($__contato) ?>"><?= htmlspecialchars($__contato) ?></a></p>
        <?php endif; ?>
        </section>
    </main>
    <?php require __DIR__ . '/../layouts/cookie_banner.php'; ?>
</body>
</html>
