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
        return array_map([$this, 'decodeItens'], $stmt->fetchAll());
    }

    /** Busca um item garantindo o dono (via pacote). Traz dados do pacote. */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, p.id AS pacote_id, p.tema AS pacote_tema, p.titulo AS pacote_titulo,
                    p.faixa_etaria AS pacote_faixa
               FROM creche_pacote_itens i
               JOIN creche_pacotes p ON p.id = i.pacote_id
              WHERE i.id = :id AND p.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        $row = $stmt->fetch();
        return $row ? $this->decodeItens($row) : null;
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE creche_pacote_itens SET tipo = :tipo, formato = :formato, titulo = :titulo,
                    instrucao = :instrucao, itens_json = :itens_json
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'tipo' => ($data['tipo'] ?? '') !== '' ? $data['tipo'] : null,
            'formato' => ($data['formato'] ?? '') === 'circular' ? 'circular' : 'escrever',
            'titulo' => $data['titulo'],
            'instrucao' => ($data['instrucao'] ?? '') !== '' ? $data['instrucao'] : null,
            'itens_json' => !empty($data['itens']) ? json_encode($data['itens'], JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM creche_pacote_itens WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** Decodifica itens_json numa chave 'itens' (array). */
    private function decodeItens(array $row): array
    {
        $row['itens'] = !empty($row['itens_json'])
            ? (json_decode($row['itens_json'], true) ?: [])
            : [];
        return $row;
    }
}
