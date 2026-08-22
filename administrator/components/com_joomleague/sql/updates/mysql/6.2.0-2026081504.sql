CREATE TABLE IF NOT EXISTS `#__joomleague_schedule_generation` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `project_id` BIGINT UNSIGNED NOT NULL, `stage_id` BIGINT UNSIGNED NOT NULL,
  `input_checksum` CHAR(64) NOT NULL, `options_json` LONGTEXT NOT NULL, `round_count` INT UNSIGNED NOT NULL DEFAULT 0, `match_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `conflict_count` INT UNSIGNED NOT NULL DEFAULT 0, `status` VARCHAR(30) NOT NULL DEFAULT 'applied', `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_schedule_generation_uuid` (`uuid`), UNIQUE KEY `uq_jl_schedule_generation_input` (`stage_id`, `input_checksum`), KEY `idx_jl_schedule_generation_project` (`project_id`, `created`),
  CONSTRAINT `fk_jl_schedule_generation_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_schedule_generation_stage` FOREIGN KEY (`stage_id`, `project_id`) REFERENCES `#__joomleague_project_stage` (`id`, `project_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_jl_schedule_generation_status` CHECK (`status` IN ('applied'))
);
CREATE TABLE IF NOT EXISTS `#__joomleague_schedule_generation_match` (
  `generation_id` BIGINT UNSIGNED NOT NULL, `match_id` BIGINT UNSIGNED NOT NULL, `project_id` BIGINT UNSIGNED NOT NULL, `round_sequence` INT UNSIGNED NOT NULL, `match_sequence` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`generation_id`, `match_id`), UNIQUE KEY `uq_jl_schedule_generation_match` (`match_id`),
  CONSTRAINT `fk_jl_schedule_generation_match_generation` FOREIGN KEY (`generation_id`) REFERENCES `#__joomleague_schedule_generation` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_schedule_generation_match_match` FOREIGN KEY (`match_id`, `project_id`) REFERENCES `#__joomleague_project_match` (`id`, `project_id`) ON DELETE CASCADE
);
