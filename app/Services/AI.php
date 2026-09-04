<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\AiGeracao;
use App\Models\CrechePacoteItem;
use Throwable;

/**
 * Camada de integracao com o assistente de IA (Claude / Anthropic).
 *
 * Isola o resto do sistema do provedor: controllers e services chamam os
 * metodos de dominio (ex: gerarConteudoAula) e nunca falam com o SDK direto.
 *
 * Toda geracao e registrada em ai_geracoes (tokens + custo estimado) para
 * controle de custo e auditoria. Falha de rede/API vira AIException com
 * mensagem amigavel, sem quebrar a aplicacao.
 *
 * Regra do produto: o texto devolvido e sempre um RASCUNHO. Quem aprova e o
 * professor (human-in-the-loop).
 */
class AI
{
    private Client $client;
    private string $model;
    private int $maxTokens;
    private bool $cacheLigado;

    /** Caixa total de IA do sistema, em USD. 0 = sem limite. */
    private float $caixaUsd;

    /** Teto adicional por professor, em USD. 0 = desligado. */
    private float $tetoUsd;

    /**
     * Tipos de geracao que reaproveitam uma resposta ja gerada.
     *
     * So entram aqui os pedidos em que repetir significa "quero a MESMA coisa":
     * o conteudo e o plano de aula de um tema. Ficam de fora questoes,
     * atividades, pacotes e cronograma, em que clicar de novo significa
     * "quero itens DIFERENTES" -- cachear esses criaria duplicatas no banco.
     */
    private const TIPOS_COM_CACHE = ['conteudo', 'plano_aula'];

    /**
     * Precos por 1M de tokens (USD), por prefixo de modelo. Usado so para
     * estimar o custo gravado em ai_geracoes. Ajuste se trocar de modelo.
     */
    private const PRECOS = [
        // Ordem importa: o prefixo mais longo primeiro, senao 'claude-fable-5'
        // casaria tambem com 'claude-fable-5-1'.
        'claude-fable-5-1'  => ['in' => 10.00, 'out' => 50.00],
        'claude-fable-5'    => ['in' => 10.00, 'out' => 50.00],
        'claude-opus-5'     => ['in' => 5.00,  'out' => 25.00],
        'claude-opus-4-8'   => ['in' => 5.00,  'out' => 25.00],
        'claude-opus-4-7'   => ['in' => 5.00,  'out' => 25.00],
        'claude-opus-4-6'   => ['in' => 5.00,  'out' => 25.00],
        'claude-sonnet-5'   => ['in' => 2.00,  'out' => 10.00],
        'claude-sonnet-4-6' => ['in' => 3.00,  'out' => 15.00],
        'claude-haiku-4-5'  => ['in' => 1.00,  'out' => 5.00],
    ];

