ALTER TABLE `#__joomleague_stage_entry` ADD COLUMN `manual_assignment` TINYINT NOT NULL DEFAULT 1 AFTER `metadata_json`;

CREATE TABLE IF NOT EXISTS `#__joomleague_stage_transition_run` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `transition_id` BIGINT UNSIGNED NOT NULL, `project_id` BIGINT UNSIGNED NOT NULL,
  `input_checksum` CHAR(64) NOT NULL, `selector_snapshot_json` LONGTEXT NOT NULL, `resolved_entries_json` LONGTEXT NOT NULL, `resolved_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'applied', `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_stage_transition_run_uuid` (`uuid`), UNIQUE KEY `uq_jl_stage_transition_run_input` (`transition_id`, `input_checksum`), KEY `idx_jl_stage_transition_run_project` (`project_id`, `created`),
  CONSTRAINT `fk_jl_stage_transition_run_transition` FOREIGN KEY (`transition_id`) REFERENCES `#__joomleague_stage_transition` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_stage_transition_run_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_jl_stage_transition_run_status` CHECK (`status` IN ('applied'))
);

CREATE TABLE IF NOT EXISTS `#__joomleague_stage_transition_assignment` (
  `transition_id` BIGINT UNSIGNED NOT NULL, `target_stage_id` BIGINT UNSIGNED NOT NULL, `project_entry_id` BIGINT UNSIGNED NOT NULL, `project_id` BIGINT UNSIGNED NOT NULL,
  `run_id` BIGINT UNSIGNED NOT NULL, `target_seed` INT UNSIGNED NULL DEFAULT NULL, `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`transition_id`, `project_entry_id`), KEY `idx_jl_stage_transition_assignment_target` (`target_stage_id`, `project_id`, `project_entry_id`), KEY `idx_jl_stage_transition_assignment_run` (`run_id`),
  CONSTRAINT `fk_jl_stage_transition_assignment_transition` FOREIGN KEY (`transition_id`) REFERENCES `#__joomleague_stage_transition` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_stage_transition_assignment_stage` FOREIGN KEY (`target_stage_id`, `project_id`) REFERENCES `#__joomleague_project_stage` (`id`, `project_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_stage_transition_assignment_entry` FOREIGN KEY (`project_entry_id`, `project_id`) REFERENCES `#__joomleague_project_entry` (`id`, `project_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_stage_transition_assignment_run` FOREIGN KEY (`run_id`) REFERENCES `#__joomleague_stage_transition_run` (`id`) ON DELETE CASCADE
);
