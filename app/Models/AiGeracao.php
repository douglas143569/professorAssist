<?php

namespace App\Models;

use App\Core\Model;

/**
 * Registro de cada chamada ao assistente de IA (tabela ai_geracoes).
 * Base para controle de custo, cache (prompt_hash) e auditoria de uso.
 *
 * Use preferencialmente via App\Services\AI, que ja preenche tokens e custo.
 */
class AiGeracao extends Model
{
    /**
     * Insere um registro de geracao e devolve o id criado.
     *
     * @param array $data user_id, tipo, modelo, prompt_hash, resposta, tokens_in,
     *                    tokens_out, custo_estimado, status, erro
     */
    public function registrar(array $data): int
    {
        $sql = 'INSERT INTO ai_geracoes
                    (user_id, tipo, modelo, prompt_hash, resposta, tokens_in, tokens_out,
                     custo_estimado, status, erro)
                VALUES
                    (:user_id, :tipo, :modelo, :prompt_hash, :resposta, :tokens_in, :tokens_out,
                     :custo_estimado, :status, :erro)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'tipo' => $data['tipo'],
            'modelo' => $data['modelo'] ?? null,
            'prompt_hash' => $data['prompt_hash'] ?? null,
            'resposta' => $data['resposta'] ?? null,
            'tokens_in' => $data['tokens_in'] ?? null,
            'tokens_out' => $data['tokens_out'] ?? null,
            'custo_estimado' => $data['custo_estimado'] ?? null,
            'status' => $data['status'] ?? 'ok',
            'erro' => $data['erro'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Procura uma resposta ja gerada para o mesmo prompt e o mesmo professor.
     *
     * O escopo e por usuario de proposito: nada gerado por um professor vaza
     * para a conta de outro. Para compartilhar entre todos, remova o
     * "user_id <=> :user_id" (o <=> compara certo mesmo quando e NULL).
     */
    public function respostaEmCache(string $promptHash, ?int $userId): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT resposta
               FROM ai_geracoes
              WHERE prompt_hash = :hash
                AND user_id <=> :user_id
                AND status = 'ok'
                AND resposta IS NOT NULL
              ORDER BY id DESC
              LIMIT 1"
        );
        $stmt->execute(['hash' => $promptHash, 'user_id' => $userId]);

        $resposta = $stmt->fetchColumn();

        return $resposta !== false ? (string) $resposta : null;
    }

    /**
     * Gasto de CADA professor, em USD. Alimenta a tela de administracao --
     * o custo da IA sai da mesma chave da API, entao o admin precisa ver
     * quem esta consumindo.
     *
     * Respostas servidas do cache entram como status 'cache' com custo zero,
     * logo nao somam aqui.
     *
     * @return array<int, float> user_id => total
     */
    public function custoPorUsuario(): array
    {
        $linhas = $this->db->query(
            'SELECT user_id, SUM(custo_estimado) AS total
               FROM ai_geracoes
              WHERE status = "ok" AND user_id IS NOT NULL
              GROUP BY user_id'
        )->fetchAll();

        $porUsuario = [];
        foreach ($linhas as $linha) {
            $porUsuario[(int) $linha['user_id']] = (float) $linha['total'];
        }

        return $porUsuario;
    }

    /** Gasto somado de todas as contas, em USD. */
    public function custoTotal(): float
    {
        return (float) $this->db->query(
            'SELECT COALESCE(SUM(custo_estimado), 0) FROM ai_geracoes WHERE status = "ok"'
        )->fetchColumn();
    }

    /**
     * Soma o custo estimado (USD) gasto por um professor. Usado para aplicar
     * o teto de gasto por usuario (RF-20).
     */
    public function custoTotalUsuario(int $userId): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(custo_estimado), 0) AS total
               FROM ai_geracoes
              WHERE user_id = :user_id AND status = "ok"'
        );
        $stmt->execute(['user_id' => $userId]);

        return (float) $stmt->fetchColumn();
    }
}
