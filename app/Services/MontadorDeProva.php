<?php

namespace App\Services;

/**
 * Regra de montagem de provas: sorteio das questoes e embaralhamento das
 * versoes (RF-15 a RF-17).
 *
 * Nao toca no banco de proposito -- recebe e devolve arrays. Isso mantem a
 * parte dificil (distribuicao por dificuldade, embaralhamento reproduzivel,
 * gabarito por versao) isolada e testavel sem subir a aplicacao.
 *
 * REPRODUTIBILIDADE: a versao B de uma prova precisa sair sempre igual, hoje
 * e daqui a um mes, senao o gabarito impresso na semana passada nao serve
 * mais. Por isso o embaralhamento usa um gerador proprio com semente
 * (xorshift32) em vez de shuffle()/mt_rand(), que dependem do estado global
 * e da versao do PHP.
 */
class MontadorDeProva
{
    public const DIFICULDADES = ['facil', 'media', 'dificil'];

    /** Letras das alternativas na folha impressa. */
    private const LETRAS = ['a', 'b', 'c', 'd', 'e', 'f'];

    /**
     * Sorteia as questoes da prova respeitando a quantidade pedida por
     * dificuldade. Se faltar questao de uma dificuldade, completa com as
     * demais -- e melhor entregar a prova cheia e avisar do que entregar
     * menos questoes do que o professor pediu.
     *
     * @param array $disponiveis  questoes aprovadas do banco
     * @param array $quantidades  ['facil' => 3, 'media' => 5, 'dificil' => 2]
     * @return array{questoes: array, faltou: array}
     */
    public function sortear(array $disponiveis, array $quantidades, int $seed): array
    {
        $rand = $this->gerador($seed);

        $porDificuldade = ['facil' => [], 'media' => [], 'dificil' => []];
        foreach ($disponiveis as $q) {
            $nivel = $q['dificuldade'] ?? 'media';
            if (isset($porDificuldade[$nivel])) {
                $porDificuldade[$nivel][] = $q;
            }
        }

        $escolhidas = [];
        $faltou = [];
        $sobras = [];

        foreach (self::DIFICULDADES as $nivel) {
            $pedido = max(0, (int) ($quantidades[$nivel] ?? 0));
            $pool = $this->embaralhar($porDificuldade[$nivel], $rand);

            $pegou = array_slice($pool, 0, $pedido);
            $escolhidas = array_merge($escolhidas, $pegou);
            $sobras = array_merge($sobras, array_slice($pool, $pedido));

            if (count($pegou) < $pedido) {
                $faltou[$nivel] = $pedido - count($pegou);
            }
        }

        // Completa o que faltou com questoes de outras dificuldades.
        $totalPedido = array_sum(array_map(fn($n) => max(0, (int) $n), $quantidades));
        if (count($escolhidas) < $totalPedido) {
            $complemento = array_slice(
                $this->embaralhar($sobras, $rand),
                0,
                $totalPedido - count($escolhidas)
            );
            $escolhidas = array_merge($escolhidas, $complemento);
        }

        return ['questoes' => $escolhidas, 'faltou' => $faltou];
    }

    /**
     * Monta uma versao da prova: embaralha as questoes e as alternativas de
     * cada uma, e devolve ja com a letra de cada alternativa e o gabarito.
     *
     * Semente 0 = nao embaralha (usada na versao A, que preserva a ordem que
     * o professor definiu na tela).
     *
     * @param array $questoes cada uma com 'alternativas' (texto, correta)
     * @return array{questoes: array, gabarito: array<int, string>}
     */
    public function montarVersao(array $questoes, int $seed): array
    {
        if ($seed > 0) {
            $questoes = $this->embaralhar($questoes, $this->gerador($seed));
        }

        $gabarito = [];
        $numero = 1;

        foreach ($questoes as &$questao) {
            $alternativas = $questao['alternativas'] ?? [];

            if ($alternativas !== [] && $seed > 0) {
                // Semente derivada do numero da questao: duas questoes da mesma
                // versao nao recebem o mesmo embaralhamento de alternativas.
                $alternativas = $this->embaralhar($alternativas, $this->gerador($seed + $numero * 7919));
            }

            foreach ($alternativas as $i => &$alternativa) {
                $alternativa['letra'] = self::LETRAS[$i] ?? '?';

                if (!empty($alternativa['correta'])) {
                    $gabarito[$numero] = $alternativa['letra'];
                }
            }
            unset($alternativa);

            $questao['alternativas'] = $alternativas;
            $questao['numero'] = $numero;

            if ($alternativas === []) {
                $gabarito[$numero] = '—'; // dissertativa: correcao a criterio do professor
            }

            $numero++;
        }
        unset($questao);

        return ['questoes' => $questoes, 'gabarito' => $gabarito];
    }

    /**
     * Semente de uma versao a partir do rotulo. Deriva do id da prova para
     * que provas diferentes nao tenham o mesmo embaralhamento.
     */
    public function sementeDaVersao(int $provaId, string $rotulo): int
    {
        if ($rotulo === 'A') {
            return 0; // versao A mantem a ordem definida pelo professor
        }

        return ($provaId * 1_000_003 + ord($rotulo) * 31) % 2_147_483_647;
    }

    /**
     * Fisher-Yates com gerador proprio: mesma semente, mesma ordem, sempre.
     */
    private function embaralhar(array $itens, callable $rand): array
    {
        $itens = array_values($itens);

        for ($i = count($itens) - 1; $i > 0; $i--) {
            $j = $rand($i);
            [$itens[$i], $itens[$j]] = [$itens[$j], $itens[$i]];
        }

        return $itens;
    }

    /**
     * xorshift32 com semente. Devolve uma funcao que sorteia de 0 a $max.
     * Independente do estado global e da versao do PHP.
     */
    private function gerador(int $seed): callable
    {
        $estado = $seed > 0 ? $seed : 1;

        return function (int $max) use (&$estado): int {
            $estado ^= ($estado << 13) & 0xFFFFFFFF;
            $estado ^= $estado >> 17;
            $estado ^= ($estado << 5) & 0xFFFFFFFF;
            $estado &= 0xFFFFFFFF;

            return $max > 0 ? $estado % ($max + 1) : 0;
        };
    }
}
