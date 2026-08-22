CREATE TABLE IF NOT EXISTS `#__joomleague_project_round` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL,
  `project_id` BIGINT UNSIGNED NOT NULL, `stage_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL, `alias` VARCHAR(255) NOT NULL DEFAULT '', `code` VARCHAR(100) NOT NULL,
  `round_type` VARCHAR(100) NOT NULL DEFAULT 'standard', `sequence_number` INT UNSIGNED NOT NULL,
  `start_date` DATE NULL DEFAULT NULL, `end_date` DATE NULL DEFAULT NULL, `lifecycle_state` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `description` TEXT NULL, `metadata_json` LONGTEXT NULL, `published` TINYINT NOT NULL DEFAULT 0, `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out` BIGINT UNSIGNED NULL DEFAULT NULL, `checked_out_time` DATETIME NULL DEFAULT NULL, `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_project_round_uuid` (`uuid`), UNIQUE KEY `uq_jl_project_round_code` (`stage_id`, `code`),
  UNIQUE KEY `uq_jl_project_round_sequence` (`stage_id`, `sequence_number`), UNIQUE KEY `uq_jl_project_round_owner` (`id`, `project_id`),
  KEY `idx_jl_project_round_state` (`project_id`, `stage_id`, `published`, `ordering`), KEY `idx_jl_project_round_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_jl_project_round_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_project_round_stage` FOREIGN KEY (`stage_id`, `project_id`) REFERENCES `#__joomleague_project_stage` (`id`, `project_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_jl_project_round_sequence` CHECK (`sequence_number` > 0),
  CONSTRAINT `chk_jl_project_round_dates` CHECK (`end_date` IS NULL OR `start_date` IS NULL OR `end_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
