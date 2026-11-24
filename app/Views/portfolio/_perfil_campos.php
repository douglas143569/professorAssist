<?php
/** Campos do perfil da crianca (cadastro e edicao). Variaveis: $c (array|null), $turmas, $periodos, $professoraPadrao. */
$v = static fn (string $k, $padrao = '') => pf_h($c[$k] ?? $padrao);
?>
<div class="pf-formgrid">
    <label class="pf-span2">Nome completo
        <input type="text" name="nome" value="<?= $v('nome') ?>" required maxlength="120" autocomplete="off">
    </label>
    <label>Data de nascimento
        <input type="date" name="nascimento" value="<?= $v('nascimento') ?>" max="<?= date('Y-m-d') ?>">
    </label>
    <label>Turma
        <input type="text" name="turma" value="<?= $v('turma') ?>" required maxlength="80" list="pf-turmas" placeholder="Ex: Maternal II">
        <datalist id="pf-turmas"><?php foreach ($turmas as $t): ?><option value="<?= pf_h($t) ?>"><?php endforeach; ?></datalist>
    </label>
    <label>Período
        <select name="periodo">
            <?php foreach ($periodos as $p): ?><option <?= ($c['periodo'] ?? 'Integral') === $p ? 'selected' : '' ?>><?= pf_h($p) ?></option><?php endforeach; ?>
        </select>
    </label>
    <label>Professor(a) responsável
        <input type="text" name="professora" value="<?= $v('professora', $professoraPadrao) ?>" maxlength="120">
    </label>
    <label>Data de matrícula
        <input type="date" name="data_matricula" value="<?= $v('data_matricula') ?>">
    </label>
    <label>Foto da criança
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($c['foto_path'])): ?>
            <span class="pf-inline"><?= pf_avatar($c, 'pf-avatar--sm') ?> <span class="muted">Foto atual</span>
            <label class="pf-check"><input type="checkbox" name="remover_foto" value="1"> remover</label></span>
        <?php endif; ?>
    </label>
    <label class="pf-span2">Dados da família
        <textarea name="familia" rows="3" maxlength="3000" placeholder="Responsáveis, contatos, quem busca a criança, combinados importantes…"><?= $v('familia') ?></textarea>
        <small class="muted">Escreva só o necessário para o trabalho pedagógico. Evite CPF, RG e dados de saúde.</small>
    </label>
    <label class="pf-check pf-span2 pf-consent">
        <input type="checkbox" name="autoriza_imagem" value="1" <?= !empty($c['autoriza_imagem']) ? 'checked' : '' ?>>
        <span>A família <b>autorizou por escrito</b> o registro e uso de imagem da criança neste portfólio (fotos e vídeos).</span>
    </label>
</div>
