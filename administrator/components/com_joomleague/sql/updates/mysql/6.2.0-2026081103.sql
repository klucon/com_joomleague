CREATE TABLE IF NOT EXISTS `#__joomleague_match_lineup_member` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL,
  `match_id` BIGINT UNSIGNED NOT NULL, `match_participant_id` BIGINT UNSIGNED NOT NULL,
  `source_entry_member_id` BIGINT UNSIGNED NULL DEFAULT NULL, `person_id` BIGINT UNSIGNED NOT NULL,
  `member_person_type` VARCHAR(50) NOT NULL, `role_code` VARCHAR(100) NULL DEFAULT NULL,
  `shirt_number` VARCHAR(20) NULL DEFAULT NULL, `lineup_status` VARCHAR(50) NOT NULL DEFAULT 'available',
  `is_captain` TINYINT NOT NULL DEFAULT 0, `notes` TEXT NULL, `metadata_json` LONGTEXT NULL,
  `published` TINYINT NOT NULL DEFAULT 1, `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_match_lineup_uuid` (`uuid`),
  UNIQUE KEY `uq_jl_match_lineup_person` (`match_participant_id`, `person_id`),
  KEY `idx_jl_match_lineup_match` (`match_id`, `match_participant_id`, `member_person_type`, `ordering`),
  KEY `idx_jl_match_lineup_source` (`source_entry_member_id`),
  CONSTRAINT `fk_jl_match_lineup_participant` FOREIGN KEY (`match_participant_id`, `match_id`) REFERENCES `#__joomleague_match_participant` (`id`, `match_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_match_lineup_source` FOREIGN KEY (`source_entry_member_id`) REFERENCES `#__joomleague_project_entry_member` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_jl_match_lineup_person` FOREIGN KEY (`person_id`) REFERENCES `#__joomleague_person` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
