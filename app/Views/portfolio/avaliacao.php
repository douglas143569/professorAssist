<?php
/** Edicao de uma avaliacao descritiva. Variaveis: $a, $c. */
use App\Models\PortfolioAvaliacao;
use App\Services\Csrf;

$sub = 'criancas';
require __DIR__ . '/_topo.php';

$dicas = [
    'socioemocional' => 'Como expressa emoções, lida com frustrações, vínculos com adultos e colegas.',
    'linguagem' => 'Comunicação oral, vocabulário, escuta, interesse por histórias.',
    'movimento' => 'Coordenação motora ampla e fina, equilíbrio, exploração do corpo.',
    'interacao' => 'Brincadeiras com os colegas, compartilhar, resolver conflitos.',
    'participacao' => 'Envolvimento nas propostas, curiosidade, concentração.',
    'autonomia' => 'Alimentação, higiene, cuidado com os pertences, escolhas.',
    'consideracoes' => 'Síntese do período e o que será estimulado a seguir.',
];
?>
<div class="pf" data-token="<?= pf_h(Csrf::token()) ?>" data-crianca="<?= (int) $c['id'] ?>">
    <p class="breadcrumb"><a href="/creche/portfolio">Portfólio</a> / <a href="/creche/portfolio/<?= (int) $c['id'] ?>?aba=avaliacoes"><?= pf_h($c['nome']) ?></a> / Avaliação</p>

    <div class="page-header">
        <div>
            <h2>Avaliação descritiva · <?= pf_h($a['periodo']) ?> de <?= (int) $a['ano'] ?></h2>
            <p class="muted">Edite livremente. Com <b>✨ Gerar com IA</b>, o texto de cada seção parte dos registros reais da criança — revise sempre antes de usar.</p>
        </div>
    </div>

    <form method="post" action="/creche/portfolio/avaliacoes/<?= (int) $a['id'] ?>" class="form pf-form">
        <?= Csrf::campo() ?>
        <?php foreach (PortfolioAvaliacao::SECOES as $campo => $rot): ?>
            <div class="card">
                <h3><?= pf_h($rot) ?></h3>
                <p class="muted"><?= pf_h($dicas[$campo]) ?></p>
                <?= pf_area_texto($campo, (string) ($a[$campo] ?? ''), ['rows' => 5, 'max' => 5000, 'ia' => 'avaliacao,corrigir', 'foco' => 'Avaliação descritiva (' . $rot . ') — ' . $a['periodo'], 'crianca' => $c['id']]) ?>
            </div>
        <?php endforeach; ?>
        <div class="actions">
            <button class="btn btn--primary" type="submit">Salvar avaliação</button>
            <a class="btn btn--ghost" href="/creche/portfolio/<?= (int) $c['id'] ?>?aba=avaliacoes">Voltar</a>
        </div>
    </form>

    <form method="post" action="/creche/portfolio/avaliacoes/<?= (int) $a['id'] ?>/excluir" onsubmit="return confirm('Excluir esta avaliação?');">
        <?= Csrf::campo() ?>
        <button class="btn btn--danger" type="submit">Excluir avaliação</button>
    </form>
</div>
<script src="/assets/js/portfolio.js" defer></script>
