<?php
/** @var array $plans Planos ativos (id, name, price, duration_days) */

$h = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$preco = static fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$porMes = static fn (array $p) => (float) $p['price'] / max(1, (int) $p['duration_days'] / 30);

$cta = 'Cadastre-se e tenha 3 dias de teste grátis';

// Icone de livro usado na marca (SVG inline, sem dependencia externa).
$logo = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 5.5C4.5 4 8 4 12 6c4-2 7.5-2 10-.5V19c-2.5-1.500-6-1.500-10 .5-4-2-7.500-2-10-.5z"/><path d="M12 6v13.500"/></svg>';
$seta = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistente do Professor — Crie semanas de planejamento em minutos</title>
    <meta name="description" content="IA que cria atividades, provas, planos de aula e materiais alinhados à BNCC. Teste grátis por 3 dias, sem cartão.">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/landing.css">
</head>
<body class="lp">

<!-- ============ HERO ============ -->
<div class="lp-top">
    <header class="lp-nav">
        <div class="lp-wrap lp-nav__in">
            <a href="/" class="lp-brand">
                <span class="lp-logo"><?= $logo ?></span>
                <span>Assistente<strong>do Professor</strong></span>
            </a>
            <nav class="lp-nav__links" aria-label="Seções">
                <a href="#inicio" class="is-on">Início</a>
                <a href="#funcionalidades">Funcionalidades</a>
                <a href="#antes-depois">Antes e depois</a>
                <a href="#planos">Planos</a>
            </nav>
            <div class="lp-nav__acoes">
                <a href="/login" class="lp-btn lp-btn--outline lp-btn--sm">Entrar</a>
                <a href="/cadastro" class="lp-btn lp-btn--sm">Testar grátis</a>
            </div>
        </div>
    </header>

    <section class="lp-hero" id="inicio">
        <div class="lp-wrap lp-hero__in">
            <div class="lp-hero__txt">
                <div class="lp-tags">
                    <span class="lp-tag lp-tag--on">● IA Educacional</span>
                    <span class="lp-tag">Mais tempo para o que realmente importa: ensinar.</span>
                </div>

                <h1>Crie semanas de planejamento <em>em minutos.</em></h1>

                <p class="lp-hero__lead">A plataforma de Inteligência Artificial que cria atividades, provas, planos de aula e materiais alinhados à BNCC.</p>

                <ul class="lp-proof">
                    <li><span class="lp-proof__ico">⚡</span><div><strong>Rascunho em segundos</strong>Você só revisa e aprova</div></li>
                    <li><span class="lp-proof__ico">🎯</span><div><strong>Alinhado à BNCC</strong>Habilidades nas questões</div></li>
                    <li><span class="lp-proof__ico">🛡️</span><div><strong>Você no controle</strong>Nada sai sem sua aprovação</div></li>
                </ul>

                <div class="lp-hero__cta">
                    <a href="/cadastro" class="lp-btn lp-btn--lg"><?= $h($cta) ?> <?= $seta ?></a>
                    <a href="#funcionalidades" class="lp-btn lp-btn--outline lp-btn--lg"><span class="lp-play">▶</span> Ver recursos</a>
                </div>
                <p class="lp-hero__nota">Sem cartão de crédito &nbsp;•&nbsp; Teste grátis por 3 dias &nbsp;•&nbsp; Cancele quando quiser</p>
            </div>

            <!-- Mockup do painel (ilustrativo) -->
            <div class="lp-hero__art" aria-hidden="true">
                <div class="lp-float lp-float--a">Da ideia<br>ao plano de aula<br>em segundos. ✨</div>
                <div class="lp-float lp-float--b">Educação<br>de qualidade<br>para um futuro<br>melhor. 💚</div>

                <div class="lp-dash">
                    <aside class="lp-dash__side">
                        <div class="lp-dash__brand"><span><?= $logo ?></span>Assistente<br>do Professor</div>
                        <ul>
                            <li class="on">Início</li><li>Chat com IA</li><li>Atividades</li><li>Planos de aula</li>
                            <li>Provas</li><li>Turmas</li><li>Calendário</li><li>Relatórios</li>
                        </ul>
                    </aside>
                    <div class="lp-dash__main">
                        <h3>Olá, Professora! 👋</h3>
                        <p class="lp-dash__sub">Hoje é um ótimo dia para inspirar novas histórias.</p>

                        <div class="lp-dash__stats">
                            <div><b>12</b><span>Turmas ativas</span></div>
                            <div><b>48</b><span>Atividades criadas</span></div>
                            <div><b>6</b><span>Provas prontas</span></div>
                            <div><b>92%</b><span>Aprovadas</span></div>
                        </div>

                        <div class="lp-dash__cols">
                            <div class="lp-chat">
                                <p class="lp-chat__h">Chat com o Assistente</p>
                                <p class="lp-chat__u">Crie 5 questões de múltipla escolha sobre a Revolução Industrial para o 8º ano, alinhadas à BNCC.</p>
                                <div class="lp-chat__a">
                                    <p>Claro! Aqui estão 5 questões (EF08HI03):</p>
                                    <p><b>1.</b> Qual foi uma das principais causas da Revolução Industrial?</p>
                                    <p>A) A invenção da escrita<br>B) O avanço da agricultura familiar<br>C) O desenvolvimento das máquinas a vapor</p>
                                </div>
                                <div class="lp-chat__chips"><span>Criar atividade</span><span>Plano de aula</span><span>Gerar prova</span></div>
                            </div>
                            <div class="lp-dash__right">
                                <div class="lp-cal">
                                    <p>Meu calendário</p>
                                    <div class="lp-cal__grid">
                                        <?php for ($d = 1; $d <= 28; $d++): ?>
                                            <i class="<?= $d === 14 ? 'hoje' : (in_array($d, [5, 19, 22], true) ? 'ev' : '') ?>"><?= $d ?></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="lp-turmas">
                                    <p>Minhas turmas</p>
                                    <div><i style="background:#7c5cff"></i>8º Ano A</div>
                                    <div><i style="background:#2ee6a6"></i>9º Ano B</div>
                                    <div><i style="background:#ffa94d"></i>1º Ano EM</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp-toast">
                    <span class="lp-toast__ok">✓</span>
                    <b>Plano de aula gerado!</b>
                    <small>História • 8º ano<br>Revolução Industrial</small>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- ============ FAIXA DE FUNCIONALIDADES ============ -->
