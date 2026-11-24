(function () {
    const container = document.getElementById('cron-campos-dia');
    const form = container && container.closest('form');
    if (!container || !form) return;

    const monthInput = form.querySelector('input[name="mes"]');
    const countInput = form.querySelector('#cron-dias-quantidade');
    let campos;
    try { campos = JSON.parse(container.dataset.campos || '[]'); } catch (_) { campos = []; }

    function datasUteis(mes) {
        if (!/^\d{4}-\d{2}$/.test(mes || '')) return [];
        const [ano, numeroMes] = mes.split('-').map(Number);
        const ultimoDia = new Date(ano, numeroMes, 0).getDate();
        const datas = [];
        for (let dia = 1; dia <= ultimoDia; dia++) {
            const data = new Date(ano, numeroMes - 1, dia);
            if (data.getDay() !== 0 && data.getDay() !== 6) {
                datas.push({
                    valor: ano + '-' + String(numeroMes).padStart(2, '0') + '-' + String(dia).padStart(2, '0'),
                    rotulo: new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: '2-digit', month: '2-digit' }).format(data)
                });
            }
        }
        return datas;
    }

    function desenhar() {
        const datas = datasUteis(monthInput.value);
        countInput.max = String(datas.length);
        const quantidade = Math.max(1, Math.min(Number(countInput.value) || datas.length, datas.length));
        countInput.value = String(quantidade);

        const anteriores = {};
        container.querySelectorAll('select[name^="campo_dia"]').forEach(select => {
            anteriores[select.name] = select.value;
        });
        container.replaceChildren();

        datas.slice(0, quantidade).forEach((dia, indice) => {
            const label = document.createElement('label');
            label.style.cssText = 'flex:1 1 260px; min-width:220px;';
            label.textContent = 'Dia ' + (indice + 1) + ' · ' + dia.rotulo;

            const select = document.createElement('select');
            select.name = 'campo_dia[' + dia.valor + ']';
            select.required = true;
            const vazio = document.createElement('option');
            vazio.value = '';
            vazio.textContent = 'Selecione o campo da BNCC';
            select.appendChild(vazio);
            campos.forEach(campo => {
                const option = document.createElement('option');
                option.value = campo;
                option.textContent = campo;
                select.appendChild(option);
            });
            select.value = anteriores[select.name] || '';
            label.appendChild(select);
            container.appendChild(label);
        });
    }

    countInput.addEventListener('input', desenhar);
    monthInput.addEventListener('change', desenhar);
    desenhar();
})();
