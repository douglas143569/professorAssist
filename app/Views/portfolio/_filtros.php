<?php
/** Filtros (mes, projeto, categoria[, tipo]). Variaveis: $filtros, $meses, $opcoesProjeto, $aba, $comTipo (opcional). */
use App\Models\PortfolioRegistro;
?>
<form method="get" class="pf-filters pf-filters--wrap" aria-label="Filtros">
    <input type="hidden" name="aba" value="<?= pf_h($aba) ?>">
    <select name="mes" aria-label="Mês" onchange="this.form.submit()">
        <option value="">Todos os meses</option>
        <?php foreach ($meses as $m): ?><option value="<?= pf_h($m) ?>" <?= $filtros['mes'] === $m ? 'selected' : '' ?>><?= pf_h(pf_mes($m)) ?></option><?php endforeach; ?>
    </select>
    <select name="projeto" aria-label="Projeto" onchange="this.form.submit()">
        <option value="">Todos os projetos</option>
        <?php foreach ($opcoesProjeto as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) $filtros['projeto'] === (int) $p['id'] ? 'selected' : '' ?>><?= pf_h($p['titulo']) ?></option><?php endforeach; ?>
    </select>
    <select name="categoria" aria-label="Categoria" onchange="this.form.submit()">
        <option value="">Todas as categorias</option>
        <?php foreach (PortfolioRegistro::CATEGORIAS as $cat): ?><option <?= $filtros['categoria'] === $cat ? 'selected' : '' ?>><?= pf_h($cat) ?></option><?php endforeach; ?>
    </select>
    <?php if (!empty($comTipo)): ?>
        <select name="tipo" aria-label="Tipo" onchange="this.form.submit()">
            <option value="">Fotos, vídeos e produções</option>
            <?php foreach (['foto' => 'Fotos', 'video' => 'Vídeos', 'producao' => 'Produções da criança', 'arquivo' => 'Arquivos'] as $k => $rot): ?>
                <option value="<?= $k ?>" <?= $filtros['tipo'] === $k ? 'selected' : '' ?>><?= $rot ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <?php if ($filtros['mes'] || $filtros['projeto'] || $filtros['categoria'] || ($filtros['tipo'] ?? '')): ?>
        <a class="btn btn--ghost btn--sm" href="?aba=<?= pf_h($aba) ?>">Limpar filtros</a>
    <?php endif; ?>
</form>
