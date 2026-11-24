<?php
/** Campos de um registro (criar/editar). Variaveis: $r (array|null), $c, $idade, $opcoesProjeto. */
use App\Models\PortfolioCrianca;
use App\Models\PortfolioRegistro;

$g = static fn (string $k, $p = '') => $r[$k] ?? $p;
?>
<div class="pf-formgrid">
    <label>Data
        <input type="date" name="data_registro" value="<?= pf_h($g('data_registro', date('Y-m-d'))) ?>" max="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
    </label>
    <label class="pf-span2">Título
        <input type="text" name="titulo" value="<?= pf_h($g('titulo')) ?>" maxlength="160" required placeholder="Ex.: Primeira vez pintando com as mãos">
    </label>
    <label>Categoria
        <select name="categoria">
            <?php foreach (PortfolioRegistro::CATEGORIAS as $cat): ?><option <?= $g('categoria', 'Rotina') === $cat ? 'selected' : '' ?>><?= pf_h($cat) ?></option><?php endforeach; ?>
        </select>
    </label>
    <label>Área de desenvolvimento
        <select name="area">
            <option value="">— nenhuma em especial —</option>
            <?php foreach (PortfolioCrianca::AREAS as $k => [$rot, $ico]): ?><option value="<?= $k ?>" <?= $g('area') === $k ? 'selected' : '' ?>><?= $ico ?> <?= pf_h($rot) ?></option><?php endforeach; ?>
        </select>
    </label>
    <label>Projeto pedagógico
        <select name="projeto_id">
            <option value="">— nenhum —</option>
            <?php foreach ($opcoesProjeto as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) $g('projeto_id', 0) === (int) $p['id'] ? 'selected' : '' ?>><?= pf_h($p['titulo']) ?></option><?php endforeach; ?>
        </select>
        <?php if (!$opcoesProjeto): ?><small class="muted"><a href="/creche/portfolio/projetos">Criar um projeto</a></small><?php endif; ?>
    </label>

    <label class="pf-span3">Observação pedagógica
        <?= pf_area_texto('descricao', (string) $g('descricao'), ['id' => 'f-descricao', 'rows' => 6, 'max' => 6000, 'req' => true, 'ia' => 'redigir,corrigir', 'foco' => 'Registro pedagógico de uma criança (' . $g('categoria', 'Rotina') . ')', 'crianca' => $c['id'], 'ph' => 'Escreva observações curtas do que aconteceu (ex.: pintou com as mãos, riu muito, chamou o colega) e use ✨ para virar texto pedagógico.']) ?>
    </label>
    <label>Objetivos trabalhados
        <?= pf_area_texto('objetivos', (string) $g('objetivos'), ['rows' => 3, 'max' => 2000, 'ph' => 'Ex.: explorar texturas e cores; ampliar a expressão corporal']) ?>
    </label>
    <label class="pf-span2">Habilidades observadas
        <?= pf_area_texto('habilidades', (string) $g('habilidades'), ['id' => 'f-habilidades', 'rows' => 3, 'max' => 2000, 'ia' => 'habilidades', 'fonte' => '#f-descricao', 'foco' => 'Registro pedagógico', 'crianca' => $c['id'], 'ph' => 'Uma por linha. Use ✨ para sugerir a partir da observação.']) ?>
    </label>

    <label class="pf-span2">Fotos, vídeos e arquivos <small class="muted">(até <?= (int) ($_ENV['UPLOAD_MAX_MB'] ?? 10) ?> MB por foto, <?= (int) ($_ENV['UPLOAD_VIDEO_MAX_MB'] ?? 40) ?> MB por vídeo)</small>
        <input type="file" name="arquivos[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,.pdf,.doc,.docx,.odt,.ppt,.pptx" data-previa="#pf-previa">
        <span class="pf-check"><input type="checkbox" name="producao" value="1" id="pf-producao"> <label for="pf-producao" style="display:inline;font-weight:500">As fotos são <b>produções da criança</b> (desenhos, pinturas, trabalhos)</label></span>
    </label>
    <div id="pf-previa" class="pf-previa pf-span3" aria-live="polite"></div>
</div>
