<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_PROFESSOR = 'professor';
    public const ROLE_ALUNO = 'aluno';

    /** Colunas seguras para trafegar na aplicacao (sem o hash da senha). */
    private const CAMPOS = 'id, name, email, celular, role, ativo, last_login_at, created_at';

    /**
     * Deixa so os digitos do celular. Aceita "(11) 98765-4321", "11987654321"
     * e afins; devolve null se nao sobrar um numero brasileiro plausivel
     * (10 digitos para fixo antigo, 11 para celular com o 9).
     */
    public static function normalizarCelular(?string $celular): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $celular);

        if ($digitos === '' || strlen($digitos) < 10 || strlen($digitos) > 11) {
            return null;
        }

        return $digitos;
    }

    /** Formata para exibicao: (11) 98765-4321 */
    public static function formatarCelular(?string $celular): string
    {
        $d = preg_replace('/\D/', '', (string) $celular);

        if (strlen($d) === 11) {
            return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7));
        }

        if (strlen($d) === 10) {
            return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
        }

        return (string) $celular;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT ' . self::CAMPOS . ' FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** Registra o aceite da Politica de Privacidade (quando e qual versao). */
    public function registrarAceite(int $id, string $versao): void
    {
        $stmt = $this->db->prepare('UPDATE users SET aceite_privacidade_em = NOW(), aceite_versao = :v WHERE id = :id');
        $stmt->execute(['v' => $versao, 'id' => $id]);
    }

    /** @return array{aceite_privacidade_em:?string, aceite_versao:?string} */
    public function aceite(int $id): array
    {
        $stmt = $this->db->prepare('SELECT aceite_privacidade_em, aceite_versao FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: ['aceite_privacidade_em' => null, 'aceite_versao' => null];
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

    public function create(
        string $nome,
        string $email,
        string $senha,
        string $role = self::ROLE_PROFESSOR,
        ?string $celular = null,
        bool $ativo = true
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, celular, password_hash, role, ativo)
             VALUES (:name, :email, :celular, :hash, :role, :ativo)'
        );
        $stmt->execute([
            'name' => $nome,
            'email' => $email,
            'celular' => self::normalizarCelular($celular),
            'hash' => password_hash($senha, PASSWORD_DEFAULT),
            'role' => $role,
            'ativo' => $ativo ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Cria uma conta de ALUNO. Aluno nao tem e-mail nem celular: entra pelo
     * nome dentro da turma (ver Auth::tentarLoginAluno). Ja nasce ativa.
     */
    public function criarAluno(string $nome, string $senha): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, celular, password_hash, role, ativo)
             VALUES (:name, NULL, NULL, :hash, :role, 1)'
        );
        $stmt->execute([
            'name' => $nome,
            'hash' => password_hash($senha, PASSWORD_DEFAULT),
            'role' => self::ROLE_ALUNO,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Busca o aluno pelo nome DENTRO de uma turma, trazendo o password_hash.
     * Use SOMENTE na verificacao de login. A comparacao ignora caixa e espacos
     * nas pontas -- o cadastro garante nome unico por turma.
     */
    public function findAlunoParaLogin(int $turmaId, string $nome): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.email, u.celular, u.role, u.ativo,
                    u.last_login_at, u.created_at, u.password_hash
               FROM users u
               JOIN turma_alunos ta ON ta.aluno_id = u.id
              WHERE ta.turma_id = :t
                AND u.role = :role
                AND LOWER(TRIM(u.name)) = LOWER(TRIM(:nome))
              LIMIT 1'
        );
        $stmt->execute(['t' => $turmaId, 'role' => self::ROLE_ALUNO, 'nome' => $nome]);

        return $stmt->fetch() ?: null;
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