    public function __construct(?Client $client = null)
    {
        $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';

        if ($client === null && $apiKey === '') {
            throw new AIException(
                'Assistente de IA nao configurado: defina ANTHROPIC_API_KEY no .env.'
            );
        }

        $this->client = $client ?? new Client(apiKey: $apiKey);
        $this->model = $_ENV['ANTHROPIC_MODEL'] ?? 'claude-haiku-4-5-20251001';
        $this->maxTokens = (int) ($_ENV['AI_MAX_TOKENS'] ?? 4096);
        $this->cacheLigado = filter_var($_ENV['AI_CACHE'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $this->caixaUsd = max(0, (float) ($_ENV['AI_TETO_TOTAL_USD'] ?? 0));
        $this->tetoUsd = max(0, (float) ($_ENV['AI_TETO_USD'] ?? 0));
    }

    /* ---------------------------------------------------------------------
     |  Metodos de dominio (o que os controllers chamam)
     * -------------------------------------------------------------------*/

    /**
     * Gera o rascunho do conteudo de uma aula, alinhado a BNCC.
     * Devolve o texto (markdown). Registra tokens/custo em ai_geracoes.
     */
    /**
     * @param string[] $jaAbordado Resumo do que os materiais existentes deste
     *                             tema ja cobrem. Quando vem preenchido, pede
     *                             um material NOVO e complementar -- e, como o
     *                             prompt muda, o cache nao devolve o anterior.
     */
    public function gerarConteudoAula(
        string $tema,
        ?string $habilidadeBncc = null,
        string $etapa = 'EF',
        ?int $userId = null,
        array $jaAbordado = []
    ): string {
        $etapaNome = $etapa === 'EM' ? 'Ensino Medio' : 'Ensino Fundamental';
        $bncc = $habilidadeBncc
            ? "Alinhe explicitamente a habilidade da BNCC de codigo {$habilidadeBncc}."
            : 'Alinhe as habilidades da BNCC pertinentes ao tema.';

        $system = 'Voce e um assistente pedagogico para professores da educacao basica '
            . 'no Brasil. Produz conteudo didatico claro, correto e alinhado a BNCC. '
            . 'Escreve em portugues do Brasil, em markdown, com titulos, objetivos de '
            . 'aprendizagem, desenvolvimento do conteudo e uma sugestao de atividade. '
            . 'O material e um rascunho que o professor vai revisar e ajustar.';

        $prompt = "Prepare o conteudo de uma aula para {$etapaNome}.\n"
            . "Tema: {$tema}\n"
            . "{$bncc}"
            . $this->pedidoDeMaterialNovo($jaAbordado);

        return $this->gerar('conteudo', $system, $prompt, $userId);
    }

    /**
     * Gera o rascunho de um plano de aula estruturado, alinhado a BNCC e aos
     * objetivos de aprendizagem. Devolve markdown.
     */
    /**
     * @param string[] $jaAbordado Resumo dos planos que ja existem para este
     *                             tema e esta duracao (ver gerarConteudoAula).
     */
    public function gerarPlanoAula(
        string $tema,
        ?string $objetivos = null,
        ?string $habilidadeBncc = null,
        string $etapa = 'EF',
        string $duracao = '1 aula de 50 min',
        ?int $userId = null,
        array $jaAbordado = []
    ): string {
        $etapaNome = $etapa === 'EM' ? 'Ensino Medio' : 'Ensino Fundamental';
        $bncc = $habilidadeBncc
            ? "Habilidades BNCC a contemplar: {$habilidadeBncc}."
            : 'Contemple as habilidades da BNCC pertinentes ao tema.';
        $objetivosLinha = $objetivos
            ? "Objetivos de aprendizagem informados pelo professor: {$objetivos}."
            : 'Defina objetivos de aprendizagem adequados ao tema e a etapa.';

        $system = 'Voce e um assistente pedagogico para professores da educacao basica '
            . 'no Brasil. Elabora planos de aula praticos, corretos e alinhados a BNCC. '
            . 'Escreve em portugues do Brasil, em markdown. O plano e um rascunho que o '
            . 'professor vai revisar e ajustar.';

        $prompt = "Elabore um plano de aula para {$etapaNome}.\n"
            . "Tema: {$tema}\n"
            . "Duracao: {$duracao}\n"
            . "{$objetivosLinha}\n"
            . "{$bncc}\n"
            . 'Estruture com as secoes: Identificacao (tema, etapa, duracao), '
            . 'Objetivos de aprendizagem, Habilidades BNCC, Conhecimentos previos, '
            . 'Metodologia (dividida em Introducao, Desenvolvimento e Fechamento, com o '
            . 'tempo estimado de cada etapa), Recursos didaticos, Avaliacao e Referencias.'
            . $this->pedidoDeMaterialNovo($jaAbordado, 'plano de aula');

        return $this->gerar('plano_aula', $system, $prompt, $userId);
    }

    /**
     * Sugere atividades praticas alinhadas ao tema e ao nivel. Devolve array:
     *   ['titulo','descricao','formato','duracao']
     */
    public function sugerirAtividades(
        string $tema,
        ?string $objetivos = null,
        ?string $habilidadeBncc = null,
        string $etapa = 'EF',
        int $quantidade = 4,
        string $foco = '',
        ?int $userId = null
    ): array {
        $quantidade = max(1, min($quantidade, 10));
        $etapaNome = $etapa === 'EM' ? 'Ensino Medio' : 'Ensino Fundamental';
        $bncc = $habilidadeBncc ? "Considere a habilidade BNCC {$habilidadeBncc}." : '';
        $obj = $objetivos ? "Objetivos: {$objetivos}." : '';
        $focoLinha = trim($foco) !== '' ? "Foco pedido pelo professor: {$foco}." : '';

        $system = 'Voce e um assistente pedagogico para professores da educacao basica no '
            . 'Brasil. Sugere atividades praticas, viaveis em sala e adequadas ao nivel dos '
            . 'alunos, alinhadas a BNCC. Responde SEMPRE com JSON valido, sem texto fora do '
            . 'JSON e sem blocos de codigo markdown.';

        $prompt = "Sugira {$quantidade} atividades para {$etapaNome} sobre o tema \"{$tema}\".\n"
            . trim("{$obj} {$bncc} {$focoLinha}") . "\n"
            . 'Responda um array JSON. Cada item deve ter: "titulo", "descricao" (passo a '
            . 'passo curto de como aplicar), "formato" (individual|grupo|discussao|pratica|'
            . 'projeto|jogo) e "duracao" (ex: "20 min").';

        $texto = $this->gerar('atividade', $system, $prompt, $userId);

        $dados = $this->decodeLista($texto);
        if (!is_array($dados)) {
            throw new AIException('A IA nao retornou as atividades em formato valido. Tente novamente.');
        }

        return $this->normalizarAtividades($dados);
    }

    /**
     * Sugere atividades ludicas de Educacao Infantil por faixa etaria e campo
     * de experiencia (BNCC EI). Devolve array:
     *   ['titulo','descricao','materiais','duracao']
     */
    public function sugerirAtividadesLudicas(
        string $faixaEtaria,
        string $campoExperiencia = '',
        string $tema = '',
        int $quantidade = 4,
        ?int $userId = null
    ): array {
        $quantidade = max(1, min($quantidade, 10));
        $campo = trim($campoExperiencia) !== ''
            ? "Campo de experiencia da BNCC: {$campoExperiencia}." : '';
        $temaLinha = trim($tema) !== '' ? "Tema/projeto: {$tema}." : '';

        $system = 'Voce e um assistente pedagogico de Educacao Infantil (creche/pre-escola) '
            . 'no Brasil. Sugere atividades ludicas SEGURAS, adequadas a faixa etaria e '
            . 'alinhadas aos campos de experiencia da BNCC. Prioriza o brincar, o concreto e '
            . 'a interacao. Responde SEMPRE com JSON valido, sem texto fora do JSON e sem '
            . 'blocos de codigo markdown.';

        $prompt = "Sugira {$quantidade} atividades ludicas para a faixa \"{$faixaEtaria}\".\n"
            . trim("{$campo} {$temaLinha}") . "\n"
            . 'Responda um array JSON. Cada item deve ter: "titulo", "descricao" (passo a '
            . 'passo de como conduzir a brincadeira), "materiais" (lista simples de '
            . 'materiais) e "duracao" (ex: "15 min"). Garanta que sejam apropriadas e '
            . 'seguras para a faixa etaria informada.';

        $texto = $this->gerar('creche_atividade', $system, $prompt, $userId);

        $dados = $this->decodeLista($texto);
        if (!is_array($dados)) {
            throw new AIException('A IA nao retornou as atividades em formato valido. Tente novamente.');
        }

        return $this->normalizarAtividadesLudicas($dados);
    }

    /**
     * Monta um cronograma coerente de {dias} atividades ludicas para uma semana
     * (progressao de segunda a sexta). Devolve array na ordem dos dias:
     *   ['titulo','descricao','materiais','duracao']
     */
    public function gerarCronogramaSemanal(
        string $faixaEtaria,
        string $campoExperiencia = '',
        string $tema = '',
        int $dias = 5,
        ?int $userId = null
    ): array {
        $dias = max(1, min($dias, 7));
        $campo = trim($campoExperiencia) !== '' ? "Campo de experiencia da BNCC: {$campoExperiencia}." : '';
        $temaLinha = trim($tema) !== '' ? "Tema/projeto da semana: {$tema}." : '';

        $system = 'Voce e um assistente pedagogico de Educacao Infantil no Brasil. Monta '
            . 'cronogramas semanais de atividades ludicas SEGURAS e adequadas a faixa etaria, '
            . 'com progressao e variedade ao longo da semana, alinhadas aos campos de '
            . 'experiencia da BNCC. Responde SEMPRE com JSON valido, sem texto fora do JSON e '
            . 'sem blocos de codigo markdown.';

        $prompt = "Monte um cronograma com {$dias} atividades ludicas (uma por dia, de segunda "
            . "a sexta) para a faixa \"{$faixaEtaria}\".\n"
            . trim("{$campo} {$temaLinha}") . "\n"
            . 'As atividades devem ter variedade e uma progressao coerente ao longo da semana. '
            . 'Responda um array JSON com exatamente ' . $dias . ' itens, na ordem dos dias. '
            . 'Cada item deve ter: "titulo", "descricao" (passo a passo), "materiais" e '
            . '"duracao" (ex: "20 min").';

        $texto = $this->gerar('creche_cronograma', $system, $prompt, $userId);

        $dados = $this->decodeLista($texto);
        if (!is_array($dados)) {
            throw new AIException('A IA nao retornou o cronograma em formato valido. Tente novamente.');
        }

        return $this->normalizarAtividadesLudicas($dados);
    }

    /**
     * Monta um pacote de FOLHAS de atividades IMPRIMIVEIS para Educacao Infantil,
     * em dois formatos claros: 'escrever' (figura -> quadro) e 'circular'
     * (linha de opcoes -> circular a certa). Devolve array de atividades:
     *   ['titulo','tipo','formato','instrucao','itens'=>[...]]
     */
    public function gerarPacoteAtividades(
        string $tema,
        string $faixaEtaria,
        int $quantidade = 6,
        ?int $userId = null
    ): array {
        $quantidade = max(3, min($quantidade, 10));

        $system = 'Voce e um especialista em Educacao Infantil no Brasil que cria FOLHAS de '
            . 'atividades imprimiveis de alta qualidade, no estilo de apostilas profissionais. '
            . 'As instrucoes sao curtas, claras e diretas para a crianca. Usa emojis simples e '
            . 'reconheciveis como figuras. Responde SEMPRE com JSON valido, sem texto fora do '
            . 'JSON e sem blocos de codigo markdown.';

        $prompt = "Crie {$quantidade} atividades imprimiveis de OTIMA QUALIDADE sobre o tema "
            . "\"{$tema}\", para a faixa \"{$faixaEtaria}\".\n\n"
            . "Cada atividade usa UM destes cinco formatos (varie bastante entre eles):\n\n"
            . "1) formato \"escrever\": a crianca observa uma FIGURA e escreve a resposta num "
            . "quadro. Bom para: escrever a letra inicial; contar e escrever o numero (repita o "
            . "emoji, ex \"🐶🐶🐶\"); escrever quantas silabas; escrever o numero que vem depois. "
            . "itens = lista de {\"figura\": \"emoji(s)\", \"rotulo\": \"nome\", \"resposta\": "
            . "\"letra/numero esperado\"}.\n\n"
            . "2) formato \"circular\": cada linha tem VARIAS opcoes (emojis) e a crianca circula "
            . "a correta. Bom para: circular o diferente; circular o maior/menor; circular o que "
            . "comeca com a letra X; circular o que pertence ao grupo. itens = lista de "
            . "{\"rotulo\": \"Linha 1\", \"opcoes\": [\"emoji\",\"emoji\",\"emoji\"], \"correta\": "
            . "numero da posicao correta comecando em 1}.\n\n"
            . "3) formato \"ligar\": duas colunas; a crianca LIGA cada figura da esquerda ao seu "
            . "par na direita. Bom para: ligar o animal ao seu filhote; ao seu alimento; ao seu "
            . "habitat; a sua sombra; a quantidade (numero) certa; a letra inicial. itens = lista "
            . "de PARES {\"esquerda\": \"emoji\", \"esq_rotulo\": \"nome\", \"direita\": \"emoji ou "
            . "texto curto\", \"dir_rotulo\": \"\"}, onde esquerda casa com direita.\n\n"
            . "4) formato \"pintar\": a crianca PINTA/colore figuras. Bom para: pintar apenas os "
            . "de uma categoria; pintar a quantidade pedida; pintar da cor certa. itens = lista de "
            . "{\"figura\": \"emoji\", \"rotulo\": \"nome\", \"pintar\": true se essa figura deve "
            . "ser pintada (serve de gabarito)}.\n\n"
            . "5) formato \"sequencia\": completar uma sequencia ou padrao. A crianca olha a "
            . "sequencia e descobre o que vem DEPOIS. Bom para: padroes (AB AB A_), sequencia "
            . "numerica, sequencia de tamanhos. itens = lista de {\"rotulo\": \"Linha 1\", "
            . "\"sequencia\": [\"emoji\",\"emoji\",\"emoji\"], \"resposta\": \"emoji/valor que vem "
            . "depois\"}.\n\n"
            . "REGRAS DE QUALIDADE:\n"
            . "- A instrucao deve deixar CLARO o que fazer (ex: \"Ligue cada animal ao seu "
            . "filhote.\").\n"
            . "- Use figuras coerentes com o tema e faceis de reconhecer.\n"
            . "- 'escrever': 4 a 6 itens. 'circular': 3 a 5 linhas com 3 ou 4 opcoes. 'ligar': 3 a "
            . "5 pares. 'pintar': 4 a 8 figuras. 'sequencia': 3 a 5 linhas, cada uma com 3 a 5 "
            . "elementos.\n"
            . "- Cada atividade tem um proposito unico e simples, adequado a idade.\n\n"
            . 'Responda um array JSON. Cada atividade: {"titulo": "titulo curto", "tipo": '
            . '"categoria (ex: Letra inicial)", "formato": "escrever", "circular", "ligar", '
            . '"pintar" ou "sequencia", "instrucao": "instrucao clara", "itens": [...conforme o '
            . 'formato...]}.';

        $texto = $this->gerar('creche_pacote', $system, $prompt, $userId);

        $dados = $this->decodeLista($texto);
        if (empty($dados)) {
            throw new AIException('A IA nao retornou o pacote em formato valido. Tente novamente.');
        }

        return $this->normalizarPacoteWorksheets($dados);
    }

    /**
     * Gera um conjunto de questoes (rascunhos) alinhadas a BNCC.
     * Devolve um array normalizado; cada item:
     *   ['tipo','enunciado','dificuldade','habilidade_bncc','tags','alternativas'=>[['texto','correta'],...]]
     *
     * @param string $tipo multipla_escolha | verdadeiro_falso | dissertativa | mista
     */
    public function gerarQuestoes(
        string $tema,
        ?string $habilidadeBncc = null,
        string $dificuldade = 'media',
        int $quantidade = 5,
        string $tipo = 'multipla_escolha',
        ?int $userId = null
    ): array {
        $quantidade = max(1, min($quantidade, 15));
        $dificuldade = in_array($dificuldade, ['facil', 'media', 'dificil'], true) ? $dificuldade : 'media';
        $tipo = in_array($tipo, ['multipla_escolha', 'verdadeiro_falso', 'dissertativa', 'mista'], true)
            ? $tipo : 'multipla_escolha';

        $bncc = $habilidadeBncc
            ? "Alinhe as questoes a habilidade da BNCC {$habilidadeBncc}."
            : 'Alinhe as questoes as habilidades da BNCC pertinentes ao tema.';

        $descTipo = match ($tipo) {
            'verdadeiro_falso' => 'todas de verdadeiro ou falso (2 alternativas: "Verdadeiro" e "Falso", exatamente 1 correta)',
            'dissertativa' => 'todas dissertativas (sem alternativas)',
            'mista' => 'variando entre multipla escolha, verdadeiro/falso e dissertativa',
            default => 'todas de multipla escolha (4 alternativas, exatamente 1 correta)',
        };

        $system = 'Voce e um assistente de avaliacao para professores da educacao basica no Brasil. '
            . 'Gera questoes corretas, claras e alinhadas a BNCC. Responde SEMPRE com JSON valido, '
            . 'sem nenhum texto fora do JSON e sem blocos de codigo markdown.';

        $prompt = "Gere {$quantidade} questoes sobre o tema \"{$tema}\", de nivel {$dificuldade}, {$descTipo}.\n"
            . "{$bncc}\n"
            . 'Responda um array JSON. Cada item deve ter as chaves: '
            . '"tipo" (multipla_escolha|verdadeiro_falso|dissertativa), "enunciado", '
            . '"dificuldade" (facil|media|dificil), "habilidade_bncc" (codigo ou null), '
            . '"alternativas" (lista de {"texto","correta":true|false}). '
            . 'Questoes dissertativas usam "alternativas": [].';

        $texto = $this->gerar('questao', $system, $prompt, $userId);

        $dados = $this->decodeLista($texto);
        if (!is_array($dados)) {
            throw new AIException('A IA nao retornou as questoes em formato valido. Tente novamente.');
        }

        return $this->normalizarQuestoes($dados);
    }

    /* ---------------------------------------------------------------------
     |  Motor generico de geracao
     * -------------------------------------------------------------------*/

    /**
     * Faz uma chamada de texto ao modelo, registra o uso e devolve a resposta.
     *
     * @param string $tipo   rotulo do tipo de geracao (ex: 'conteudo', 'questao')
     * @throws AIException   em qualquer falha de comunicacao com a API
     */
    public function gerar(
        string $tipo,
        string $system,
        string $prompt,
        ?int $userId = null,
        bool $usarCache = true
    ): string {
        // maxTokens entra no hash porque muda o tamanho da resposta: com outro
        // teto de tokens o pedido nao e mais "o mesmo pedido".
        $promptHash = hash('sha256', $this->model . '|' . $this->maxTokens . '|' . $system . '|' . $prompt);

        if ($usarCache && $this->podeUsarCache($tipo)) {
            $emCache = (new AiGeracao())->respostaEmCache($promptHash, $userId);

            if ($emCache !== null) {
                // Registra o reaproveitamento com custo zero: a auditoria de uso
                // continua completa e a economia fica visivel em ai_geracoes.
                $this->registrarGeracao($userId, $tipo, $promptHash, null, 0, 0, 'cache', null);

                Logger::info('IA: resposta reaproveitada do cache', [
                    'tipo' => $tipo,
                    'modelo' => $this->model,
                    'user_id' => $userId,
                ]);

                return $emCache;
            }
        }

        // O teto e checado DEPOIS do cache: reaproveitar uma resposta ja paga
        // nao custa nada, entao nao faz sentido barrar quem so repete um pedido.
        $this->exigirDentroDoTeto($userId);

        try {
            $message = $this->client->messages->create(
                model: $this->model,
                maxTokens: $this->maxTokens,
                system: $system,
                messages: [
                    ['role' => 'user', 'content' => $prompt],
                ],
            );
        } catch (Throwable $e) {
            Logger::exception($e);
            $this->registrarGeracao($userId, $tipo, $promptHash, null, null, null, 'erro', $e->getMessage());

            throw new AIException(
                'Nao foi possivel gerar o conteudo agora. Tente novamente em instantes.',
                0,
                $e
            );
        }

        $texto = $this->extrairTexto($message);
        $tokensIn = $message->usage->inputTokens ?? 0;
        $tokensOut = $message->usage->outputTokens ?? 0;

        $geracaoId = $this->registrarGeracao(
            $userId,
            $tipo,
            $promptHash,
            // So guarda o texto dos tipos que reaproveitam: nao incha a tabela
            // com respostas que nunca serao lidas de volta.
            $this->podeUsarCache($tipo) ? $texto : null,
            $tokensIn,
            $tokensOut,
            'ok',
            null
        );

        Logger::activity('ai.gerou', [
            'entity_type' => 'ai_geracao',
            'entity_id' => $geracaoId,
            'description' => "IA gerou conteudo do tipo {$tipo}",
            'properties' => ['modelo' => $this->model, 'tokens_out' => $tokensOut],
            'user_id' => $userId,
        ]);

        return $texto;
    }

    /* ---------------------------------------------------------------------
     |  Internos
     * -------------------------------------------------------------------*/

    /**
     * Concatena os blocos de texto da resposta (ignora blocos de outro tipo).
     */
    private function extrairTexto(object $message): string
    {
        $partes = [];
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $partes[] = $block->text;
            }
        }

        return trim(implode('', $partes));
    }

