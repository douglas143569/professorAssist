<?php
/** Avaliacoes descritivas. Variaveis: $lista. */
use App\Models\PortfolioAvaliacao;
?>
<div class="pf-duo">
    <form method="post" action="/creche/portfolio/<?= (int) $c['id'] ?>/avaliacoes" class="form card">
        <?= \App\Services\Csrf::campo() ?>
        <h3>Nova avaliação descritiva</h3>
        <label>Tipo
            <select name="periodicidade" id="av-per" required>
                <?php foreach (PortfolioAvaliacao::PERIODICIDADES as $k => [$rot]): ?><option value="<?= $k ?>"><?= $rot ?></option><?php endforeach; ?>
            </select>
        </label>
        <label>Período
            <select name="periodo" id="av-periodo" required></select>
        </label>
        <label>Ano letivo
            <input type="number" name="ano" value="<?= date('Y') ?>" min="2000" max="2100" required>
        </label>
        <button class="btn btn--primary" type="submit">Criar e preencher</button>
        <script>
            (function () {
                var mapa = <?= json_encode(array_map(fn ($p) => $p[1], PortfolioAvaliacao::PERIODICIDADES), JSON_UNESCAPED_UNICODE) ?>;
                var per = document.getElementById('av-per'), sel = document.getElementById('av-periodo');
                function montar() { sel.innerHTML = ''; mapa[per.value].forEach(function (p) { var o = document.createElement('option'); o.textContent = p; sel.appendChild(o); }); }
                per.addEventListener('change', montar); montar();
            })();
        </script>
    </form>

    <div class="card">
        <h3>Avaliações desta criança</h3>
        <?php if (!$lista): ?>
            <p class="muted">Nenhuma avaliação ainda. A IA ajuda a redigir cada trecho com base nos registros.</p>
        <?php else: ?>
            <ul class="pf-list">
                <?php foreach ($lista as $a):
                    $feitos = count(array_filter(array_map(fn ($k) => trim((string) $a[$k]) !== '', array_keys(PortfolioAvaliacao::SECOES))));
                ?>
                    <li><a href="/creche/portfolio/avaliacoes/<?= (int) $a['id'] ?>">
                        <span class="pf-list__ph" aria-hidden="true">📋</span>
                        <span><b><?= pf_h($a['periodo']) ?> · <?= (int) $a['ano'] ?></b><small><?= $feitos ?>/<?= count(PortfolioAvaliacao::SECOES) ?> seções preenchidas</small></span>
                    </a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
