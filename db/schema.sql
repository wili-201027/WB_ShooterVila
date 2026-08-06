-- Schema SQL para VR_ShooterVila project
-- Crea la base de dades i la taula `scores` usada per la classificació

CREATE DATABASE IF NOT EXISTS vrgame_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vrgame_db;

CREATE TABLE IF NOT EXISTS scores (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  player_name VARCHAR(24) NOT NULL,
  score INT NOT NULL DEFAULT 0,
  games_played INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY ux_player_name (player_name),
  INDEX ix_score (score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example insert (opcional):
-- INSERT INTO scores (player_name, score, games_played) VALUES ('ProGamer', 1200, 3);
