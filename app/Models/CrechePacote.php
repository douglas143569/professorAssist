<?php

namespace App\Models;

use App\Core\Model;

class CrechePacote extends Model
{
    /** Pacotes do professor, com contagem de itens. */
    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM creche_pacote_itens i WHERE i.pacote_id = p.id) AS n_itens
               FROM creche_pacotes p
              WHERE p.user_id = :u
              ORDER BY p.id DESC'
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM creche_pacotes WHERE id = :id AND user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    /** Cria o pacote e seus itens numa transacao. */
    public function createComItens(array $pacote, array $itens): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO creche_pacotes (user_id, faixa_etaria, tema, titulo, descricao)
                 VALUES (:u, :faixa, :tema, :titulo, :descricao)'
            );
            $stmt->execute([
                'u' => $pacote['user_id'],
                'faixa' => $pacote['faixa_etaria'],
                'tema' => $pacote['tema'],
                'titulo' => $pacote['titulo'],
                'descricao' => ($pacote['descricao'] ?? '') !== '' ? $pacote['descricao'] : null,
            ]);
            $pacoteId = (int) $this->db->lastInsertId();

            $ins = $this->db->prepare(
                'INSERT INTO creche_pacote_itens (pacote_id, tipo, formato, titulo, instrucao, itens_json, ordem)
                 VALUES (:p, :tipo, :formato, :titulo, :instrucao, :itens_json, :ordem)'
            );
            $ordem = 0;
            foreach ($itens as $it) {
                if (($it['titulo'] ?? '') === '') {
                    continue;
                }
                $ins->execute([
                    'p' => $pacoteId,
                    'tipo' => ($it['tipo'] ?? '') !== '' ? $it['tipo'] : null,
                    'formato' => CrechePacoteItem::normalizarFormato($it['formato'] ?? null),
                    'titulo' => $it['titulo'],
                    'instrucao' => ($it['instrucao'] ?? '') !== '' ? $it['instrucao'] : null,
                    'itens_json' => !empty($it['itens']) ? json_encode($it['itens'], JSON_UNESCAPED_UNICODE) : null,
                    'ordem' => $ordem++,
                ]);
            }

            $this->db->commit();
            return $pacoteId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        // Itens caem por ON DELETE CASCADE.
        $stmt = $this->db->prepare('DELETE FROM creche_pacotes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
