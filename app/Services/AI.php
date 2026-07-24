<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\AiGeracao;
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

    /**
     * Precos por 1M de tokens (USD), por prefixo de modelo. Usado so para
     * estimar o custo gravado em ai_geracoes. Ajuste se trocar de modelo.
     */
    private const PRECOS = [
        'claude-haiku-4-5'  => ['in' => 1.00, 'out' => 5.00],
        'claude-sonnet-5'   => ['in' => 3.00, 'out' => 15.00],
        'claude-opus-4-8'   => ['in' => 5.00, 'out' => 25.00],
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
    }

    /* ---------------------------------------------------------------------
     |  Metodos de dominio (o que os controllers chamam)
     * -------------------------------------------------------------------*/

    /**
     * Gera o rascunho do conteudo de uma aula, alinhado a BNCC.
     * Devolve o texto (markdown). Registra tokens/custo em ai_geracoes.
     */
    public function gerarConteudoAula(
        string $tema,
        ?string $habilidadeBncc = null,
        string $etapa = 'EF',
        ?int $userId = null
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
            . "{$bncc}";

        return $this->gerar('conteudo', $system, $prompt, $userId);
    }

    /**
     * Gera o rascunho de um plano de aula estruturado, alinhado a BNCC e aos
     * objetivos de aprendizagem. Devolve markdown.
     */
    public function gerarPlanoAula(
        string $tema,
        ?string $objetivos = null,
        ?string $habilidadeBncc = null,
        string $etapa = 'EF',
        string $duracao = '1 aula de 50 min',
        ?int $userId = null
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
            . 'tempo estimado de cada etapa), Recursos didaticos, Avaliacao e Referencias.';

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

        $dados = json_decode($this->extrairJson($texto), true);
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

        $dados = json_decode($this->extrairJson($texto), true);
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

        $dados = json_decode($this->extrairJson($texto), true);
        if (!is_array($dados)) {
            throw new AIException('A IA nao retornou o cronograma em formato valido. Tente novamente.');
        }

        return $this->normalizarAtividadesLudicas($dados);
    }

    /**
     * Monta um pacote completo de atividades sobre um tema, com TIPOS VARIADOS
     * (memoria, pintura, musica, movimento, historia...). Devolve array:
     *   ['tipo','titulo','descricao','materiais','duracao']
     */
    public function gerarPacoteAtividades(
        string $tema,
        string $faixaEtaria,
        int $quantidade = 6,
        ?int $userId = null
    ): array {
        $quantidade = max(3, min($quantidade, 12));

        $system = 'Voce e um assistente pedagogico de Educacao Infantil no Brasil. Monta '
            . 'pacotes tematicos de atividades ludicas SEGURAS e adequadas a faixa etaria, '
            . 'variando os TIPOS de atividade. Responde SEMPRE com JSON valido, sem texto fora '
            . 'do JSON e sem blocos de codigo markdown.';

        $prompt = "Monte um pacote completo de {$quantidade} atividades sobre o tema \"{$tema}\" "
            . "para a faixa \"{$faixaEtaria}\".\n"
            . 'Varie os TIPOS de atividade ao longo do pacote, por exemplo: brincadeira de '
            . 'memoria, desenho para pintar/colorir, musica ou cantiga, atividade de movimento, '
            . 'historia/roda de conversa, artesanato/colagem, jogo sensorial. '
            . 'Responda um array JSON. Cada item deve ter: "tipo" (categoria curta da atividade, '
            . 'ex: "Brincadeira de memoria"), "titulo", "descricao" (passo a passo), "materiais" '
            . 'e "duracao" (ex: "15 min").';

        $texto = $this->gerar('creche_pacote', $system, $prompt, $userId);

        $dados = json_decode($this->extrairJson($texto), true);
        if (!is_array($dados)) {
            throw new AIException('A IA nao retornou o pacote em formato valido. Tente novamente.');
        }

        return $this->normalizarPacoteItens($dados);
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

        $dados = json_decode($this->extrairJson($texto), true);
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
    public function gerar(string $tipo, string $system, string $prompt, ?int $userId = null): string
    {
        $promptHash = hash('sha256', $this->model . '|' . $system . '|' . $prompt);

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
            $this->registrarGeracao($userId, $tipo, $promptHash, null, null, 'erro', $e->getMessage());

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
     * Extrai o array JSON de uma resposta, tolerando cercas ```json e texto
     * antes/depois (pega do primeiro '[' ao ultimo ']').
     */
    private function extrairJson(string $texto): string
    {
        $texto = trim($texto);
        $texto = preg_replace('/^```(?:json)?\s*/i', '', $texto);
        $texto = preg_replace('/\s*```$/', '', (string) $texto);

        $ini = strpos((string) $texto, '[');
        $fim = strrpos((string) $texto, ']');
        if ($ini !== false && $fim !== false && $fim > $ini) {
            return substr((string) $texto, $ini, $fim - $ini + 1);
        }

        return (string) $texto;
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

    /** Sanitiza os itens de um pacote (inclui o campo "tipo"). */
    private function normalizarPacoteItens(array $dados): array
    {
        $out = [];
        foreach ($dados as $a) {
            if (!is_array($a) || empty($a['titulo'])) {
                continue;
            }
            $out[] = [
                'tipo' => !empty($a['tipo']) ? mb_substr((string) $a['tipo'], 0, 60) : null,
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

    private function registrarGeracao(
        ?int $userId,
        string $tipo,
        string $promptHash,
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
