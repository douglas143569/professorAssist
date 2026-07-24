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
     * @param array $data user_id, tipo, modelo, prompt_hash, tokens_in,
     *                    tokens_out, custo_estimado, status, erro
     */
    public function registrar(array $data): int
    {
        $sql = 'INSERT INTO ai_geracoes
                    (user_id, tipo, modelo, prompt_hash, tokens_in, tokens_out,
                     custo_estimado, status, erro)
                VALUES
                    (:user_id, :tipo, :modelo, :prompt_hash, :tokens_in, :tokens_out,
                     :custo_estimado, :status, :erro)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'tipo' => $data['tipo'],
            'modelo' => $data['modelo'] ?? null,
            'prompt_hash' => $data['prompt_hash'] ?? null,
            'tokens_in' => $data['tokens_in'] ?? null,
            'tokens_out' => $data['tokens_out'] ?? null,
            'custo_estimado' => $data['custo_estimado'] ?? null,
            'status' => $data['status'] ?? 'ok',
            'erro' => $data['erro'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
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
