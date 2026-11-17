<?php
    $titulo = 'Política de Cookies';
    $ativa = 'cookies';
    require __DIR__ . '/_topo.php';
?>
            <p>
                Cookies são pequenos arquivos que o navegador guarda. Esta política explica quais usamos no
                <strong>Assistente do Professor</strong> e por quê. Ela complementa a <a href="/privacidade">Política de Privacidade</a>.
            </p>

            <h2>Resumo</h2>
            <ul>
                <li>Usamos <strong>somente cookies estritamente necessários</strong> ao funcionamento e à segurança.</li>
                <li><strong>Não usamos</strong> cookies de publicidade, análise de audiência (analytics) ou rastreamento entre sites.</li>
                <li>Por serem necessários, não dependem de consentimento (LGPD e guia de cookies da ANPD); mesmo assim mostramos um aviso, para transparência.</li>
            </ul>

            <h2>Cookies e armazenamento que usamos</h2>
            <div class="legal-table-wrap">
            <table class="legal-table">
                <thead><tr><th>Nome</th><th>Tipo</th><th>Finalidade</th><th>Duração</th></tr></thead>
                <tbody>
                    <tr>
                        <td><code>PHPSESSID</code></td><td>Essencial (cookie)</td>
                        <td>Mantém você conectado(a) entre as páginas, guarda o token de proteção dos formulários (CSRF) e mensagens temporárias. É <em>HttpOnly</em>, <em>SameSite=Lax</em> e <em>Secure</em> quando o site usa HTTPS.</td>
                        <td>Sessão (some ao fechar o navegador ou ao sair da conta)</td>
                    </tr>
                    <tr>
                        <td><code>ap_aviso_cookies</code></td><td>Essencial (armazenamento local)</td>
                        <td>Lembra que você já viu o aviso de cookies, para não mostrá-lo de novo. Não identifica você.</td>
                        <td>Até você limpar os dados do navegador</td>
                    </tr>
                </tbody>
            </table>
            </div>

            <h2>Cookies de terceiros</h2>
            <p>
                Ao pagar a assinatura você é levado(a) ao ambiente do <strong>Mercado Pago</strong>, que pode usar os próprios cookies
                (por exemplo, para prevenção a fraudes). Eles são regidos pela política do Mercado Pago, e não temos acesso a eles.
            </p>

            <h2>Como gerenciar</h2>
            <p>
                Você pode bloquear ou apagar cookies nas configurações do navegador. Se bloquear o cookie de sessão, o login não funcionará.
                Para rever o aviso, limpe os dados do site no navegador.
            </p>

            <h2>Alterações</h2>
            <p>Se passarmos a usar outros cookies (por exemplo, de medição), atualizaremos esta página e pediremos o seu consentimento antes, quando exigido.</p>
<?php require __DIR__ . '/_rodape.php'; ?>
