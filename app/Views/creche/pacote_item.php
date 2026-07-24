    <p class="breadcrumb">
        <a href="/creche/pacotes">Pacotes</a> /
        <a href="/creche/pacotes/<?= (int) $item['pacote_id'] ?>"><?= htmlspecialchars($item['pacote_tema']) ?></a> /
        Atividade
    </p>

    <div class="page-header">
        <h1>Editar atividade</h1>
    </div>

    <form method="post" action="/creche/pacote-itens/<?= (int) $item['id'] ?>" class="form">
        <label>Título
            <input type="text" name="titulo" value="<?= htmlspecialchars($item['titulo']) ?>" required>
        </label>
        <div class="form--inline">
            <label style="flex:1 1 240px;">Tipo de atividade
                <input type="text" name="tipo" value="<?= htmlspecialchars($item['tipo'] ?? '') ?>" placeholder="Ex: Brincadeira de memória">
            </label>
            <label style="flex:0 1 150px;">Duração
                <input type="text" name="duracao" value="<?= htmlspecialchars($item['duracao'] ?? '') ?>">
            </label>
        </div>
        <label>Como conduzir
            <textarea name="descricao" rows="8"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
        </label>
        <label>Materiais
            <textarea name="materiais" rows="3"><?= htmlspecialchars($item['materiais'] ?? '') ?></textarea>
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
