ALTER TABLE `#__joomleague_project_stage`
  ADD COLUMN `entry_selection_mode` VARCHAR(30) NOT NULL DEFAULT 'inherit_project' AFTER `stage_type`,
  ADD UNIQUE KEY `uq_jl_project_stage_owner` (`id`, `project_id`),
  ADD CONSTRAINT `chk_jl_project_stage_entry_mode` CHECK (`entry_selection_mode` IN ('inherit_project', 'explicit'));

ALTER TABLE `#__joomleague_project_entry`
  ADD UNIQUE KEY `uq_jl_project_entry_owner` (`id`, `project_id`);

CREATE TABLE IF NOT EXISTS `#__joomleague_stage_entry` (
  `stage_id` BIGINT UNSIGNED NOT NULL,
  `entry_id` BIGINT UNSIGNED NOT NULL,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  `seed_number` INT UNSIGNED NULL DEFAULT NULL,
  `metadata_json` LONGTEXT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`stage_id`, `entry_id`),
  KEY `idx_jl_stage_entry_entry` (`entry_id`, `project_id`),
  CONSTRAINT `fk_jl_stage_entry_stage` FOREIGN KEY (`stage_id`, `project_id`) REFERENCES `#__joomleague_project_stage` (`id`, `project_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_stage_entry_entry` FOREIGN KEY (`entry_id`, `project_id`) REFERENCES `#__joomleague_project_entry` (`id`, `project_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
