<?php
    $formato = $item['formato'] ?? 'escrever';
    $itensTexto = '';
    if ($formato === 'circular') {
        foreach ($item['itens'] ?? [] as $it) {
            $itensTexto .= implode(' ', $it['opcoes'] ?? []) . ' | ' . ($it['correta'] ?? 1)
                . ' | ' . ($it['rotulo'] ?? '') . "\n";
        }
        $hint = 'uma linha por linha da folha — <code>opção1 opção2 opção3 | nº da correta | rótulo</code>';
        $ph = "🐶 🐶 🐱 🐶 | 3 | Linha 1";
    } elseif ($formato === 'ligar') {
        foreach ($item['itens'] ?? [] as $it) {
            $itensTexto .= ($it['esquerda'] ?? '') . ' | ' . ($it['esq_rotulo'] ?? '')
                . ' | ' . ($it['direita'] ?? '') . ' | ' . ($it['dir_rotulo'] ?? '') . "\n";
        }
        $hint = 'um par por linha — <code>esquerda | rótulo esq. | direita | rótulo dir.</code>';
        $ph = "🐮 | vaca | 🥛 | leite";
    } elseif ($formato === 'sequencia') {
        foreach ($item['itens'] ?? [] as $it) {
            $itensTexto .= implode(' ', $it['sequencia'] ?? []) . ' | ' . ($it['resposta'] ?? '')
                . ' | ' . ($it['rotulo'] ?? '') . "\n";
        }
        $hint = 'uma linha por sequência — <code>e1 e2 e3 | resposta | rótulo</code>';
        $ph = "🔴 🔵 🔴 🔵 | 🔴 | Linha 1";
    } elseif ($formato === 'pintar') {
        foreach ($item['itens'] ?? [] as $it) {
            $itensTexto .= ($it['figura'] ?? '') . ' | ' . ($it['rotulo'] ?? '')
                . ' | ' . (!empty($it['pintar']) ? 'sim' : 'nao') . "\n";
        }
        $hint = 'um por linha — <code>figura | rótulo | pintar? (sim/nao)</code>';
        $ph = "🍎 | maçã | sim";
    } else {
        foreach ($item['itens'] ?? [] as $it) {
            $itensTexto .= ($it['figura'] ?? '') . ' | ' . ($it['rotulo'] ?? '') . ' | ' . ($it['resposta'] ?? '') . "\n";
        }
        $hint = 'um por linha — <code>figura | rótulo | resposta</code>';
        $ph = "🐶 | cachorro | C";
    }
?>
    <p class="breadcrumb">
        <a href="/creche/pacotes">Pacotes</a> /
        <a href="/creche/pacotes/<?= (int) $item['pacote_id'] ?>"><?= htmlspecialchars($item['pacote_tema']) ?></a> /
        Atividade
    </p>

    <div class="page-header">
        <h1>Editar atividade</h1>
        <a href="/creche/pacotes/<?= (int) $item['pacote_id'] ?>/imprimir?itens=<?= (int) $item['id'] ?>" class="btn btn--ghost">Ver no tamanho de impressão ↗</a>
    </div>

    <form method="post" action="/creche/pacote-itens/<?= (int) $item['id'] ?>" class="form">
        <label>Título
            <input type="text" name="titulo" value="<?= htmlspecialchars($item['titulo']) ?>" required>
        </label>
        <div class="form--inline">
            <label style="flex:1 1 240px;">Tipo de atividade
                <input type="text" name="tipo" value="<?= htmlspecialchars($item['tipo'] ?? '') ?>" placeholder="Ex: Letra inicial">
            </label>
            <label style="flex:0 1 220px;">Formato
                <select name="formato" onchange="this.form.submit()">
                    <option value="escrever" <?= $formato === 'escrever' ? 'selected' : '' ?>>Escrever (figura → quadro)</option>
                    <option value="circular" <?= $formato === 'circular' ? 'selected' : '' ?>>Circular (opções → circule)</option>
                    <option value="ligar" <?= $formato === 'ligar' ? 'selected' : '' ?>>Ligar (relacionar pares)</option>
                    <option value="pintar" <?= $formato === 'pintar' ? 'selected' : '' ?>>Pintar (colorir figuras)</option>
                    <option value="sequencia" <?= $formato === 'sequencia' ? 'selected' : '' ?>>Sequência (o que vem depois)</option>
                </select>
            </label>
        </div>
        <p class="muted" style="font-size:0.8rem; margin-top:-6px;">Trocar o formato recarrega o modelo dos itens abaixo.</p>

        <label>Instrução para a criança
            <textarea name="instrucao" rows="2"><?= htmlspecialchars($item['instrucao'] ?? '') ?></textarea>
        </label>
        <label>Itens <span class="muted">(<?= $hint ?>)</span>
            <textarea name="itens_texto" rows="8" class="mono" placeholder="<?= htmlspecialchars($ph) ?>"><?= htmlspecialchars(trim($itensTexto)) ?></textarea>
        </label>
        <div class="actions">
            <button type="submit" class="btn btn--primary">Salvar alterações</button>
            <a href="/creche/pacotes/<?= (int) $item['pacote_id'] ?>" class="btn btn--ghost">Voltar ao pacote</a>
        </div>
    </form>

    <form method="post" action="/creche/pacote-itens/<?= (int) $item['id'] ?>/excluir"
          onsubmit="return confirm('Remover esta atividade do pacote?');" style="margin-top:12px;">
        <button type="submit" class="btn btn--danger">Remover do pacote</button>
    </form>
