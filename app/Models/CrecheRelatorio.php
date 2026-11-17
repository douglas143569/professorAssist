<?php

namespace App\Models;

use App\Core\Model;

/** Relatorio individual de aluno da creche. Sempre filtrado por professor. */
class CrecheRelatorio extends Model
{
    public function byUser(int $userId, string $busca = ''): array
    {
        $sql = 'SELECT id, aluno_nome, idade, turma, periodo, professor, created_at, updated_at,
                       CHAR_LENGTH(texto) AS n_chars
                  FROM creche_relatorios WHERE user_id = :u';
        $params = ['u' => $userId];
        if ($busca !== '') {
            $sql .= ' AND (aluno_nome LIKE :b1 OR turma LIKE :b2 OR periodo LIKE :b3)';
            $like = '%' . addcslashes($busca, '%_\\') . '%';
            $params += ['b1' => $like, 'b2' => $like, 'b3' => $like];
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY id DESC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM creche_relatorios WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO creche_relatorios (user_id, aluno_nome, idade, turma, periodo, professor, texto)
             VALUES (:u, :nome, :idade, :turma, :periodo, :prof, :texto)'
        );
        $stmt->execute([
            'u' => $d['user_id'], 'nome' => $d['aluno_nome'], 'idade' => $d['idade'],
            'turma' => $d['turma'], 'periodo' => $d['periodo'], 'prof' => $d['professor'],
            'texto' => $d['texto'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateTexto(int $id, int $userId, string $texto): void
    {
        $stmt = $this->db->prepare('UPDATE creche_relatorios SET texto = :t WHERE id = :id AND user_id = :u');
        $stmt->execute(['t' => $texto, 'id' => $id, 'u' => $userId]);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM creche_relatorios WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
    }
}