<section class="lp-strip" id="funcionalidades">
    <div class="lp-wrap lp-strip__in">
        <article class="lp-feat"><span class="lp-feat__ico" style="--c:#7c5cff;--b:#efeaff">📄</span><div><h3>Criação de atividades</h3><p>Exercícios alinhados à BNCC em segundos.</p></div></article>
        <article class="lp-feat"><span class="lp-feat__ico" style="--c:#0f9d6b;--b:#e0f7ee">📖</span><div><h3>Planos de aula</h3><p>Objetivos, metodologias e recursos prontos.</p></div></article>
        <article class="lp-feat"><span class="lp-feat__ico" style="--c:#e5484d;--b:#ffe8e9">✅</span><div><h3>Provas e gabaritos</h3><p>Questões, versões embaralhadas e cartão-resposta.</p></div></article>
        <article class="lp-feat"><span class="lp-feat__ico" style="--c:#2f7de1;--b:#e5f0ff">👥</span><div><h3>Gestão de turmas</h3><p>Escolas, turmas, tarefas, avisos e notas.</p></div></article>
        <article class="lp-feat"><span class="lp-feat__ico" style="--c:#f08c00;--b:#fff3dc">📅</span><div><h3>Calendário e Creche</h3><p>Eventos, cronograma e folhas para a Educação Infantil.</p></div></article>
    </div>
</section>