    /**
     * Decodifica a lista de itens de uma resposta da IA, de forma robusta.
     * Aceita: (a) array JSON puro; (b) objeto que embrulha a lista numa chave
     * (ex: {"atividades": [...]}); tolera cercas ```json. Devolve [] se nada.
     */
    private function decodeLista(string $texto): array
    {
        $t = trim($texto);
        $t = preg_replace('/^```(?:json)?\s*/i', '', $t);
        $t = trim((string) preg_replace('/\s*```$/', '', (string) $t));

        $decoded = json_decode($t, true);

        // Ja veio como lista de itens.
        if (is_array($decoded) && array_is_list($decoded)) {
            return $decoded;
        }
        // Veio como objeto: usa a primeira propriedade que seja uma lista.
        if (is_array($decoded)) {
            foreach ($decoded as $valor) {
                if (is_array($valor) && $valor !== [] && array_is_list($valor)) {
                    return $valor;
                }
            }
        }
        // Fallback: recorta do primeiro '[' ao ultimo ']'.
        $ini = strpos($t, '[');
        $fim = strrpos($t, ']');
        if ($ini !== false && $fim !== false && $fim > $ini) {
            $sub = json_decode(substr($t, $ini, $fim - $ini + 1), true);
            if (is_array($sub) && array_is_list($sub)) {
                return $sub;
            }
        }

        return [];
    }

