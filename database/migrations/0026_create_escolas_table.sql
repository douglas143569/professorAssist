-- Escola: novo topo da hierarquia.
-- Escola > Turma > Materia (disciplinas) > Tema da aula (modulos).
-- Guarda so dados institucionais (nenhum dado pessoal de aluno).
CREATE TABLE escolas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,                             -- professor dono
    nome VARCHAR(160) NOT NULL,                                -- ex: 'EMEF Machado de Assis'
    rede ENUM('municipal','estadual','federal','privada') NULL,
    cidade VARCHAR(120) NULL,
    uf CHAR(2) NULL,
    endereco VARCHAR(200) NULL,
    telefone VARCHAR(30) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_escolas_user (user_id),

    CONSTRAINT fk_escolas_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
