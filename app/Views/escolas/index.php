<div class="schools-page">
    <section class="schools-hero">
        <div class="schools-hero__title"><span class="schools-icon">▦</span><div><h1>Escolas</h1><p>Organize suas escolas e gerencie turmas, matérias e temas em um só lugar.</p></div></div>
        <div class="schools-hero__art"><span>✦</span><div><strong>Tudo organizado<br>em um só lugar</strong><p>Cadastre suas escolas e mantenha sua rotina pedagógica em ordem.</p></div><i aria-hidden="true">🏫</i></div>
    </section>

    <nav class="schools-steps" aria-label="Etapas de organização">
        <div class="is-current"><b>1</b><span><strong>Escola</strong><small>Cadastre ou selecione</small></span></div><i>→</i>
        <div><b>2</b><span><strong>Turma</strong><small>Crie suas turmas</small></span></div><i>→</i>
        <div><b>3</b><span><strong>Matéria</strong><small>Defina as matérias</small></span></div><i>→</i>
        <div><b>4</b><span><strong>Tema da aula</strong><small>Comece a criar</small></span></div>
    </nav>

    <div class="schools-columns">
        <section class="schools-list card">
            <div class="schools-section-head"><div><h2>Minhas escolas</h2><p>Veja e gerencie todas as suas escolas cadastradas.</p></div>
                <label class="schools-search"><span aria-hidden="true">⌕</span><input type="search" id="schools-search" placeholder="Buscar escola..." aria-label="Buscar escola"></label>
            </div>
            <?php if (empty($escolas)): ?>
                <div class="schools-empty"><span>▦</span><strong>Nenhuma escola cadastrada ainda</strong><p>Cadastre sua primeira escola para começar a organizar suas turmas.</p><a href="#nova-escola">Cadastrar primeira escola ↓</a></div>
            <?php else: ?>
                <div class="schools-cards" id="schools-cards">
                    <?php foreach ($escolas as $n => $e): ?>
                        <article class="school-card" style="--i:<?= $n % 12 ?>" data-school-name="<?= htmlspecialchars(mb_strtolower($e['nome'] . ' ' . ($e['cidade'] ?? '') . ' ' . ($e['uf'] ?? ''))) ?>">
                            <form class="sc-del" method="post" action="/escolas/<?= (int) $e['id'] ?>/excluir" onsubmit="return confirm('Excluir a escola &quot;<?= htmlspecialchars($e['nome'], ENT_QUOTES) ?>&quot; e tudo dentro dela (turmas, matérias, temas, conteúdos e questões)?');">
                                <?= \App\Services\Csrf::campo() ?><button type="submit" aria-label="Excluir <?= htmlspecialchars($e['nome']) ?>" title="Excluir escola">⋮</button>
                            </form>
                            <div class="sc-tile" aria-hidden="true"><svg viewBox="0 0 24 24" width="52" height="52" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10 12 4l9 6"/><path d="M5 10v9h14v-9"/><path d="M9 19v-5h6v5"/><path d="M12 4V2"/><path d="M8 12.5h.01M16 12.5h.01"/></svg></div>
                            <div class="sc-waves" aria-hidden="true"><svg viewBox="0 0 400 80" preserveAspectRatio="none"><path class="sc-w1" d="M0 40Q50 0 100 40T200 40T300 40T400 40V80H0Z"/><path class="sc-w2" d="M0 52Q50 22 100 52T200 52T300 52T400 52V80H0Z"/></svg></div>
                            <h3 class="sc-pill"><?= htmlspecialchars($e['nome']) ?></h3>
                            <div class="sc-meta">
                                <span>♧ <?= (int) $e['n_turmas'] ?> <?= (int) $e['n_turmas'] === 1 ? 'turma' : 'turmas' ?></span>
                                <?php if (!empty($e['rede'])): ?><span>▣ <?= htmlspecialchars($redes[$e['rede']] ?? $e['rede']) ?></span><?php endif; ?>
                                <?php if (!empty($e['cidade']) || !empty($e['uf'])): ?><span>⌖ <?= htmlspecialchars(trim(($e['cidade'] ?? '') . (!empty($e['uf']) ? ' - ' . $e['uf'] : ''))) ?></span><?php endif; ?>
                            </div>
                            <span class="sc-status <?= (int) $e['n_turmas'] > 0 ? 'is-ready' : '' ?>"><i></i><?= (int) $e['n_turmas'] > 0 ? 'Em andamento' : 'Comece por uma turma' ?></span>
                            <a href="/escolas/<?= (int) $e['id'] ?>" class="sc-open">Abrir <span aria-hidden="true">↗</span></a>
                        </article>
                    <?php endforeach; ?>
                    <p class="schools-no-results" id="schools-no-results" hidden>Nenhuma escola encontrada para essa busca.</p>
                </div>
            <?php endif; ?>
        </section>

        <aside class="schools-form-card card" id="nova-escola">
            <div class="schools-form-heading"><span>＋</span><div><h2>Cadastrar nova escola</h2><p>Preencha as informações para adicionar uma escola.</p></div></div>
            <form method="post" action="/escolas" class="form schools-form">
                <?= \App\Services\Csrf::campo() ?>
                <label>Nome da escola <em>*</em><input type="text" name="nome" placeholder="Ex: EMEF Machado de Assis" required></label>
                <label>Rede de ensino<select name="rede"><option value="">Selecione uma rede de ensino</option><?php foreach ($redes as $valor => $rotulo): ?><option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($rotulo) ?></option><?php endforeach; ?></select></label>
                <div class="schools-form__row"><label>Cidade <span>(opcional)</span><input type="text" name="cidade" placeholder="Ex: Campinas"></label><label>UF <span>(opcional)</span><select name="uf"><option value="">Selecione</option><?php foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?><option value="<?= $uf ?>"><?= $uf ?></option><?php endforeach; ?></select></label></div>
                <label>Endereço <span>(opcional)</span><input type="text" name="endereco" placeholder="Rua, número, bairro"></label>
                <label>Telefone <span>(opcional)</span><input type="tel" name="telefone" placeholder="(19) 3333-0000"></label>
                <button type="submit" class="btn btn--primary">▦ &nbsp; Cadastrar escola</button>
                <small class="schools-privacy">♙ Seus dados são usados apenas para organizar sua conta.</small>
            </form>
        </aside>
    </div>
    <aside class="schools-tip"><span class="schools-tip__icon">◆</span><div><strong>Dica: depois de cadastrar a escola, crie suas turmas e comece a organizar suas aulas.</strong><p>Em poucos passos você terá tudo organizado.</p></div><a href="#nova-escola">Cadastrar uma escola <span aria-hidden="true">→</span></a></aside>
</div>
<script>
(() => {
    const input = document.getElementById('schools-search');
    const cards = [...document.querySelectorAll('.school-card')];
    const empty = document.getElementById('schools-no-results');
    if (!input) return;
    input.addEventListener('input', () => {
        const query = input.value.trim().toLocaleLowerCase('pt-BR');
        let visible = 0;
        cards.forEach(card => { const match = card.dataset.schoolName.includes(query); card.hidden = !match; if (match) visible++; });
        if (empty) empty.hidden = visible !== 0;
    });
})();
</script>
