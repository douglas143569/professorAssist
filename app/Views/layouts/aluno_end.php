        </main>

        <footer class="aluno-foot muted">
            Área do Aluno — Assistente do Professor · <a href="/privacidade">Privacidade</a> · <a href="/cookies">Cookies</a>
        </footer>
    </div>

    <script>
        // Feedback de "enviando..." nos formularios de entrega.
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.classList || !form.classList.contains('js-enviar')) return;
            var btn = form.querySelector('[type="submit"]');
            if (!btn || btn.disabled) return;
            btn.disabled = true;
            btn.textContent = 'Enviando…';
        }, true);
    </script>
    <?php require __DIR__ . '/cookie_banner.php'; ?>
</body>
</html>
