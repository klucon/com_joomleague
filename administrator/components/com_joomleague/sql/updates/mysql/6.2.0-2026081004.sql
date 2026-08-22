ALTER TABLE `#__joomleague_project_round` ADD UNIQUE KEY `uq_jl_project_round_scope` (`id`, `stage_id`, `project_id`);

CREATE TABLE IF NOT EXISTS `#__joomleague_project_match` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `project_id` BIGINT UNSIGNED NOT NULL, `stage_id` BIGINT UNSIGNED NOT NULL, `round_id` BIGINT UNSIGNED NOT NULL,
  `code` VARCHAR(100) NULL DEFAULT NULL, `match_number` VARCHAR(100) NULL DEFAULT NULL, `contest_type` VARCHAR(100) NOT NULL DEFAULT 'head_to_head',
  `scheduled_start` DATETIME NULL DEFAULT NULL, `timezone` VARCHAR(100) NULL DEFAULT NULL, `duration_minutes` INT UNSIGNED NULL DEFAULT NULL,
  `venue_id` BIGINT UNSIGNED NULL DEFAULT NULL, `attendance` BIGINT UNSIGNED NULL DEFAULT NULL, `status_code` VARCHAR(100) NOT NULL DEFAULT 'scheduled',
  `description` TEXT NULL, `metadata_json` LONGTEXT NULL, `published` TINYINT NOT NULL DEFAULT 0, `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0, `modified` DATETIME NULL DEFAULT NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out` BIGINT UNSIGNED NULL DEFAULT NULL, `checked_out_time` DATETIME NULL DEFAULT NULL, `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_project_match_uuid` (`uuid`), UNIQUE KEY `uq_jl_project_match_code` (`round_id`, `code`), UNIQUE KEY `uq_jl_project_match_owner` (`id`, `project_id`),
  KEY `idx_jl_project_match_schedule` (`project_id`, `scheduled_start`), KEY `idx_jl_project_match_round` (`round_id`, `status_code`, `ordering`), KEY `idx_jl_project_match_venue` (`venue_id`),
  CONSTRAINT `fk_jl_project_match_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_project_match_round` FOREIGN KEY (`round_id`, `stage_id`, `project_id`) REFERENCES `#__joomleague_project_round` (`id`, `stage_id`, `project_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_project_match_venue` FOREIGN KEY (`venue_id`) REFERENCES `#__joomleague_venue` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_match_participant` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `match_id` BIGINT UNSIGNED NOT NULL, `project_id` BIGINT UNSIGNED NOT NULL, `project_entry_id` BIGINT UNSIGNED NOT NULL,
  `role_code` VARCHAR(100) NOT NULL DEFAULT 'participant', `slot_number` INT UNSIGNED NOT NULL, `result_status` VARCHAR(100) NOT NULL DEFAULT 'scheduled', `result_rank` INT UNSIGNED NULL DEFAULT NULL,
  `metadata_json` LONGTEXT NULL, `published` TINYINT NOT NULL DEFAULT 1, `ordering` INT NOT NULL DEFAULT 0, `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0, `modified` DATETIME NULL DEFAULT NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_match_participant_uuid` (`uuid`), UNIQUE KEY `uq_jl_match_participant_entry` (`match_id`, `project_entry_id`), UNIQUE KEY `uq_jl_match_participant_slot` (`match_id`, `slot_number`),
  KEY `idx_jl_match_participant_entry` (`project_entry_id`, `project_id`),
  CONSTRAINT `fk_jl_match_participant_match` FOREIGN KEY (`match_id`, `project_id`) REFERENCES `#__joomleague_project_match` (`id`, `project_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_match_participant_entry` FOREIGN KEY (`project_entry_id`, `project_id`) REFERENCES `#__joomleague_project_entry` (`id`, `project_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_jl_match_participant_slot` CHECK (`slot_number` > 0), CONSTRAINT `chk_jl_match_participant_rank` CHECK (`result_rank` IS NULL OR `result_rank` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
