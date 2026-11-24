/* Pagina de pagamento: PIX (QR Code) e cartao (MercadoPago.js, campos seguros). */
(function () {
    var root = document.getElementById('pg');
    if (!root) return;

    var cfg = { plan: root.dataset.plan, promo: root.dataset.promo, price: root.dataset.price, key: root.dataset.key, token: root.dataset.token };
    var $ = function (id) { return document.getElementById(id); };
    var msg = $('pg-msg'), cpfEl = $('pg-cpf');
    var pollTimer = null, countTimer = null, mp = null;

    function aviso(texto, info) {
        msg.textContent = texto; msg.hidden = !texto; msg.classList.toggle('is-info', !!info);
    }
    function busy(btn, on, label) {
        if (on) { btn.dataset.l = btn.textContent; btn.textContent = label || 'Processando…'; }
        else if (btn.dataset.l) { btn.textContent = btn.dataset.l; }
        btn.classList.toggle('is-loading', on);
    }

    // ---- CPF: mascara + validacao ----
    cpfEl.addEventListener('input', function () {
        var d = cpfEl.value.replace(/\D/g, '').slice(0, 11);
        cpfEl.value = d.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    });
    function cpfDigits() { return cpfEl.value.replace(/\D/g, ''); }
    function cpfOk(c) {
        if (c.length !== 11 || /^(\d)\1{10}$/.test(c)) return false;
        for (var t = 9; t < 11; t++) {
            var s = 0;
            for (var i = 0; i < t; i++) s += +c[i] * (t + 1 - i);
            if (+c[t] !== ((10 * s) % 11) % 10) return false;
        }
        return true;
    }
    function exigirCpf() {
        if (cpfOk(cpfDigits())) return true;
        aviso('Informe um CPF válido para continuar.'); cpfEl.focus(); return false;
    }

    // ---- Abas ----
    document.querySelectorAll('.pg-tab').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('.pg-tab').forEach(function (x) { x.classList.toggle('is-on', x === b); });
            $('pane-pix').hidden = b.dataset.tab !== 'pix';
            $('pane-cartao').hidden = b.dataset.tab !== 'cartao';
            aviso('');
        });
    });

    // ---- POST helper (FormData leva o token CSRF) ----
    function post(url, dados) {
        var fd = new FormData();
        fd.append('_token', cfg.token);
        fd.append('plan_id', cfg.plan);
        if (cfg.promo) fd.append('promotion_id', cfg.promo);
        fd.append('cpf', cpfDigits());
        Object.keys(dados || {}).forEach(function (k) { if (dados[k] != null) fd.append(k, dados[k]); });
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (r) {
                return r.json().catch(function () {
                    return { ok: false, mensagem: r.status === 403 ? 'Sessão expirada. Recarregue a página e tente de novo.' : 'Erro inesperado. Tente novamente.' };
                });
            });
    }

    // ---- Confirmacao (polling) ----
    function sucesso() {
        clearInterval(pollTimer); clearInterval(countTimer);
        ['pane-pix', 'pane-cartao'].forEach(function (id) { $(id).hidden = true; });
        document.querySelectorAll('#pg-main > h2, #pg-main > .pg-tabs, #pg-main > .pg-lbl, #pg-main > .pg-in').forEach(function (e) { e.hidden = true; });
        aviso(''); $('pg-ok').hidden = false;
        setTimeout(function () { location.href = '/assinatura'; }, 3500);
    }
    function acompanhar(pagamento) {
        clearInterval(pollTimer);
        pollTimer = setInterval(function () {
            fetch('/assinatura/pagar/status?pagamento=' + encodeURIComponent(pagamento), { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.status === 'approved') sucesso();
                    else if (d.status === 'cancelled' || d.status === 'rejected') {
                        clearInterval(pollTimer); clearInterval(countTimer);
                        aviso('Este pagamento expirou ou foi cancelado. Gere um novo.');
                        $('pix-show').hidden = true; $('pix-start').hidden = false;
                    }
                }).catch(function () {});
        }, 4000);
    }

    // ---- PIX ----
    $('btn-pix').addEventListener('click', function () {
        aviso('');
        if (!exigirCpf()) return;
        var btn = this; busy(btn, true, 'Gerando PIX…');
        post('/assinatura/pagar/pix').then(function (d) {
            busy(btn, false);
            if (!d.ok) { aviso(d.mensagem || 'Não foi possível gerar o PIX.'); return; }
            $('pix-img').src = d.qr_code_base64 ? 'data:image/png;base64,' + d.qr_code_base64 : '';
            $('pix-img').hidden = !d.qr_code_base64;
            $('pix-code').value = d.qr_code;
            $('pix-start').hidden = true; $('pix-show').hidden = false;

            var resta = d.expira_em || 1800;
            clearInterval(countTimer);
            countTimer = setInterval(function () {
                resta--;
                if (resta <= 0) { clearInterval(countTimer); $('pix-timer').textContent = '(expirado)'; return; }
                $('pix-timer').textContent = '(expira em ' + Math.floor(resta / 60) + ':' + String(resta % 60).padStart(2, '0') + ')';
            }, 1000);
            acompanhar(d.pagamento);
        }).catch(function () { busy(btn, false); aviso('Falha de conexão. Tente novamente.'); });
    });

    $('btn-copy').addEventListener('click', function () {
        var inp = $('pix-code'), b = this;
        var ok = function () { b.textContent = 'Copiado ✓'; setTimeout(function () { b.textContent = 'Copiar'; }, 2000); };
        if (navigator.clipboard) navigator.clipboard.writeText(inp.value).then(ok);
        else { inp.select(); document.execCommand('copy'); ok(); }
    });

    // ---- Cartao ----
    if (!cfg.key || typeof MercadoPago === 'undefined' || !$('btn-cartao')) return;

    mp = new MercadoPago(cfg.key, { locale: 'pt-BR' });
    var campoSeguroEstilo = {
        style: {
            base: { color: '#ffffff', fontSize: '16px' },
            placeholder: { color: '#ffffff', fontSize: '16px' },
            invalid: { color: '#ffb3b3' }
        }
    };
    var fNumero = mp.fields.create('cardNumber', Object.assign({ placeholder: '0000 0000 0000 0000' }, campoSeguroEstilo)).mount('f-numero');
    mp.fields.create('expirationDate', Object.assign({ placeholder: 'MM/AA' }, campoSeguroEstilo)).mount('f-validade');
    mp.fields.create('securityCode', Object.assign({ placeholder: 'CVV' }, campoSeguroEstilo)).mount('f-cvv');

    var metodo = null, emissor = '', binAtual = '';
    fNumero.on('binChange', function (e) {
        var bin = e && e.bin ? e.bin : '';
        if (bin === binAtual) return;
        binAtual = bin; metodo = null; emissor = '';
        if (!bin) return;

        mp.getPaymentMethods({ bin: bin }).then(function (r) {
            var lista = r.results || [];
            var pm = lista.filter(function (m) { return m.payment_type_id === 'credit_card'; })[0] || lista[0];
            if (!pm) return null;
            metodo = pm.id;
            if (pm.issuer && pm.issuer.id) emissor = String(pm.issuer.id);
            return mp.getInstallments({ amount: cfg.price, bin: bin, paymentTypeId: 'credit_card' });
        }).then(function (inst) {
            if (!inst || !inst[0]) return;
            var sel = $('c-parcelas');
            if (inst[0].issuer && inst[0].issuer.id) emissor = String(inst[0].issuer.id);
            sel.innerHTML = '';
            inst[0].payer_costs.forEach(function (c) {
                var o = document.createElement('option');
                o.value = c.installments; o.textContent = c.recommended_message;
                sel.appendChild(o);
            });
        }).catch(function () {});
    });

    $('btn-cartao').addEventListener('click', function () {
        aviso('');
        if (!exigirCpf()) return;
        var nome = $('c-nome').value.trim();
        if (nome.length < 3) { aviso('Informe o nome impresso no cartão.'); $('c-nome').focus(); return; }
        if (!metodo) { aviso('Digite o número do cartão para continuar.'); return; }

        var btn = this; busy(btn, true, 'Processando pagamento…');
        mp.fields.createCardToken({ cardholderName: nome, identificationType: 'CPF', identificationNumber: cpfDigits() })
            .then(function (tk) {
                return post('/assinatura/pagar/cartao', {
                    token: tk.id, payment_method_id: metodo, issuer_id: emissor,
                    installments: $('c-parcelas').value, device_id: window.MP_DEVICE_SESSION_ID || ''
                });
            })
            .then(function (d) {
                busy(btn, false);
                if (d.status === 'approved') { sucesso(); return; }
                if (d.ok) { aviso(d.mensagem, true); acompanhar(d.pagamento); return; }
                aviso(d.mensagem || 'Pagamento não aprovado.');
            })
            .catch(function (err) {
                busy(btn, false);
                aviso('Confira os dados do cartão (número, validade e CVV) e tente novamente.');
                if (window.console) console.warn(err);
            });
    });
})();