    /**
     * Sanitiza as questoes vindas da IA (enums validos, alternativas limpas).
     */
    private function normalizarQuestoes(array $dados): array
    {
        $tiposValidos = ['multipla_escolha', 'verdadeiro_falso', 'dissertativa'];
        $out = [];

        foreach ($dados as $q) {
            if (!is_array($q) || empty($q['enunciado'])) {
                continue;
            }

            $tipo = in_array($q['tipo'] ?? '', $tiposValidos, true) ? $q['tipo'] : 'multipla_escolha';
            $dif = in_array($q['dificuldade'] ?? '', ['facil', 'media', 'dificil'], true)
                ? $q['dificuldade'] : 'media';

            $alternativas = [];
            if ($tipo !== 'dissertativa' && !empty($q['alternativas']) && is_array($q['alternativas'])) {
                foreach ($q['alternativas'] as $a) {
                    if (!is_array($a) || ($a['texto'] ?? '') === '') {
                        continue;
                    }
                    $alternativas[] = [
                        'texto' => (string) $a['texto'],
                        'correta' => !empty($a['correta']),
                    ];
                }
            }

            $out[] = [
                'tipo' => $tipo,
                'enunciado' => (string) $q['enunciado'],
                'dificuldade' => $dif,
                'habilidade_bncc' => !empty($q['habilidade_bncc'])
                    ? mb_substr((string) $q['habilidade_bncc'], 0, 20) : null,
                'tags' => null,
                'alternativas' => $alternativas,
            ];
        }

        if (empty($out)) {
            throw new AIException('A IA nao retornou nenhuma questao aproveitavel. Tente novamente.');
        }

        return $out;
    }