<!-- ============ ANTES E DEPOIS ============ -->
<section class="lp-sec" id="antes-depois">
    <div class="lp-wrap lp-ad">
        <div class="lp-ad__head">
            <h2>Antes e depois</h2>
            <p>Veja como o Assistente do Professor transforma sua rotina.</p>
        </div>

        <div class="lp-ad__box lp-ad__box--antes">
            <h3><span>😞</span> Antes</h3>
            <ul>
                <li>Planejar aulas manualmente</li>
                <li>Fazer provas no Word</li>
                <li>Organizar tudo em vários arquivos</li>
                <li>Pouco tempo para os alunos</li>
            </ul>
        </div>
        <div class="lp-ad__box lp-ad__box--depois">
            <h3><span>😊</span> Depois</h3>
            <ul>
                <li>Um comando gera tudo</li>
                <li>Provas prontas em segundos</li>
                <li>Turmas organizadas em um só lugar</li>
                <li>Mais tempo para ensinar</li>
            </ul>
        </div>

        <div class="lp-ad__msg">
            <h2>Educação mais humana com o poder da IA.</h2>
            <p>Professores bem apoiados formam alunos mais preparados para o futuro.</p>
            <p class="lp-ad__how"><strong>Como funciona:</strong> você descreve o tema → a IA rascunha → você revisa e aprova → imprime ou publica para a turma.</p>
        </div>
    </div>
</section>

<!-- ============ PLANOS (valores vindos do banco) ============ -->
<section class="lp-sec lp-sec--dark" id="planos">
    <div class="lp-wrap">
        <h2 class="lp-center">Planos</h2>
        <p class="lp-center lp-muted">Comece grátis por 3 dias. Escolha o plano quando o teste terminar.</p>
        <div class="lp-plans">
            <?php
            $maisBarato = $plans ? min(array_map($porMes, $plans)) : 0;
            foreach ($plans as $p):
                $destaque = count($plans) > 1 && $porMes($p) === $maisBarato;
            ?>
                <article class="lp-plan<?= $destaque ? ' lp-plan--top' : '' ?>">
                    <?php if ($destaque): ?><span class="lp-plan__tag">Melhor custo-benefício</span><?php endif; ?>
                    <h3><?= $h($p['name']) ?></h3>
                    <p class="lp-plan__preco"><?= $h($preco($p['price'])) ?></p>
                    <p class="lp-plan__per"><?= (int) $p['duration_days'] ?> dias · <?= $h($preco($porMes($p))) ?>/mês</p>
                    <ul>
                        <li>Todas as funcionalidades</li>
                        <li>PIX, cartão ou boleto</li>
                    </ul>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ BARRA FINAL ============ -->
<footer class="lp-bar">
    <div class="lp-wrap lp-bar__in">
        <div class="lp-bar__brand">
            <span class="lp-logo"><?= $logo ?></span>
            <div><strong>Assistente do Professor</strong><small>Tecnologia a serviço da educação.</small></div>
        </div>
        <ul class="lp-bar__stats">
            <li><span>🎓</span><div><b>BNCC</b>Fundamental e Médio</div></li>
            <li><span>🧸</span><div><b>Creche</b>Educação Infantil</div></li>
            <li><span>🖨️</span><div><b>PDF</b>Provas e atividades</div></li>
            <li><span>🔒</span><div><b>Seguro</b>PIX, cartão e boleto</div></li>
        </ul>
        <div class="lp-bar__cta">
            <a href="/cadastro" class="lp-btn lp-btn--lg">Comece seu teste grátis <?= $seta ?></a>
            <small>Sem cartão de crédito. Cancele quando quiser.</small>
        </div>
    </div>
    <div class="lp-wrap lp-bar__legal">
        <span>© <?= date('Y') ?> Assistente do Professor</span>
        <span><a href="/login">Entrar</a> · <a href="/cadastro">Criar conta</a> · <a href="/aluno/login">Sou aluno</a></span>
    </div>
</footer>

</body>
</html>
