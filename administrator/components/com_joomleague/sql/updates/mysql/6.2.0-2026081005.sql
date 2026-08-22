ALTER TABLE `#__joomleague_match_participant` ADD UNIQUE KEY `uq_jl_match_participant_scope` (`id`, `match_id`);

CREATE TABLE IF NOT EXISTS `#__joomleague_match_result` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `match_id` BIGINT UNSIGNED NOT NULL, `result_type` VARCHAR(100) NOT NULL, `status_code` VARCHAR(100) NOT NULL DEFAULT 'draft', `outcome_code` VARCHAR(100) NULL DEFAULT NULL,
  `finalized_at` DATETIME NULL DEFAULT NULL, `notes` TEXT NULL, `metadata_json` LONGTEXT NULL, `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0, `modified` DATETIME NULL DEFAULT NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_match_result_uuid` (`uuid`), UNIQUE KEY `uq_jl_match_result_match` (`match_id`), CONSTRAINT `fk_jl_match_result_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_project_match` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_match_score_segment` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `match_id` BIGINT UNSIGNED NOT NULL, `parent_id` BIGINT UNSIGNED NULL DEFAULT NULL, `level_code` VARCHAR(100) NOT NULL, `sequence_number` INT UNSIGNED NOT NULL, `status_code` VARCHAR(100) NOT NULL DEFAULT 'completed', `metadata_json` LONGTEXT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0, `modified` DATETIME NULL DEFAULT NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_match_score_segment_uuid` (`uuid`), UNIQUE KEY `uq_jl_match_score_segment_position` (`match_id`, `parent_id`, `level_code`, `sequence_number`), UNIQUE KEY `uq_jl_match_score_segment_scope` (`id`, `match_id`), KEY `idx_jl_match_score_segment_parent` (`parent_id`, `match_id`),
  CONSTRAINT `fk_jl_match_score_segment_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_project_match` (`id`) ON DELETE CASCADE, CONSTRAINT `fk_jl_match_score_segment_parent` FOREIGN KEY (`parent_id`, `match_id`) REFERENCES `#__joomleague_match_score_segment` (`id`, `match_id`) ON DELETE CASCADE, CONSTRAINT `chk_jl_match_score_segment_sequence` CHECK (`sequence_number` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_match_score_value` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `match_id` BIGINT UNSIGNED NOT NULL, `segment_id` BIGINT UNSIGNED NOT NULL, `participant_id` BIGINT UNSIGNED NOT NULL, `numeric_value` DECIMAL(30,9) NULL DEFAULT NULL, `text_value` VARCHAR(255) NULL DEFAULT NULL, `status_code` VARCHAR(100) NULL DEFAULT NULL, `result_rank` INT UNSIGNED NULL DEFAULT NULL, `metadata_json` LONGTEXT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0, `modified` DATETIME NULL DEFAULT NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_match_score_value_uuid` (`uuid`), UNIQUE KEY `uq_jl_match_score_value_participant` (`segment_id`, `participant_id`), KEY `idx_jl_match_score_value_match` (`match_id`, `result_rank`), KEY `idx_jl_match_score_value_participant` (`participant_id`, `match_id`),
  CONSTRAINT `fk_jl_match_score_value_segment` FOREIGN KEY (`segment_id`, `match_id`) REFERENCES `#__joomleague_match_score_segment` (`id`, `match_id`) ON DELETE CASCADE, CONSTRAINT `fk_jl_match_score_value_participant` FOREIGN KEY (`participant_id`, `match_id`) REFERENCES `#__joomleague_match_participant` (`id`, `match_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_jl_match_score_value_rank` CHECK (`result_rank` IS NULL OR `result_rank` > 0), CONSTRAINT `chk_jl_match_score_value_payload` CHECK (`numeric_value` IS NOT NULL OR `text_value` IS NOT NULL OR `status_code` IS NOT NULL OR `result_rank` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
