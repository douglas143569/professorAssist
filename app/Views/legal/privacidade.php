<?php
    use App\Services\Legal;

    $titulo = 'Política de Privacidade';
    $ativa = 'privacidade';
    $controlador = htmlspecialchars(Legal::controlador());
    $contato = Legal::contatoEmail();
    require __DIR__ . '/_topo.php';
?>
            <p>
                Esta política explica, de forma simples, quais dados pessoais o <strong>Assistente do Professor</strong>
                trata, para quê, com quem compartilha e como você exerce seus direitos, conforme a
                <strong>Lei Geral de Proteção de Dados (LGPD – Lei 13.709/2018)</strong>.
            </p>

            <nav class="legal-toc" aria-label="Índice">
                <a href="#quem">1. Quem somos</a><a href="#dados">2. Dados que tratamos</a><a href="#finalidades">3. Para que usamos</a>
                <a href="#criancas">4. Crianças e adolescentes</a><a href="#compartilhamento">5. Compartilhamento</a><a href="#internacional">6. Transferência internacional</a>
                <a href="#retencao">7. Por quanto tempo guardamos</a><a href="#seguranca">8. Segurança</a><a href="#direitos">9. Seus direitos</a>
                <a href="#ia">10. Inteligência artificial</a><a href="#cookies">11. Cookies</a><a href="#alteracoes">12. Alterações e contato</a>
            </nav>

            <h2 id="quem">1. Quem somos</h2>
            <p>
                O responsável (controlador) pelos dados da sua conta é <strong><?= $controlador ?></strong><?php if (Legal::cnpj()): ?>, CNPJ <?= htmlspecialchars(Legal::cnpj()) ?><?php endif; ?>.
                <?php if ($contato): ?>Contato para assuntos de privacidade: <a href="mailto:<?= htmlspecialchars($contato) ?>"><?= htmlspecialchars($contato) ?></a>.<?php else: ?>Para assuntos de privacidade, use a página <a href="/meus-dados">Meus dados</a> (com a conta ativa) ou o canal de suporte informado no momento da contratação.<?php endif; ?>
                <?php if (Legal::encarregado()): ?> Encarregado(a) pelo tratamento de dados (DPO): <?= htmlspecialchars(Legal::encarregado()) ?>.<?php endif; ?>
            </p>
            <p>
                Sobre os dados de <strong>alunos e crianças</strong> que o professor cadastra ou escreve no sistema (por exemplo,
                relatórios individuais), o professor e a escola decidem o que registrar e por quê; nesse caso, atuamos como
                <strong>operador</strong>, tratando os dados apenas para prestar o serviço contratado.
            </p>

            <h2 id="dados">2. Dados que tratamos</h2>
            <div class="legal-table-wrap">
            <table class="legal-table">
                <thead><tr><th>Quem</th><th>Dados</th><th>De onde vêm</th></tr></thead>
                <tbody>
                    <tr><td>Professor(a)</td><td>Nome, e-mail, celular, senha (guardada apenas em formato criptografado/hash), data do último acesso.</td><td>Informados por você no cadastro.</td></tr>
                    <tr><td>Professor(a)</td><td>Conteúdo que você cria: escolas, turmas, matérias, temas, conteúdos, planos, provas, questões, tarefas, avisos, eventos, cronogramas e atividades.</td><td>Criados por você ou gerados com IA a seu pedido.</td></tr>
                    <tr><td>Alunos (Área do Aluno)</td><td>Nome, turma, senha (hash), entregas de tarefas (textos e arquivos), notas e correções.</td><td>Informados pelo próprio aluno e pelo professor.</td></tr>
                    <tr><td>Crianças da creche</td><td>No <em>Banco de relatórios</em>: nome, idade, turma e o relatório descritivo escrito pelo professor (observações sobre o desenvolvimento).</td><td>Informados pelo professor.</td></tr>
                    <tr><td>Professor(a)</td><td>Dados de assinatura e pagamento: plano, valor, status e identificador da transação. <strong>Não armazenamos número de cartão</strong>; o pagamento é processado pelo Mercado Pago.</td><td>Gerados na contratação.</td></tr>
                    <tr><td>Todos</td><td>Registros de acesso e segurança: endereço IP, navegador (user agent), data e hora de acessos e de ações importantes.</td><td>Coletados automaticamente.</td></tr>
                    <tr><td>Todos</td><td>Cookie de sessão necessário ao login (veja a <a href="/cookies">Política de Cookies</a>).</td><td>Coletado automaticamente.</td></tr>
                </tbody>
            </table>
            </div>
            <p>Não pedimos dados sensíveis por padrão. Evite escrever nos campos livres informações de saúde, religião, origem racial ou outras categorias sensíveis, a menos que seja indispensável e você tenha autorização.</p>

            <h2 id="finalidades">3. Para que usamos seus dados (e a base legal)</h2>
            <ul>
                <li><strong>Criar e manter sua conta e prestar o serviço</strong> (geração de materiais, provas, relatórios, Área do Aluno) — execução de contrato (art. 7º, V).</li>
                <li><strong>Cobrar e liberar acesso</strong> à assinatura — execução de contrato e cumprimento de obrigação legal/fiscal (art. 7º, II e V).</li>
                <li><strong>Segurança, prevenção a fraudes e limite de tentativas de login</strong> — legítimo interesse (art. 7º, IX) e obrigação legal de guardar registros de acesso (Marco Civil da Internet, art. 15).</li>
                <li><strong>Comunicações sobre sua conta</strong> (avisos de acesso, assinatura) — execução de contrato.</li>
                <li><strong>Dados de crianças e adolescentes</strong> — veja a seção 4.</li>
                <li>Registramos o seu <strong>aceite</strong> desta política (data e versão) ao criar a conta, para comprovar que você foi informado(a).</li>
            </ul>
            <p>Não vendemos dados pessoais e não os usamos para publicidade.</p>

            <h2 id="criancas">4. Crianças e adolescentes</h2>
            <p>
                O tratamento de dados de crianças e adolescentes é feito <strong>no seu melhor interesse</strong> (LGPD, art. 14).
                Dados de crianças (até 12 anos) só devem ser inseridos com o <strong>consentimento específico e em destaque de pelo menos um dos pais ou responsável legal</strong>,
                coletado pela escola ou pelo professor. Ao criar o acesso de um aluno menor de idade, o responsável declara ter essa autorização.
            </p>
            <ul>
                <li>Coletamos o mínimo necessário: o aluno acessa apenas com nome, turma e senha (sem e-mail ou celular).</li>
                <li>Os relatórios da creche ficam visíveis <strong>somente</strong> ao professor que os criou e podem ser excluídos a qualquer momento.</li>
                <li>Não usamos dados de crianças para publicidade, perfilamento comercial ou treinamento de modelos de IA.</li>
                <li>Responsáveis podem pedir acesso, correção ou exclusão dos dados da criança à escola/professor ou diretamente pelo contato acima.</li>
            </ul>

            <h2 id="compartilhamento">5. Com quem compartilhamos</h2>
            <ul>
                <li><strong>Anthropic (IA Claude)</strong> — recebe os textos necessários para gerar conteúdos, atividades e relatórios (veja a seção 10).</li>
                <li><strong>Mercado Pago</strong> — processa pagamentos (PIX, cartão, boleto) e nos informa o status da transação.</li>
                <li><strong>Provedor de hospedagem e banco de dados</strong> — armazena o sistema e os dados com as medidas de segurança contratadas.</li>
                <li><strong>Autoridades</strong> — quando houver ordem judicial ou obrigação legal.</li>
            </ul>
            <p>Os professores <strong>não</strong> veem dados uns dos outros: cada conta só acessa o que ela mesma criou.</p>

            <h2 id="internacional">6. Transferência internacional</h2>
            <p>
                A IA e alguns serviços de infraestrutura podem processar dados em servidores fora do Brasil (por exemplo, nos Estados Unidos).
                Nesses casos, buscamos garantir nível de proteção compatível com a LGPD (art. 33), por meio de cláusulas contratuais e políticas dos fornecedores.
            </p>

            <h2 id="retencao">7. Por quanto tempo guardamos</h2>
            <ul>
                <li><strong>Conta e conteúdo:</strong> enquanto a conta existir. Ao <a href="/meus-dados">excluir a conta</a>, seu conteúdo e os dados dos alunos vinculados são apagados.</li>
                <li><strong>Registros de acesso:</strong> mantidos por pelo menos 6 meses (Marco Civil da Internet), sem conteúdo pedagógico.</li>
                <li><strong>Dados de pagamento e assinatura:</strong> mantidos pelo prazo exigido pela legislação fiscal e contábil (em regra, 5 anos), sem os seus dados de contato.</li>
                <li><strong>Cópias de segurança:</strong> podem conservar dados por um curto período até serem sobrescritas.</li>
            </ul>

            <h2 id="seguranca">8. Segurança</h2>
            <p>
                Senhas são armazenadas com hash; formulários têm proteção contra CSRF; os cookies de sessão são <em>HttpOnly</em> e <em>SameSite</em>;
                há limite de tentativas de login e cada conta só acessa os próprios dados. Mantenha sua senha em sigilo e use o sistema por conexão segura (HTTPS).
                Nenhum sistema é 100% imune; em caso de incidente com risco relevante, comunicaremos os titulares afetados e a ANPD, conforme a LGPD (art. 48).
            </p>

            <h2 id="direitos">9. Seus direitos (LGPD, art. 18)</h2>
            <p>Você pode, a qualquer momento: confirmar se tratamos seus dados; acessá-los; corrigi-los; pedir anonimização, bloqueio ou eliminação; pedir <strong>portabilidade</strong>; saber com quem compartilhamos; <strong>revogar consentimentos</strong>; e se opor a um tratamento. Você também pode reclamar à <a href="https://www.gov.br/anpd" target="_blank" rel="noopener">Autoridade Nacional de Proteção de Dados (ANPD)</a>.</p>
            <p>
                <strong>Como exercer:</strong> logado(a) como professor(a), acesse <a href="/meus-dados">Meus dados</a> para <strong>baixar uma cópia</strong> (arquivo JSON) e para <strong>excluir sua conta</strong>.
                Para os demais pedidos, e para alunos e responsáveis, use o contato da seção 1. Respondemos em até 15 dias.
            </p>

            <h2 id="ia">10. Inteligência artificial</h2>
            <p>
                O sistema usa IA (Claude, da Anthropic) para gerar rascunhos de conteúdo, planos, atividades, questões e relatórios.
                O que você preenche nos formulários de geração é enviado ao serviço de IA <strong>apenas para produzir a resposta</strong>.
                No <em>Relatório individual da creche</em>, o nome da criança e as observações digitadas são enviados à IA para redigir o texto;
                por isso, <strong>use somente o necessário</strong> e prefira o primeiro nome ou iniciais quando possível.
                O conteúdo gerado é sempre um <strong>rascunho para revisão do professor</strong>; não tomamos decisões automatizadas com efeitos jurídicos sobre pessoas.
            </p>

            <h2 id="cookies">11. Cookies</h2>
            <p>Usamos somente cookies essenciais. Detalhes na <a href="/cookies">Política de Cookies</a>.</p>

            <h2 id="alteracoes">12. Alterações e contato</h2>
            <p>
                Podemos atualizar esta política. A versão vigente e a data aparecem no topo; mudanças relevantes serão avisadas no sistema.
                <?php if ($contato): ?>Dúvidas: <a href="mailto:<?= htmlspecialchars($contato) ?>"><?= htmlspecialchars($contato) ?></a>.<?php endif; ?>
            </p>
<?php require __DIR__ . '/_rodape.php'; ?>