    /** Sanitiza as atividades vindas da IA. */
    private function normalizarAtividades(array $dados): array
    {
        $formatos = ['individual', 'grupo', 'discussao', 'pratica', 'projeto', 'jogo'];
        $out = [];

        foreach ($dados as $a) {
            if (!is_array($a) || empty($a['titulo'])) {
                continue;
            }
            $formato = in_array($a['formato'] ?? '', $formatos, true) ? $a['formato'] : null;

            $out[] = [
                'titulo' => (string) $a['titulo'],
                'descricao' => isset($a['descricao']) ? (string) $a['descricao'] : null,
                'formato' => $formato,
                'duracao' => !empty($a['duracao']) ? mb_substr((string) $a['duracao'], 0, 60) : null,
            ];
        }

        if (empty($out)) {
            throw new AIException('A IA nao retornou nenhuma atividade aproveitavel. Tente novamente.');
        }

        return $out;
    }

    /** Sanitiza as atividades ludicas vindas da IA. */
    private function normalizarAtividadesLudicas(array $dados): array
    {
        $out = [];
        foreach ($dados as $a) {
            if (!is_array($a) || empty($a['titulo'])) {
                continue;
            }
            $out[] = [
                'titulo' => (string) $a['titulo'],
                'descricao' => isset($a['descricao']) ? (string) $a['descricao'] : null,
                'materiais' => isset($a['materiais'])
                    ? (is_array($a['materiais']) ? implode(', ', $a['materiais']) : (string) $a['materiais'])
                    : null,
                'duracao' => !empty($a['duracao']) ? mb_substr((string) $a['duracao'], 0, 60) : null,
            ];
        }

        if (empty($out)) {
            throw new AIException('A IA nao retornou nenhuma atividade aproveitavel. Tente novamente.');
        }

        return $out;
    }

