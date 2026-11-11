<?php

namespace App\Models;

use App\Core\Model;

/**
 * Escola: topo da hierarquia (Escola > Turma > Materia > Tema da aula).
 * So dados institucionais — nada de dado pessoal de aluno.
 */
class Escola extends Model
{
    public const REDES = [
        'municipal' => 'Municipal',
        'estadual' => 'Estadual',
        'federal' => 'Federal',
        'privada' => 'Privada',
    ];

    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM turmas t WHERE t.escola_id = e.id) AS n_turmas
               FROM escolas e
              WHERE e.user_id = :u
              ORDER BY e.nome'
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM escolas WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    /** Totais reais dos recursos pertencentes a uma escola. */
    public function resumo(int $escolaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                (SELECT COUNT(*) FROM turmas t WHERE t.escola_id = e.id) AS n_turmas,
                (SELECT COUNT(*) FROM disciplinas d JOIN turmas t ON t.id = d.turma_id WHERE t.escola_id = e.id) AS n_materias,
                (SELECT COUNT(*) FROM turma_alunos ta JOIN turmas t ON t.id = ta.turma_id WHERE t.escola_id = e.id AND ta.situacao = \'ativa\') AS n_alunos,
                (SELECT COUNT(*) FROM conteudos c JOIN modulos m ON m.id = c.modulo_id JOIN disciplinas d ON d.id = m.disciplina_id JOIN turmas t ON t.id = d.turma_id WHERE t.escola_id = e.id) AS n_conteudos,
                (SELECT COUNT(*) FROM questoes q JOIN disciplinas d ON d.id = q.disciplina_id JOIN turmas t ON t.id = d.turma_id WHERE t.escola_id = e.id) AS n_questoes,
                (SELECT COUNT(*) FROM atividades a JOIN modulos m ON m.id = a.modulo_id JOIN disciplinas d ON d.id = m.disciplina_id JOIN turmas t ON t.id = d.turma_id WHERE t.escola_id = e.id) AS n_atividades
             FROM escolas e WHERE e.id = :id'
        );
        $stmt->execute(['id' => $escolaId]);
        $resumo = $stmt->fetch() ?: [];
        foreach (['n_turmas', 'n_materias', 'n_alunos', 'n_conteudos', 'n_questoes', 'n_atividades'] as $campo) {
            $resumo[$campo] = (int) ($resumo[$campo] ?? 0);
        }
        return $resumo;
    }

    /**
     * Todas as escolas (id, nome) -- para os <select> publicos do cadastro/login
     * do aluno. So dado institucional, nada de dado pessoal.
     */
    public function todas(): array
    {
        return $this->db->query('SELECT id, nome FROM escolas ORDER BY nome')->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO escolas (user_id, nome, rede, cidade, uf, endereco, telefone)
             VALUES (:u, :nome, :rede, :cidade, :uf, :endereco, :telefone)'
        );
        $stmt->execute([
            'u' => $data['user_id'],
            'nome' => $data['nome'],
            'rede' => self::nullable($data['rede'] ?? null),
            'cidade' => self::nullable($data['cidade'] ?? null),
            'uf' => self::nullable($data['uf'] ?? null),
            'endereco' => self::nullable($data['endereco'] ?? null),
            'telefone' => self::nullable($data['telefone'] ?? null),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE escolas
                SET nome = :nome, rede = :rede, cidade = :cidade, uf = :uf,
                    endereco = :endereco, telefone = :telefone
              WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'nome' => $data['nome'],
            'rede' => self::nullable($data['rede'] ?? null),
            'cidade' => self::nullable($data['cidade'] ?? null),
            'uf' => self::nullable($data['uf'] ?? null),
            'endereco' => self::nullable($data['endereco'] ?? null),
            'telefone' => self::nullable($data['telefone'] ?? null),
        ]);
    }

    public function delete(int $id): void
    {
        // Turmas, materias, temas e conteudos caem por ON DELETE CASCADE.
        $stmt = $this->db->prepare('DELETE FROM escolas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM escolas WHERE user_id = :u');
        $stmt->execute(['u' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    private static function nullable(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }
}
