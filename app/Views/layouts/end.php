            </main>
        </div>
    </div>

    <link rel="stylesheet" href="/assets/css/dark-theme.css">

    <script>
        // Feedback de carregamento nos formularios de IA (a geracao leva alguns segundos).
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.classList || !form.classList.contains('js-ai')) return;
            var btn = form.querySelector('[type="submit"]');
            if (!btn || btn.classList.contains('is-loading')) return;
            btn.classList.add('is-loading');
            var label = btn.getAttribute('data-loading') || 'Gerando…';
            btn.innerHTML = '<span class="spinner"></span> ' + label;
        }, true);
    </script>

    <script>
        // Elementos visuais dinamicos: revelacao ao rolar + contadores.
        (function () {
            var semAnim = window.matchMedia
                && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // --- Revelacao ao rolar ---------------------------------------
            var alvos = document.querySelectorAll('.js-reveal');
            if (semAnim || !('IntersectionObserver' in window)) {
                alvos.forEach(function (el) { el.classList.add('is-visible'); });
            } else {
                var io = new IntersectionObserver(function (entradas) {
                    entradas.forEach(function (ent) {
                        if (ent.isIntersecting) {
                            ent.target.classList.add('is-visible');
                            io.unobserve(ent.target);
                        }
                    });
                }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
                alvos.forEach(function (el) { io.observe(el); });
            }

            // --- Contadores (data-count) --------------------------------
            function anima(el) {
                var alvo = parseInt(el.getAttribute('data-count'), 10) || 0;
                if (semAnim || alvo === 0) { el.textContent = alvo; return; }
                var ini = null, dur = 700;
                function passo(ts) {
                    if (ini === null) ini = ts;
                    var p = Math.min((ts - ini) / dur, 1);
                    var eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = Math.round(alvo * eased);
                    if (p < 1) requestAnimationFrame(passo);
                    else el.textContent = alvo;
                }
                requestAnimationFrame(passo);
            }

            var contadores = document.querySelectorAll('[data-count]');
            if (!('IntersectionObserver' in window)) {
                contadores.forEach(anima);
            } else {
                var ioc = new IntersectionObserver(function (entradas) {
                    entradas.forEach(function (ent) {
                        if (ent.isIntersecting) { anima(ent.target); ioc.unobserve(ent.target); }
                    });
                }, { threshold: 0.4 });
                contadores.forEach(function (el) { ioc.observe(el); });
            }
        })();
    </script>
</body>
</html>
