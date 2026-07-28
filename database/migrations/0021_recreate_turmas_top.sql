-- Reestrutura a hierarquia: Turma (topo) > Materia (disciplinas) > Tema (modulos).
-- A tabela 'turmas' antiga (turma sob disciplina) nunca foi usada; recriamos
-- como nivel de topo, dona do professor, com etapa/ano da turma.
DROP TABLE IF EXISTS turmas;

CREATE TABLE turmas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,           -- professor dono
    nome VARCHAR(120) NOT NULL,              -- ex: '6 ano A'
    etapa ENUM('EF','EM') NULL,              -- Ensino Fundamental / Medio
    ano_serie VARCHAR(30) NULL,              -- ex: '6 ano'

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_turmas_user (user_id),

    CONSTRAINT fk_turmas_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
