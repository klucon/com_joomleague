CREATE TABLE IF NOT EXISTS `#__joomleague_match_duel` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `uuid` CHAR(36) NOT NULL,
 `match_id` BIGINT UNSIGNED NOT NULL,
 `sequence_number` INT NOT NULL,
 `format_code` VARCHAR(64) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_jl_duel_uuid` (`uuid`),
 UNIQUE KEY `uq_jl_duel_sequence` (`match_id`, `sequence_number`),
 UNIQUE KEY `uq_jl_duel_scope` (`id`, `match_id`),
 CONSTRAINT `fk_jl_duel_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_project_match` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_match_duel_member` (
 `duel_id` BIGINT UNSIGNED NOT NULL,
 `match_id` BIGINT UNSIGNED NOT NULL,
 `match_participant_id` BIGINT UNSIGNED NOT NULL,
 `lineup_member_id` BIGINT UNSIGNED NOT NULL,
 `ordering` INT NOT NULL,
 PRIMARY KEY (`duel_id`, `lineup_member_id`),
 UNIQUE KEY `uq_jl_duel_member_order` (`duel_id`, `match_participant_id`, `ordering`),
 CONSTRAINT `fk_jl_duel_member_duel` FOREIGN KEY (`duel_id`, `match_id`) REFERENCES `#__joomleague_match_duel` (`id`, `match_id`) ON DELETE CASCADE,
 CONSTRAINT `fk_jl_duel_member_lineup` FOREIGN KEY (`lineup_member_id`, `match_id`, `match_participant_id`) REFERENCES `#__joomleague_match_lineup_member` (`id`, `match_id`, `match_participant_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_match_encounter_revision` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `uuid` CHAR(36) NOT NULL,
 `match_id` BIGINT UNSIGNED NOT NULL,
 `payload_json` LONGTEXT NOT NULL,
 `checksum` CHAR(64) NOT NULL,
 `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_jl_encounter_revision_uuid` (`uuid`),
 KEY `idx_jl_encounter_revision_match` (`match_id`, `id`),
 CONSTRAINT `fk_jl_encounter_revision_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_project_match` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

