CREATE TABLE IF NOT EXISTS `#__joomleague_standing_freshness` (
  `project_id` BIGINT UNSIGNED NOT NULL, `stage_key` BIGINT UNSIGNED NOT NULL DEFAULT 0, `scope_code` VARCHAR(100) NOT NULL,
  `is_dirty` TINYINT UNSIGNED NOT NULL DEFAULT 1, `input_checksum` CHAR(64) NULL DEFAULT NULL,
  `dirty_at` DATETIME NULL DEFAULT NULL, `refreshed_at` DATETIME NULL DEFAULT NULL, `refreshed_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`project_id`, `stage_key`, `scope_code`),
  CONSTRAINT `fk_jl_standing_freshness_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_jl_standing_freshness_dirty` CHECK (`is_dirty` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