    /** Sanitiza as folhas imprimiveis de um pacote (2 formatos). */
    private function normalizarPacoteWorksheets(array $dados): array
    {
        $out = [];
        foreach ($dados as $a) {
            if (!is_array($a) || empty($a['titulo'])) {
                continue;
            }

            $formato = CrechePacoteItem::normalizarFormato($a['formato'] ?? null);
            $itens = [];

            if (!empty($a['itens']) && is_array($a['itens'])) {
                foreach ($a['itens'] as $it) {
                    if (!is_array($it)) {
                        continue;
                    }

                    if ($formato === 'sequencia') {
                        $seq = [];
                        foreach ((array) ($it['sequencia'] ?? []) as $s) {
                            $s = trim((string) $s);
                            if ($s !== '') {
                                $seq[] = mb_substr($s, 0, 16);
                            }
                        }
                        if (empty($seq)) {
                            continue;
                        }
                        $itens[] = [
                            'rotulo' => mb_substr(trim((string) ($it['rotulo'] ?? '')), 0, 40),
                            'sequencia' => $seq,
                            'resposta' => mb_substr(trim((string) ($it['resposta'] ?? '')), 0, 16),
                        ];
                    } elseif ($formato === 'pintar') {
                        $figura = trim((string) ($it['figura'] ?? ''));
                        $rotulo = trim((string) ($it['rotulo'] ?? ''));
                        if ($figura === '' && $rotulo === '') {
                            continue;
                        }
                        $itens[] = [
                            'figura' => mb_substr($figura, 0, 16),
                            'rotulo' => mb_substr($rotulo, 0, 40),
                            'pintar' => !empty($it['pintar']),
                        ];
                    } elseif ($formato === 'ligar') {
                        $esq = trim((string) ($it['esquerda'] ?? ''));
                        $dir = trim((string) ($it['direita'] ?? ''));
                        if ($esq === '' || $dir === '') {
                            continue;
                        }
                        $itens[] = [
                            'esquerda' => mb_substr($esq, 0, 16),
                            'esq_rotulo' => mb_substr(trim((string) ($it['esq_rotulo'] ?? '')), 0, 40),
                            'direita' => mb_substr($dir, 0, 40),
                            'dir_rotulo' => mb_substr(trim((string) ($it['dir_rotulo'] ?? '')), 0, 40),
                        ];
                    } elseif ($formato === 'circular') {
                        $opcoes = [];
                        foreach ((array) ($it['opcoes'] ?? []) as $op) {
                            $op = trim((string) $op);
                            if ($op !== '') {
                                $opcoes[] = mb_substr($op, 0, 16);
                            }
                        }
                        if (count($opcoes) < 2) {
                            continue;
                        }
                        $correta = (int) ($it['correta'] ?? 1);
                        $correta = max(1, min($correta, count($opcoes)));
                        $itens[] = [
                            'rotulo' => mb_substr(trim((string) ($it['rotulo'] ?? '')), 0, 40),
                            'opcoes' => $opcoes,
                            'correta' => $correta,
                        ];
                    } else {
                        $figura = trim((string) ($it['figura'] ?? ''));
                        $rotulo = trim((string) ($it['rotulo'] ?? ''));
                        if ($figura === '' && $rotulo === '') {
                            continue;
                        }
                        $itens[] = [
                            'figura' => mb_substr($figura, 0, 16),
                            'rotulo' => mb_substr($rotulo, 0, 40),
                            'resposta' => mb_substr(trim((string) ($it['resposta'] ?? '')), 0, 40),
                        ];
                    }
                }
            }

            if (empty($itens)) {
                continue;
            }

            $out[] = [
                'titulo' => mb_substr((string) $a['titulo'], 0, 200),
                'tipo' => !empty($a['tipo']) ? mb_substr((string) $a['tipo'], 0, 60) : null,
                'formato' => $formato,
                'instrucao' => !empty($a['instrucao']) ? (string) $a['instrucao'] : null,
                'itens' => $itens,
            ];
        }

        if (empty($out)) {
            throw new AIException('A IA nao retornou nenhuma atividade aproveitavel. Tente novamente.');
        }

        return $out;
    }

