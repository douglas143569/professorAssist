<?php

namespace App\Models;

use App\Core\Model;

/**
 * Questoes que compoem uma prova, com a ordem e a pontuacao de cada uma.
 */
class ProvaQuestao extends Model
{
    /**
     * Questoes da prova na ordem definida pelo professor, ja com as
     * alternativas de cada uma (a prova impressa precisa das duas coisas).
     */
    public function byProva(int $provaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT pq.id AS vinculo_id, pq.ordem, pq.pontuacao,
                    q.id, q.tipo, q.enunciado, q.dificuldade, q.habilidade_bncc,
                    m.titulo AS modulo_titulo
               FROM prova_questoes pq
               JOIN questoes q ON q.id = pq.questao_id
               LEFT JOIN modulos m ON m.id = q.modulo_id
              WHERE pq.prova_id = :p
              ORDER BY pq.ordem, pq.id'
        );
        $stmt->execute(['p' => $provaId]);

        $questoes = $stmt->fetchAll();

        if ($questoes === []) {
            return [];
        }

        $alternativas = $this->alternativasDe(array_column($questoes, 'id'));

        foreach ($questoes as &$questao) {
            $questao['alternativas'] = $alternativas[(int) $questao['id']] ?? [];
        }

        return $questoes;
    }

    /**
     * Alternativas de varias questoes numa consulta so (evita uma consulta
     * por questao ao montar a prova).
     *
     * @param int[] $questaoIds
     * @return array<int, array>
     */
    private function alternativasDe(array $questaoIds): array
    {
        if ($questaoIds === []) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($questaoIds), '?'));

        $stmt = $this->db->prepare(
            "SELECT questao_id, texto, correta
               FROM questao_alternativas
              WHERE questao_id IN ({$marcadores})
              ORDER BY questao_id, ordem, id"
        );
        $stmt->execute(array_map('intval', $questaoIds));

        $porQuestao = [];
        foreach ($stmt->fetchAll() as $alternativa) {
            $porQuestao[(int) $alternativa['questao_id']][] = $alternativa;
        }

        return $porQuestao;
    }

    /** Adiciona uma questao no fim da prova. Ignora se ja estiver la. */
    public function adicionar(int $provaId, int $questaoId, float $pontuacao = 1.0): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO prova_questoes (prova_id, questao_id, ordem, pontuacao)
             VALUES (:p, :q, :ordem, :pontuacao)'
        );
        $stmt->execute([
            'p' => $provaId,
            'q' => $questaoId,
            'ordem' => $this->proximaOrdem($provaId),
            'pontuacao' => $pontuacao,
        ]);
    }

    public function remover(int $provaId, int $questaoId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM prova_questoes WHERE prova_id = :p AND questao_id = :q'
        );
        $stmt->execute(['p' => $provaId, 'q' => $questaoId]);
    }

    /**
     * Salva ordem e pontuacao de todas as questoes da prova de uma vez.
     *
     * @param array<int, array{ordem: int, pontuacao: float}> $valores por questao_id
     */
    public function salvarOrdemEPontuacao(int $provaId, array $valores): void
    {
        $stmt = $this->db->prepare(
            'UPDATE prova_questoes SET ordem = :ordem, pontuacao = :pontuacao
              WHERE prova_id = :p AND questao_id = :q'
        );

        $this->db->beginTransaction();
        try {
            foreach ($valores as $questaoId => $valor) {
                $stmt->execute([
                    'p' => $provaId,
                    'q' => (int) $questaoId,
                    'ordem' => (int) $valor['ordem'],
                    'pontuacao' => (float) $valor['pontuacao'],
                ]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function totalPontos(int $provaId): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(pontuacao), 0) FROM prova_questoes WHERE prova_id = :p'
        );
        $stmt->execute(['p' => $provaId]);

        return (float) $stmt->fetchColumn();
    }

    public function limpar(int $provaId): void
    {
        $stmt = $this->db->prepare('DELETE FROM prova_questoes WHERE prova_id = :p');
        $stmt->execute(['p' => $provaId]);
    }

    private function proximaOrdem(int $provaId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(ordem), 0) + 1 FROM prova_questoes WHERE prova_id = :p'
        );
        $stmt->execute(['p' => $provaId]);

        return (int) $stmt->fetchColumn();
    }
}
