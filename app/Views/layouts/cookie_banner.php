<?php /* Aviso de cookies. So ha cookies essenciais (sessao), entao e um aviso informativo com "Entendi". */ ?>
<div class="ck-banner" id="ck-banner" role="dialog" aria-live="polite" aria-label="Aviso de cookies" hidden>
    <p>
        🍪 Usamos apenas <strong>cookies essenciais</strong> para manter você conectado(a) e proteger sua conta.
        Não usamos cookies de publicidade nem de rastreamento.
        <a href="/cookies">Política de Cookies</a> · <a href="/privacidade">Política de Privacidade</a>
    </p>
    <button type="button" class="btn btn--primary btn--sm" id="ck-ok">Entendi</button>
</div>
<script>
    (function () {
        var chave = 'ap_aviso_cookies';
        var banner = document.getElementById('ck-banner');
        var visto = false;
        try { visto = localStorage.getItem(chave) === '1'; } catch (e) {}
        if (!visto) banner.hidden = false;
        document.getElementById('ck-ok').addEventListener('click', function () {
            try { localStorage.setItem(chave, '1'); } catch (e) {}
            banner.hidden = true;
        });
    })();
</script>
