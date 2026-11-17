            </main>
            <footer class="legal-foot legal-foot--app"><a href="/privacidade">Privacidade</a> · <a href="/cookies">Cookies</a> · <a href="/meus-dados">Meus dados (LGPD)</a></footer>
        </div>
    </div>

    <link rel="stylesheet" href="/assets/css/dark-theme.css">

    <script>
        // Links "Criar ..." (#ancora): rola ate o formulario e poe o cursor no primeiro campo.
        (function () {
            function irParaFormulario(id) {
                var alvo = id && document.getElementById(id);
                if (!alvo) return false;
                var campo = alvo.querySelector('input:not([type="hidden"]):not([type="checkbox"]), textarea, select');
                if (!campo) return false;
                alvo.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function () { campo.focus({ preventScroll: true }); }, 450);
                return true;
            }
            document.addEventListener('click', function (e) {
                var a = e.target.closest && e.target.closest('a[href^="#"]');
                if (!a || a.getAttribute('href').length < 2) return;
                if (irParaFormulario(a.getAttribute('href').slice(1))) e.preventDefault();
            });
            if (location.hash.length > 1) irParaFormulario(decodeURIComponent(location.hash.slice(1)));
        })();
    </script>

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
    <?php require __DIR__ . '/cookie_banner.php'; ?>
</body>
</html>
