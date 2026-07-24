<?php

namespace App\Models;

use App\Core\Model;

class CrechePacoteItem extends Model
{
    public function byPacote(int $pacoteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM creche_pacote_itens WHERE pacote_id = :p ORDER BY ordem, id'
        );
        $stmt->execute(['p' => $pacoteId]);
        return $stmt->fetchAll();
    }

    /** Busca um item garantindo o dono (via pacote). Traz dados do pacote. */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, p.id AS pacote_id, p.tema AS pacote_tema, p.titulo AS pacote_titulo
               FROM creche_pacote_itens i
               JOIN creche_pacotes p ON p.id = i.pacote_id
              WHERE i.id = :id AND p.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE creche_pacote_itens SET tipo = :tipo, titulo = :titulo,
                    descricao = :descricao, materiais = :materiais, duracao = :duracao
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'tipo' => ($data['tipo'] ?? '') !== '' ? $data['tipo'] : null,
            'titulo' => $data['titulo'],
            'descricao' => ($data['descricao'] ?? '') !== '' ? $data['descricao'] : null,
            'materiais' => ($data['materiais'] ?? '') !== '' ? $data['materiais'] : null,
            'duracao' => ($data['duracao'] ?? '') !== '' ? $data['duracao'] : null,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM creche_pacote_itens WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
