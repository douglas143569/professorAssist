<?php
/**
 * Relatorio / portfolio imprimivel (documento independente, sem o layout do sistema).
 * Variaveis: $c, $idade, $areas, $registros (cronologico), $midiasPorRegistro, $avaliacoes, $galeria.
 */
use App\Models\PortfolioAvaliacao;
use App\Models\PortfolioCrianca;

require_once __DIR__ . '/_helpers.php';
$paragrafos = static fn (?string $t) => $t ? '<p>' . implode('</p><p>', array_map('pf_h', preg_split('/\n\s*\n|\R/', trim($t)))) . '</p>' : '';
$ano = date('Y');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portfólio · <?= pf_h($c['nome']) ?></title>
    <link rel="stylesheet" href="/assets/css/portfolio.css">
</head>
<body class="rp-body">
<div class="rp-toolbar">
    <a href="/creche/portfolio/<?= (int) $c['id'] ?>">← Voltar ao portfólio</a>
    <?php if (!$c['autoriza_imagem']): ?><span class="rp-warn">⚠️ Autorização de imagem não marcada: confira antes de entregar.</span><?php endif; ?>
    <button type="button" onclick="window.print()">🖨 Imprimir / Salvar como PDF</button>
    <small>Dica: no diálogo de impressão, ative “Gráficos de segundo plano” para manter as cores.</small>
</div>

<!-- ============ CAPA ============ -->
<section class="rp-page rp-capa">
    <div class="rp-capa__borda">
        <p class="rp-eyebrow">Portfólio Pedagógico · Educação Infantil</p>
        <div class="rp-capa__foto">
            <?php if ($c['foto_path']): ?><img src="/creche/portfolio/<?= (int) $c['id'] ?>/foto" alt="Foto de <?= pf_h($c['nome']) ?>"><?php else: ?><span aria-hidden="true">🧸</span><?php endif; ?>
        </div>
        <h1><?= pf_h($c['nome']) ?></h1>
        <p class="rp-capa__meta"><?= pf_h($c['turma']) ?> · <?= pf_h($c['periodo']) ?><br><?= pf_h($idade) ?></p>
        <p class="rp-capa__prof">Professor(a) responsável: <b><?= pf_h($c['professora']) ?></b></p>
        <p class="rp-capa__ano">Ano letivo <?= $ano ?></p>
    </div>
</section>

<!-- ============ APRESENTACAO ============ -->
<section class="rp-page">
    <h2>Apresentação</h2>
    <?php if ($c['apresentacao']): ?><div class="rp-texto"><?= $paragrafos($c['apresentacao']) ?></div><?php endif; ?>
    <?php if ($c['adaptacao']): ?><h3>Adaptação</h3><div class="rp-texto"><?= $paragrafos($c['adaptacao']) ?></div><?php endif; ?>
    <?php if ($c['caracteristicas']): ?><h3>Características observadas</h3><div class="rp-texto"><?= $paragrafos($c['caracteristicas']) ?></div><?php endif; ?>
    <?php if (!$c['apresentacao'] && !$c['adaptacao'] && !$c['caracteristicas']): ?><p class="rp-vazio">Apresentação ainda não escrita.</p><?php endif; ?>
</section>

<!-- ============ DESENVOLVIMENTO ============ -->
<?php $comArea = array_filter(PortfolioCrianca::AREAS, fn ($k) => trim((string) ($areas[$k] ?? '')) !== '', ARRAY_FILTER_USE_KEY); ?>
<?php if ($comArea): ?>
<section class="rp-page">
    <h2>Desenvolvimento</h2>
    <?php foreach ($comArea as $k => [$rot, $ico, $rgb]): ?>
        <div class="rp-area" style="--cor:<?= $rgb ?>">
            <h3><span aria-hidden="true"><?= $ico ?></span> <?= pf_h($rot) ?></h3>
            <div class="rp-texto"><?= $paragrafos($areas[$k]) ?></div>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<!-- ============ LINHA DO TEMPO ============ -->
<?php if ($registros): ?>
<section class="rp-page">
    <h2>Linha do tempo</h2>
    <ol class="rp-tl">
        <?php foreach ($registros as $r): ?>
            <li><time><?= pf_data($r['data_registro']) ?></time><span><b><?= pf_h($r['titulo']) ?></b> <em><?= pf_h($r['categoria']) ?></em></span></li>
        <?php endforeach; ?>
    </ol>
</section>

<!-- ============ REGISTROS ============ -->
<section class="rp-page rp-registros">
    <h2>Registros pedagógicos</h2>
    <?php foreach ($registros as $r):
        $fotos = array_filter($midiasPorRegistro[$r['id']] ?? [], fn ($m) => in_array($m['tipo'], ['foto', 'producao'], true));
    ?>
        <article class="rp-reg">
            <header><time><?= pf_data($r['data_registro']) ?></time><h3><?= pf_h($r['titulo']) ?></h3><em><?= pf_h($r['categoria']) ?><?= $r['projeto_titulo'] ? ' · ' . pf_h($r['projeto_titulo']) : '' ?></em></header>
            <?php if ($fotos): ?>
                <div class="rp-fotos rp-fotos--<?= min(3, count($fotos)) ?>"><?php foreach (array_slice($fotos, 0, 3) as $m): ?><img src="<?= pf_midia_url((int) $m['id']) ?>" alt="<?= pf_h($m['registro_titulo']) ?>"><?php endforeach; ?></div>
            <?php endif; ?>
            <div class="rp-texto"><?= $paragrafos($r['descricao']) ?></div>
            <?php if ($r['habilidades']): ?><p class="rp-hab"><b>Habilidades observadas:</b> <?= pf_h(implode(' · ', array_map(fn ($h) => ltrim(trim($h), '-•* '), array_filter(preg_split('/\R/', $r['habilidades']))))) ?></p><?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<!-- ============ AVALIACOES ============ -->
<?php foreach ($avaliacoes as $a): ?>
<section class="rp-page">
    <h2>Avaliação descritiva · <?= pf_h($a['periodo']) ?> de <?= (int) $a['ano'] ?></h2>
    <?php foreach (PortfolioAvaliacao::SECOES as $campo => $rot): if (trim((string) $a[$campo]) === '') { continue; } ?>
        <h3><?= pf_h($rot) ?></h3><div class="rp-texto"><?= $paragrafos($a[$campo]) ?></div>
    <?php endforeach; ?>
</section>
<?php endforeach; ?>

<!-- ============ GALERIA ============ -->
<?php if ($galeria): ?>
<section class="rp-page">
    <h2>Galeria de momentos</h2>
    <div class="rp-galeria">
        <?php foreach (array_slice($galeria, 0, 24) as $m): ?>
            <figure><img src="<?= pf_midia_url((int) $m['id']) ?>" alt="" loading="lazy"><figcaption><?= pf_h($m['registro_titulo'] ?: 'Desenvolvimento') ?></figcaption></figure>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============ MENSAGEM FINAL ============ -->
<section class="rp-page rp-final">
    <h2>Mensagem final</h2>
    <div class="rp-texto rp-texto--grande"><?= $c['mensagem_final'] ? $paragrafos($c['mensagem_final']) : '<p class="rp-vazio">Mensagem final ainda não escrita.</p>' ?></div>
    <p class="rp-assina"><span></span>Prof.ª <?= pf_h($c['professora']) ?></p>
</section>
</body>
</html>
