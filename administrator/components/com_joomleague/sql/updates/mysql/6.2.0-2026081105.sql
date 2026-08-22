CREATE TABLE IF NOT EXISTS `#__joomleague_project_actor_role` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `project_id` BIGINT UNSIGNED NOT NULL,
  `actor_kind` VARCHAR(20) NOT NULL, `person_id` BIGINT UNSIGNED NULL DEFAULT NULL, `team_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `role_code` VARCHAR(100) NOT NULL, `person_type` VARCHAR(100) NOT NULL, `valid_from` DATE NULL DEFAULT NULL, `valid_until` DATE NULL DEFAULT NULL,
  `lifecycle_state` VARCHAR(30) NOT NULL DEFAULT 'active', `notes` TEXT NULL, `metadata_json` LONGTEXT NULL,
  `published` TINYINT NOT NULL DEFAULT 1, `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_project_actor_role_uuid` (`uuid`),
  KEY `idx_jl_project_actor_role_project` (`project_id`, `person_type`, `published`, `ordering`),
  KEY `idx_jl_project_actor_role_person` (`person_id`, `valid_from`, `valid_until`), KEY `idx_jl_project_actor_role_team` (`team_id`, `valid_from`, `valid_until`),
  CONSTRAINT `fk_jl_project_actor_role_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_project_actor_role_person` FOREIGN KEY (`person_id`) REFERENCES `#__joomleague_person` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_jl_project_actor_role_team` FOREIGN KEY (`team_id`) REFERENCES `#__joomleague_team` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_jl_project_actor_role_actor` CHECK ((`actor_kind` = 'person' AND `person_id` IS NOT NULL AND `team_id` IS NULL) OR (`actor_kind` = 'team' AND `team_id` IS NOT NULL AND `person_id` IS NULL)),
  CONSTRAINT `chk_jl_project_actor_role_dates` CHECK (`valid_until` IS NULL OR `valid_from` IS NULL OR `valid_until` >= `valid_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__joomleague_match_actor_role` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `match_id` BIGINT UNSIGNED NOT NULL, `project_id` BIGINT UNSIGNED NOT NULL,
  `source_project_actor_role_id` BIGINT UNSIGNED NULL DEFAULT NULL, `actor_kind` VARCHAR(20) NOT NULL,
  `person_id` BIGINT UNSIGNED NULL DEFAULT NULL, `team_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `role_code` VARCHAR(100) NOT NULL, `person_type` VARCHAR(100) NOT NULL, `display_name_snapshot` VARCHAR(255) NOT NULL,
  `notes` TEXT NULL, `metadata_json` LONGTEXT NULL, `published` TINYINT NOT NULL DEFAULT 1, `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_match_actor_role_uuid` (`uuid`), UNIQUE KEY `uq_jl_match_actor_role_source` (`match_id`, `source_project_actor_role_id`),
  KEY `idx_jl_match_actor_role_match` (`match_id`, `person_type`, `ordering`), KEY `idx_jl_match_actor_role_project` (`project_id`),
  KEY `idx_jl_match_actor_role_person` (`person_id`), KEY `idx_jl_match_actor_role_team` (`team_id`),
  CONSTRAINT `fk_jl_match_actor_role_match` FOREIGN KEY (`match_id`, `project_id`) REFERENCES `#__joomleague_project_match` (`id`, `project_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_match_actor_role_source` FOREIGN KEY (`source_project_actor_role_id`) REFERENCES `#__joomleague_project_actor_role` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_jl_match_actor_role_person` FOREIGN KEY (`person_id`) REFERENCES `#__joomleague_person` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_jl_match_actor_role_team` FOREIGN KEY (`team_id`) REFERENCES `#__joomleague_team` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_jl_match_actor_role_actor` CHECK ((`actor_kind` = 'person' AND `person_id` IS NOT NULL AND `team_id` IS NULL) OR (`actor_kind` = 'team' AND `team_id` IS NOT NULL AND `person_id` IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
