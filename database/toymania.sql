-- Toy Mania
-- Projeto Integrador 2026
-- Banco de dados do painel administrativo
--
-- Como importar, na pasta do projeto:
--   mysql -u root -p < database/toymania.sql
--
-- Para reinstalar do zero:
--   mysql -u root -p -e "DROP DATABASE IF EXISTS toymania_db;"
--   mysql -u root -p < database/toymania.sql
--
-- O primeiro funcionário NÃO é criado aqui de propósito.
-- Assim o sistema não fica com uma senha padrão. O cadastro inicial
-- é feito pela tela setup.php, que só funciona enquanto não existir ninguém.

CREATE DATABASE IF NOT EXISTS toymania_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE toymania_db;

CREATE TABLE IF NOT EXISTS funcionarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome_completo VARCHAR(150) NOT NULL,
  email VARCHAR(180) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  telefone VARCHAR(20) NULL,
  cargo VARCHAR(80) NOT NULL,
  status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_funcionarios_email (email),
  KEY idx_funcionarios_nome (nome_completo),
  KEY idx_funcionarios_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  descricao TEXT NULL,
  status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_categorias_nome (nome),
  KEY idx_categorias_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produtos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(180) NOT NULL,
  descricao TEXT NOT NULL,
  categoria_id INT UNSIGNED NOT NULL,
  preco DECIMAL(10,2) NOT NULL,
  faixa_etaria VARCHAR(20) NOT NULL,
  estoque INT UNSIGNED NOT NULL DEFAULT 0,
  marca VARCHAR(100) NULL,
  codigo VARCHAR(60) NOT NULL,
  status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  funcionario_id INT UNSIGNED NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_produtos_codigo (codigo),
  KEY idx_produtos_nome (nome),
  KEY idx_produtos_faixa (faixa_etaria),
  KEY idx_produtos_status (status),
  KEY idx_produtos_preco (preco),
  KEY idx_produtos_criado (criado_em),
  CONSTRAINT fk_produtos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_produtos_funcionario
    FOREIGN KEY (funcionario_id) REFERENCES funcionarios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_produtos_preco CHECK (preco > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produto_imagens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  produto_id INT UNSIGNED NOT NULL,
  caminho VARCHAR(255) NOT NULL,
  principal TINYINT(1) NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_imagens_produto (produto_id, principal),
  CONSTRAINT fk_imagens_produto
    FOREIGN KEY (produto_id) REFERENCES produtos (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_imagem_principal CHECK (principal IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorias iniciais para a loja já abrir com o catálogo organizado.
-- Não há funcionário nem senha neste arquivo.
INSERT IGNORE INTO categorias (nome, descricao, status) VALUES
  ('Bonecas', 'Bonecas, acessórios e conjuntos para brincar de imaginar.', 'ativo'),
  ('Carrinhos', 'Carrinhos, pistas e veículos infantis.', 'ativo'),
  ('Jogos educativos', 'Jogos que estimulam o raciocínio e a aprendizagem.', 'ativo'),
  ('Brinquedos de montar', 'Blocos, peças de encaixe e conjuntos de construção.', 'ativo'),
  ('Pelúcias', 'Bichos de pelúcia e naninhas.', 'ativo'),
  ('Jogos de tabuleiro', 'Jogos de mesa para jogar em família.', 'ativo'),
  ('Brinquedos para bebês', 'Brinquedos sensoriais e itens para a primeira infância.', 'ativo');
