<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_PROFESSOR = 'professor';

    /** Colunas seguras para trafegar na aplicacao (sem o hash da senha). */
    private const CAMPOS = 'id, name, email, role, ativo, last_login_at, created_at';

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT ' . self::CAMPOS . ' FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Traz tambem o password_hash: use SOMENTE na verificacao de login.
     */
    public function findByEmailComHash(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::CAMPOS . ', password_hash FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT ' . self::CAMPOS . ' FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Todas as contas, para a pagina de administracao. Traz quanto cada
     * professor tem no sistema -- desativar alguem com muito conteudo e uma
     * decisao diferente de desativar uma conta vazia.
     */
    public function all(): array
    {
        $sql = 'SELECT ' . self::CAMPOS . ',
                    (SELECT COUNT(*) FROM escolas e WHERE e.user_id = users.id) AS n_escolas,
                    (SELECT COUNT(*) FROM disciplinas d WHERE d.user_id = users.id) AS n_materias
                  FROM users
                 ORDER BY role = \'admin\' DESC, name';

        return $this->db->query($sql)->fetchAll();
    }

    public function definirRole(int $id, string $role): void
    {
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_PROFESSOR], true)) {
            return;
        }

        $stmt = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $id]);
    }

    public function create(string $nome, string $email, string $senha, string $role = self::ROLE_PROFESSOR): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, role, ativo)
             VALUES (:name, :email, :hash, :role, 1)'
        );
        $stmt->execute([
            'name' => $nome,
            'email' => $email,
            'hash' => password_hash($senha, PASSWORD_DEFAULT),
            'role' => $role,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Converte uma conta existente em administradora, trocando nome, e-mail e
     * senha. Usado para transformar o "Professor Demo" na conta real sem perder
     * nada do que ja foi criado (escolas, turmas, questoes... apontam para o id).
     */
    public function promoverParaAdmin(int $id, string $nome, string $email, string $senha): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users
                SET name = :name, email = :email, password_hash = :hash,
                    role = :role, ativo = 1
              WHERE id = :id'
        );
        $stmt->execute([
            'name' => $nome,
            'email' => $email,
            'hash' => password_hash($senha, PASSWORD_DEFAULT),
            'role' => self::ROLE_ADMIN,
            'id' => $id,
        ]);
    }

    public function atualizarSenha(int $id, string $senha): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute([
            'hash' => password_hash($senha, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
    }

    public function registrarLogin(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function definirAtivo(int $id, bool $ativo): void
    {
        $stmt = $this->db->prepare('UPDATE users SET ativo = :ativo WHERE id = :id');
        $stmt->execute(['ativo' => $ativo ? 1 : 0, 'id' => $id]);
    }

    public function countAdmins(): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE role = :role AND ativo = 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role' => self::ROLE_ADMIN]);

        return (int) $stmt->fetchColumn();
    }
}
