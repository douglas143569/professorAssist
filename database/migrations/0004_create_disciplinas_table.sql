-- Disciplinas do professor (ex: Matematica - 6 ano, Historia - 1 serie EM).
-- Cada disciplina pertence a um professor (users) e define a etapa/ano.
CREATE TABLE IF NOT EXISTS disciplinas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,           -- professor dono
    nome VARCHAR(150) NOT NULL,              -- ex: 'Matematica'
    etapa ENUM('EF','EM') NOT NULL,          -- Ensino Fundamental / Medio
    ano_serie VARCHAR(30) NULL,              -- ex: '6 ano', '1 serie'

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_disciplinas_user (user_id),

    CONSTRAINT fk_disciplinas_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
