-- =========================================================
--  ProSiga — Sistema Acadêmico (MySQL / MariaDB)
--  Importe este arquivo no phpMyAdmin ou no cliente MySQL.
-- =========================================================

CREATE DATABASE IF NOT EXISTS prosiga
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE prosiga;

-- ---------------------------------------------------------
-- Usuários (alunos, professores e administradores)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome          VARCHAR(120) NOT NULL,
  email         VARCHAR(160) NOT NULL,
  senha         VARCHAR(255) NOT NULL,
  tipo          ENUM('admin','professor','aluno') NOT NULL DEFAULT 'aluno',
  matricula     VARCHAR(40)  DEFAULT NULL,
  setor         VARCHAR(120) DEFAULT NULL,
  token         VARCHAR(64)  DEFAULT NULL,
  ativo         TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  UNIQUE KEY uq_usuarios_matricula (matricula),
  KEY idx_usuarios_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Turmas
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS turmas (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome        VARCHAR(120) NOT NULL,
  disciplina  VARCHAR(120) NOT NULL,
  codigo      VARCHAR(30)  NOT NULL,
  semestre    VARCHAR(20)  NOT NULL DEFAULT '2026.1',
  sala        VARCHAR(40)  DEFAULT NULL,
  capacidade  SMALLINT UNSIGNED NOT NULL DEFAULT 40,
  criado_por  INT UNSIGNED DEFAULT NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_turmas_codigo (codigo),
  KEY idx_turmas_criado_por (criado_por),
  CONSTRAINT fk_turmas_usuario FOREIGN KEY (criado_por)
    REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Vínculos professores x turmas / alunos x turmas
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS turma_professores (
  turma_id     INT UNSIGNED NOT NULL,
  professor_id INT UNSIGNED NOT NULL,
  vinculo_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (turma_id, professor_id),
  KEY idx_tp_professor (professor_id),
  CONSTRAINT fk_tp_turma FOREIGN KEY (turma_id)
    REFERENCES turmas (id) ON DELETE CASCADE,
  CONSTRAINT fk_tp_professor FOREIGN KEY (professor_id)
    REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS turma_alunos (
  turma_id  INT UNSIGNED NOT NULL,
  aluno_id  INT UNSIGNED NOT NULL,
  vinculo_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (turma_id, aluno_id),
  KEY idx_ta_aluno (aluno_id),
  CONSTRAINT fk_ta_turma FOREIGN KEY (turma_id)
    REFERENCES turmas (id) ON DELETE CASCADE,
  CONSTRAINT fk_ta_aluno FOREIGN KEY (aluno_id)
    REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Aulas
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS aulas (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  turma_id     INT UNSIGNED NOT NULL,
  professor_id INT UNSIGNED DEFAULT NULL,
  titulo       VARCHAR(160) NOT NULL,
  data_aula    DATE NOT NULL,
  hora_inicio  TIME NOT NULL,
  hora_fim     TIME NOT NULL,
  local        VARCHAR(80) DEFAULT NULL,
  descricao    TEXT,
  criado_por   INT UNSIGNED DEFAULT NULL,
  criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_aulas_data (data_aula),
  KEY idx_aulas_turma (turma_id),
  CONSTRAINT fk_aulas_turma FOREIGN KEY (turma_id)
    REFERENCES turmas (id) ON DELETE CASCADE,
  CONSTRAINT fk_aulas_professor FOREIGN KEY (professor_id)
    REFERENCES usuarios (id) ON DELETE SET NULL,
  CONSTRAINT fk_aulas_criador FOREIGN KEY (criado_por)
    REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Provas
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS provas (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  turma_id     INT UNSIGNED NOT NULL,
  professor_id INT UNSIGNED DEFAULT NULL,
  titulo       VARCHAR(160) NOT NULL,
  data_prova   DATE NOT NULL,
  hora         TIME DEFAULT NULL,
  local        VARCHAR(80) DEFAULT NULL,
  peso         DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  descricao    TEXT,
  criado_por   INT UNSIGNED DEFAULT NULL,
  criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_provas_data (data_prova),
  KEY idx_provas_turma (turma_id),
  CONSTRAINT fk_provas_turma FOREIGN KEY (turma_id)
    REFERENCES turmas (id) ON DELETE CASCADE,
  CONSTRAINT fk_provas_professor FOREIGN KEY (professor_id)
    REFERENCES usuarios (id) ON DELETE SET NULL,
  CONSTRAINT fk_provas_criador FOREIGN KEY (criado_por)
    REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Atividades
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS atividades (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  turma_id       INT UNSIGNED NOT NULL,
  professor_id   INT UNSIGNED DEFAULT NULL,
  titulo         VARCHAR(160) NOT NULL,
  tipo           ENUM('tarefa','trabalho','exercicio','projeto','outro') NOT NULL DEFAULT 'tarefa',
  data_entrega   DATE NOT NULL,
  hora_entrega   TIME DEFAULT NULL,
  valor          DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  descricao      TEXT,
  criado_por     INT UNSIGNED DEFAULT NULL,
  criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_atividades_data (data_entrega),
  KEY idx_atividades_turma (turma_id),
  CONSTRAINT fk_atividades_turma FOREIGN KEY (turma_id)
    REFERENCES turmas (id) ON DELETE CASCADE,
  CONSTRAINT fk_atividades_professor FOREIGN KEY (professor_id)
    REFERENCES usuarios (id) ON DELETE SET NULL,
  CONSTRAINT fk_atividades_criador FOREIGN KEY (criado_por)
    REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Usuários de exemplo
--   admin@prosiga.edu  -> senha: admin123
--   prof@prosiga.edu   -> senha: prof1234
--   aluno@prosiga.edu  -> senha: aluno1234
-- ---------------------------------------------------------
INSERT INTO usuarios (nome, email, senha, tipo, matricula, setor) VALUES
  ('Secretaria Geral', 'admin@prosiga.edu',
   '$2y$10$iUfq4vr//dI259pEulxEqe89q93hyra4UvIaG49S/dX51FJKnvQx.', 'admin', 'ADM001', 'Secretaria'),
  ('Ana Beatriz Costa', 'prof@prosiga.edu',
   '$2y$10$0UVYq2Y/owOygJe3Kj13me9fKn4KX.UpdrcTewcEYBF8QGSPjFU0m', 'professor', '4521', 'Ciência da Computação'),
  ('João Pedro Silva', 'aluno@prosiga.edu',
   '$2y$10$EPM3aNywKx8346HTkINjLeGuNjHh6cmLmCV.Alc.DSwQyVD6x8sS2', 'aluno', '20261002', 'Engenharia de Software');

-- Os hashes acima correspondem respectivamente a: admin123, prof1234 e aluno1234.
-- Para gerar um novo hash: php -r "echo password_hash('sua_senha', PASSWORD_BCRYPT);"
