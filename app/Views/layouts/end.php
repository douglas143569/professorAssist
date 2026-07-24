            </main>
        </div>
    </div>

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
</body>
</html>
