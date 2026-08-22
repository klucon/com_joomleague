ALTER TABLE `#__joomleague_match_lineup_member` ADD UNIQUE KEY `uq_jl_match_lineup_scope` (`id`, `match_id`, `match_participant_id`);
CREATE TABLE IF NOT EXISTS `#__joomleague_match_lineup_change` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL,
  `match_id` BIGINT UNSIGNED NOT NULL, `match_participant_id` BIGINT UNSIGNED NOT NULL,
  `outgoing_lineup_member_id` BIGINT UNSIGNED NOT NULL, `incoming_lineup_member_id` BIGINT UNSIGNED NOT NULL,
  `change_type` VARCHAR(50) NOT NULL DEFAULT 'substitution', `sequence_number` INT UNSIGNED NOT NULL,
  `phase_code` VARCHAR(100) NULL DEFAULT NULL, `phase_sequence` INT UNSIGNED NULL DEFAULT NULL,
  `clock_value` DECIMAL(30,9) NULL DEFAULT NULL, `clock_unit` VARCHAR(50) NULL DEFAULT NULL,
  `notes` TEXT NULL, `metadata_json` LONGTEXT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_match_lineup_change_uuid` (`uuid`),
  UNIQUE KEY `uq_jl_match_lineup_change_sequence` (`match_participant_id`, `sequence_number`),
  KEY `idx_jl_match_lineup_change_match` (`match_id`, `match_participant_id`, `sequence_number`),
  CONSTRAINT `fk_jl_lineup_change_outgoing` FOREIGN KEY (`outgoing_lineup_member_id`, `match_id`, `match_participant_id`) REFERENCES `#__joomleague_match_lineup_member` (`id`, `match_id`, `match_participant_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_jl_lineup_change_incoming` FOREIGN KEY (`incoming_lineup_member_id`, `match_id`, `match_participant_id`) REFERENCES `#__joomleague_match_lineup_member` (`id`, `match_id`, `match_participant_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_jl_lineup_change_members` CHECK (`outgoing_lineup_member_id` <> `incoming_lineup_member_id`),
  CONSTRAINT `chk_jl_lineup_change_sequence` CHECK (`sequence_number` > 0),
  CONSTRAINT `chk_jl_lineup_change_phase` CHECK (`phase_sequence` IS NULL OR `phase_sequence` > 0),
  CONSTRAINT `chk_jl_lineup_change_clock` CHECK (`clock_value` IS NULL OR `clock_value` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