    /**
     * Resume registros ja gerados (conteudos, planos...) pelos seus titulos de
     * secao em markdown. E compacto -- gasta poucos tokens -- e diz a IA qual
     * recorte do tema ja foi coberto, para ela nao repetir.
     *
     * @param array<int, array<string, mixed>> $registros linhas do banco
     * @return string[]
     */
    public static function resumirMateriais(array $registros, string $campo = 'corpo', int $max = 5): array
    {
        $resumos = [];

        foreach (array_slice($registros, 0, $max) as $registro) {
            $titulos = [];

            foreach (preg_split('/\r?\n/', (string) ($registro[$campo] ?? '')) as $linha) {
                if (preg_match('/^#{1,3}\s+(.+?)\s*$/', trim($linha), $m)) {
                    $titulos[] = $m[1];
                }
            }

            if ($titulos !== []) {
                $resumos[] = mb_substr(implode(' / ', array_slice($titulos, 0, 8)), 0, 300);
            }
        }

        return $resumos;
    }

    /**
     * Trecho do prompt que pede um material DIFERENTE dos que ja existem.
     *
     * Serve a dois propositos ao mesmo tempo: instrui a IA a nao repetir o que
     * o professor ja tem e, por mudar o prompt, faz o pedido ter outro hash --
     * entao "criar mais" gera de verdade, enquanto repetir o MESMO pedido
     * continua saindo do cache de graca.
     *
     * @param string[] $jaAbordado
     */
    private function pedidoDeMaterialNovo(array $jaAbordado, string $oQue = 'material'): string
    {
        if ($jaAbordado === []) {
            return '';
        }

        $lista = '';
        foreach ($jaAbordado as $i => $resumo) {
            $lista .= '  ' . ($i + 1) . '. ' . $resumo . "\n";
        }

        return "\n\nATENCAO: o professor JA possui " . count($jaAbordado) . ' ' . $oQue
            . "(is) sobre este mesmo tema, cobrindo:\n" . $lista
            . "Produza um {$oQue} NOVO e COMPLEMENTAR aos que ja existem: escolha outro "
            . "recorte do tema, use outros exemplos e contextos, e proponha outra atividade. "
            . "Nao repita os titulos de secao, os exemplos nem a abordagem acima.";
    }

