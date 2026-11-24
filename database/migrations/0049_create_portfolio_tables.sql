-- Portfolio Pedagogico Infantil (aba Creche).
-- ATENCAO: guarda dados pessoais de CRIANCAS (nome, nascimento, foto, familia,
-- observacoes). Tudo pertence a um professor (user_id) e so ele enxerga; some
-- junto com a conta (ON DELETE CASCADE) e entra na exportacao/exclusao LGPD.
-- Nada disso vai para logs. A IA recebe so o texto, nunca nome nem foto.

CREATE TABLE IF NOT EXISTS portfolio_criancas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    nome VARCHAR(120) NOT NULL,
    nascimento DATE NULL,
    turma VARCHAR(80) NOT NULL,
    periodo VARCHAR(30) NOT NULL DEFAULT 'Integral',
    professora VARCHAR(120) NOT NULL,
    data_matricula DATE NULL,
    familia TEXT NULL,
    foto_path VARCHAR(255) NULL,
    autoriza_imagem TINYINT(1) NOT NULL DEFAULT 0,
    apresentacao TEXT NULL,
    adaptacao TEXT NULL,
    caracteristicas TEXT NULL,
    mensagem_final TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pf_criancas_user (user_id, nome),
    CONSTRAINT fk_pf_criancas_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS portfolio_projetos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    objetivos TEXT NULL,
    atividades TEXT NULL,
    data_inicio DATE NULL,
    data_fim DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pf_projetos_user (user_id),
    CONSTRAINT fk_pf_projetos_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS portfolio_registros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    crianca_id INT UNSIGNED NOT NULL,
    projeto_id INT UNSIGNED NULL,
    data_registro DATE NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    descricao TEXT NOT NULL,
    categoria VARCHAR(40) NOT NULL DEFAULT 'Rotina',
    area VARCHAR(30) NULL,
    objetivos TEXT NULL,
    habilidades TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pf_reg_crianca (crianca_id, data_registro),
    INDEX idx_pf_reg_projeto (projeto_id),
    INDEX idx_pf_reg_user (user_id),
    CONSTRAINT fk_pf_reg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pf_reg_crianca FOREIGN KEY (crianca_id) REFERENCES portfolio_criancas(id) ON DELETE CASCADE,
    CONSTRAINT fk_pf_reg_projeto FOREIGN KEY (projeto_id) REFERENCES portfolio_projetos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fotos, videos, producoes e arquivos. Ligados a um registro e/ou a uma area de desenvolvimento.
CREATE TABLE IF NOT EXISTS portfolio_midias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    crianca_id INT UNSIGNED NOT NULL,
    registro_id INT UNSIGNED NULL,
    area VARCHAR(30) NULL,
    tipo ENUM('foto','video','producao','arquivo') NOT NULL DEFAULT 'foto',
    path VARCHAR(255) NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    legenda VARCHAR(200) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pf_midias_crianca (crianca_id, tipo),
    INDEX idx_pf_midias_registro (registro_id),
    INDEX idx_pf_midias_user (user_id),
    CONSTRAINT fk_pf_midias_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pf_midias_crianca FOREIGN KEY (crianca_id) REFERENCES portfolio_criancas(id) ON DELETE CASCADE,
    CONSTRAINT fk_pf_midias_registro FOREIGN KEY (registro_id) REFERENCES portfolio_registros(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registro descritivo por area de desenvolvimento (1 texto por crianca e area).
CREATE TABLE IF NOT EXISTS portfolio_areas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    crianca_id INT UNSIGNED NOT NULL,
    area VARCHAR(30) NOT NULL,
    texto TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pf_areas (crianca_id, area),
    INDEX idx_pf_areas_user (user_id),
    CONSTRAINT fk_pf_areas_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pf_areas_crianca FOREIGN KEY (crianca_id) REFERENCES portfolio_criancas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS portfolio_avaliacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    crianca_id INT UNSIGNED NOT NULL,
    periodicidade ENUM('bimestre','trimestre','semestre','ano') NOT NULL,
    periodo VARCHAR(40) NOT NULL,
    ano SMALLINT UNSIGNED NOT NULL,
    socioemocional TEXT NULL,
    linguagem TEXT NULL,
    movimento TEXT NULL,
    interacao TEXT NULL,
    participacao TEXT NULL,
    autonomia TEXT NULL,
    consideracoes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pf_aval_crianca (crianca_id, ano),
    INDEX idx_pf_aval_user (user_id),
    CONSTRAINT fk_pf_aval_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pf_aval_crianca FOREIGN KEY (crianca_id) REFERENCES portfolio_criancas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
