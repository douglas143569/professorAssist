<?php /** Campos de um projeto. Variavel: $p (array|null). */ ?>
<label>Nome do projeto
    <input type="text" name="titulo" value="<?= pf_h($p['titulo'] ?? '') ?>" maxlength="160" required placeholder="Ex.: Descobrindo as Cores">
</label>
<div class="pf-formgrid pf-formgrid--2">
    <label>Início <input type="date" name="data_inicio" value="<?= pf_h($p['data_inicio'] ?? '') ?>"></label>
    <label>Fim <input type="date" name="data_fim" value="<?= pf_h($p['data_fim'] ?? '') ?>"></label>
</div>
<label>Objetivos
    <?= pf_area_texto('objetivos', (string) ($p['objetivos'] ?? ''), ['rows' => 4, 'max' => 3000, 'ph' => 'O que as crianças vão explorar e desenvolver?']) ?>
</label>
<label>Atividades realizadas
    <?= pf_area_texto('atividades', (string) ($p['atividades'] ?? ''), ['rows' => 4, 'max' => 3000, 'ph' => 'Ex.: pintura com as mãos, caça às cores, mistura de tintas…']) ?>
</label>