    /**
     * Barra a geracao quando o dinheiro acabou (RF-20).
     *
     * Sao dois limites, e vale o primeiro que estourar:
     *
     * 1. CAIXA (AI_TETO_TOTAL_USD) -- o total do sistema, somando todas as
     *    contas. E o unico que limita a fatura de verdade: existe uma chave
     *    de API so, e todo mundo gasta dela. Com cadastro aberto, sem o
     *    caixa o gasto maximo cresce a cada pessoa que se cadastra.
     *
     * 2. TETO POR CONTA (AI_TETO_USD) -- opcional, desligado por padrao.
     *    Serve para impedir que uma pessoa sozinha esvazie o caixa comum.
     *
     * Qualquer um dos dois em 0 fica desligado.
     */
    private function exigirDentroDoTeto(?int $userId): void
    {
        $registro = new AiGeracao();

        if ($this->caixaUsd > 0) {
            $gastoTotal = $registro->custoTotal();

            if ($gastoTotal >= $this->caixaUsd) {
                Logger::warning('Geracao bloqueada: caixa de IA esgotado', [
                    'user_id' => $userId,
                    'gasto_total_usd' => $gastoTotal,
                    'caixa_usd' => $this->caixaUsd,
                ]);

                throw new AIException(sprintf(
                    'O caixa de IA do sistema acabou (US$ %s de US$ %s). '
                    . 'Tudo que ja foi gerado continua disponivel. '
                    . 'Para liberar mais, o administrador aumenta o AI_TETO_TOTAL_USD no .env.',
                    number_format($gastoTotal, 2, ',', '.'),
                    number_format($this->caixaUsd, 2, ',', '.')
                ));
            }
        }

        if ($this->tetoUsd <= 0 || $userId === null) {
            return;
        }

        $gasto = $registro->custoTotalUsuario($userId);

        if ($gasto < $this->tetoUsd) {
            return;
        }

        Logger::warning('Geracao bloqueada: teto por conta atingido', [
            'user_id' => $userId,
            'gasto_usd' => $gasto,
            'teto_usd' => $this->tetoUsd,
        ]);

        throw new AIException(sprintf(
            'Voce atingiu o seu limite de gasto com IA (US$ %s de US$ %s). '
            . 'O conteudo ja gerado continua disponivel; fale com o administrador.',
            number_format($gasto, 2, ',', '.'),
            number_format($this->tetoUsd, 2, ',', '.')
        ));
    }

    /**
     * O cache vale para este tipo de geracao?
     */
    private function podeUsarCache(string $tipo): bool
    {
        return $this->cacheLigado && in_array($tipo, self::TIPOS_COM_CACHE, true);
    }

    private function registrarGeracao(
        ?int $userId,
        string $tipo,
        string $promptHash,
        ?string $resposta,
        ?int $tokensIn,
        ?int $tokensOut,
        string $status,
        ?string $erro
    ): int {
        try {
            return (new AiGeracao())->registrar([
                'user_id' => $userId,
                'tipo' => $tipo,
                'modelo' => $this->model,
                'prompt_hash' => $promptHash,
                'resposta' => $resposta,
                'tokens_in' => $tokensIn,
                'tokens_out' => $tokensOut,
                'custo_estimado' => $this->estimarCusto($tokensIn, $tokensOut),
                'status' => $status,
                'erro' => $erro !== null ? mb_substr($erro, 0, 255) : null,
            ]);
        } catch (Throwable $e) {
            // Log nunca pode quebrar a geracao.
            Logger::exception($e);
            return 0;
        }
    }

    /**
     * Estima o custo em USD a partir dos tokens e da tabela de precos.
     */
    private function estimarCusto(?int $tokensIn, ?int $tokensOut): ?float
    {
        if ($tokensIn === null && $tokensOut === null) {
            return null;
        }

        $precos = null;
        foreach (self::PRECOS as $prefixo => $valores) {
            if (str_starts_with($this->model, $prefixo)) {
                $precos = $valores;
                break;
            }
        }

        if ($precos === null) {
            return null; // modelo desconhecido: nao arrisca uma estimativa errada
        }

        $custo = ($tokensIn ?? 0) / 1_000_000 * $precos['in']
            + ($tokensOut ?? 0) / 1_000_000 * $precos['out'];

        return round($custo, 6);
    }
}
