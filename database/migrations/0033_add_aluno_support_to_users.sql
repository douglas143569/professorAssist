-- Area do Aluno (Fase 1): o aluno reaproveita a tabela users com role='aluno'.
-- Alunos entram por NOME + SENHA (sem e-mail), escolhendo escola + turma.

-- E-mail deixa de ser obrigatorio: aluno nao tem. A UNIQUE existente aceita
-- varios NULL no MySQL/MariaDB, entao continua valendo para professores.
ALTER TABLE users MODIFY email VARCHAR(150) NULL;

-- Login do aluno casa por nome dentro da turma; indexa para a busca ficar rapida.
ALTER TABLE users ADD INDEX idx_users_name (name);
