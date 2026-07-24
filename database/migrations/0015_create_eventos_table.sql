-- Agenda do professor: provas, trabalhos, lembretes e aulas.
-- E a agenda pessoal do professor (nao envolve alunos). Pode se ligar
-- opcionalmente a uma disciplina.
CREATE TABLE IF NOT EXISTS eventos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,           -- professor dono
    disciplina_id INT UNSIGNED NULL,         -- vinculo opcional

    titulo VARCHAR(200) NOT NULL,
    tipo ENUM('prova','trabalho','lembrete','aula') NOT NULL DEFAULT 'lembrete',
    descricao TEXT NULL,

    data_evento DATE NOT NULL,
    hora TIME NULL,                          -- opcional
    concluido TINYINT(1) NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_eventos_user_data (user_id, data_evento),
    INDEX idx_eventos_disciplina (disciplina_id),

    CONSTRAINT fk_eventos_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_eventos_disciplina FOREIGN KEY (disciplina_id)
        REFERENCES disciplinas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
