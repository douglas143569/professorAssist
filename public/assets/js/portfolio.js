/* Portfolio: assistente de IA nos campos de texto, previa de arquivos e lightbox. */
(function () {
    'use strict';
    var raiz = document.querySelector('.pf[data-token]');
    var token = raiz ? raiz.dataset.token : '';

    // ---------- Assistente de IA: <textarea data-ia="redigir,corrigir" ...> ----------
    var ROTULOS = {
        redigir: '✨ Escrever com IA',
        corrigir: '✓ Corrigir ortografia',
        habilidades: '✨ Sugerir habilidades',
        avaliacao: '✨ Gerar com IA'
    };

    document.querySelectorAll('textarea[data-ia]').forEach(function (campo) {
        var barra = document.createElement('div');
        barra.className = 'pf-ia';
        var msg = document.createElement('span');
        msg.className = 'pf-ia__msg';
        msg.setAttribute('role', 'status');
        msg.setAttribute('aria-live', 'polite');

        campo.dataset.ia.split(',').forEach(function (acao) {
            acao = acao.trim();
            if (!ROTULOS[acao]) { return; }
            var b = document.createElement('button');
            b.type = 'button';
            b.textContent = ROTULOS[acao];
            b.addEventListener('click', function () { executar(campo, acao, b, msg); });
            barra.appendChild(b);
        });
        barra.appendChild(msg);
        campo.insertAdjacentElement('afterend', barra);
    });

    function executar(campo, acao, botao, msg) {
        var fonte = campo.dataset.fonte ? document.querySelector(campo.dataset.fonte) : campo;
        var texto = fonte ? fonte.value.trim() : '';

        if (acao !== 'avaliacao' && texto === '') {
            aviso(msg, acao === 'habilidades' ? 'Escreva a observação do registro primeiro.' : 'Escreva algo no campo primeiro.', true);
            return;
        }

        var antes = campo.value;
        var rotulo = botao.textContent;
        botao.disabled = true;
        botao.textContent = 'Pensando…';
        aviso(msg, '', false);

        var fd = new FormData();
        fd.append('_token', token);
        fd.append('acao', acao);
        fd.append('texto', texto);
        fd.append('foco', campo.dataset.foco || '');
        if (campo.dataset.crianca) { fd.append('crianca_id', campo.dataset.crianca); }

        fetch('/creche/portfolio/ia', { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (r) {
                return r.json().catch(function () { return { ok: false, mensagem: 'Sessão expirada. Recarregue a página.' }; });
            })
            .then(function (d) {
                if (!d.ok) { aviso(msg, d.mensagem || 'Não foi possível usar a IA agora.', true); return; }
                campo.value = d.texto;
                campo.dispatchEvent(new Event('input', { bubbles: true }));
                msg.className = 'pf-ia__msg';
                msg.textContent = 'Pronto! Revise o texto. ';
                var desfazer = document.createElement('button');
                desfazer.type = 'button';
                desfazer.textContent = 'Desfazer';
                desfazer.addEventListener('click', function () { campo.value = antes; msg.textContent = 'Texto anterior restaurado.'; });
                msg.appendChild(desfazer);
            })
            .catch(function () { aviso(msg, 'Falha de conexão. Tente novamente.', true); })
            .then(function () { botao.disabled = false; botao.textContent = rotulo; });
    }

    function aviso(el, texto, erro) {
        el.className = 'pf-ia__msg' + (erro ? ' is-erro' : '');
        el.textContent = texto;
    }

    // ---------- Previa das fotos escolhidas ----------
    document.querySelectorAll('input[type=file][data-previa]').forEach(function (inp) {
        var alvo = document.querySelector(inp.dataset.previa);
        if (!alvo) { return; }
        inp.addEventListener('change', function () {
            alvo.innerHTML = '';
            Array.prototype.slice.call(inp.files, 0, 20).forEach(function (f) {
                if (f.type.indexOf('image/') === 0) {
                    var img = document.createElement('img');
                    img.alt = f.name;
                    img.src = URL.createObjectURL(f);
                    img.onload = function () { URL.revokeObjectURL(img.src); };
                    alvo.appendChild(img);
                } else {
                    var s = document.createElement('span');
                    s.textContent = (f.type.indexOf('video/') === 0 ? '🎬 ' : '📎 ') + f.name;
                    alvo.appendChild(s);
                }
            });
        });
    });

    // ---------- Salvar: evita duplo clique durante o envio ----------
    document.querySelectorAll('form.js-pf-salvar').forEach(function (f) {
        f.addEventListener('submit', function () {
            var b = f.querySelector('[type=submit]');
            if (b) { b.disabled = true; b.textContent = b.getAttribute('data-loading') || 'Salvando…'; }
        });
    });

    // ---------- Lightbox ----------
    var lb = document.getElementById('pf-lightbox');
    if (lb && typeof lb.showModal === 'function') {
        document.addEventListener('click', function (e) {
            var a = e.target.closest && e.target.closest('a[data-lightbox]');
            if (!a) { return; }
            e.preventDefault();
            lb.querySelector('img').src = a.getAttribute('href');
            lb.querySelector('img').alt = a.dataset.leg || '';
            lb.querySelector('p').textContent = a.dataset.leg || '';
            lb.showModal();
        });
        lb.addEventListener('click', function (e) { if (e.target === lb) { lb.close(); } });
    }
})();
