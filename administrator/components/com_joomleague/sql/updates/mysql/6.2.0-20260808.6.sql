CREATE TABLE IF NOT EXISTS `#__joomleague_sport_position` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `sport_type_id` BIGINT UNSIGNED NOT NULL, `source_profile_version_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(100) NOT NULL, `name` VARCHAR(255) NOT NULL DEFAULT '', `name_key` VARCHAR(255) NULL, `person_type` VARCHAR(100) NOT NULL, `lineup_group` VARCHAR(100) NULL,
  `parent_id` BIGINT UNSIGNED NULL, `has_events` TINYINT NULL, `has_statistics` TINYINT NULL, `source` VARCHAR(30) NOT NULL DEFAULT 'local', `source_checksum` CHAR(64) NULL,
  `published` TINYINT NOT NULL DEFAULT 1, `ordering` INT NOT NULL DEFAULT 0, `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0, `checked_out` BIGINT UNSIGNED NULL, `checked_out_time` DATETIME NULL, `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_sport_position_uuid` (`uuid`), UNIQUE KEY `uq_jl_sport_position_code` (`sport_type_id`,`code`), KEY `idx_jl_sport_position_parent` (`parent_id`), KEY `idx_jl_sport_position_state` (`published`,`ordering`),
  CONSTRAINT `fk_jl_sport_position_type` FOREIGN KEY (`sport_type_id`) REFERENCES `#__joomleague_sport_type` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_sport_position_profile` FOREIGN KEY (`source_profile_version_id`) REFERENCES `#__joomleague_sport_profile_version` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_jl_sport_position_parent` FOREIGN KEY (`parent_id`) REFERENCES `#__joomleague_sport_position` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_event_type` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `sport_type_id` BIGINT UNSIGNED NOT NULL, `source_profile_version_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(100) NOT NULL, `name` VARCHAR(255) NOT NULL DEFAULT '', `name_key` VARCHAR(255) NULL, `person_type` VARCHAR(100) NULL, `timeline` TINYINT NOT NULL DEFAULT 0,
  `direction` SMALLINT NOT NULL DEFAULT 0, `affects_score` TINYINT NOT NULL DEFAULT 0, `score_delta` DECIMAL(12,4) NULL, `score_target` VARCHAR(30) NULL,
  `requires_second_person` TINYINT NOT NULL DEFAULT 0, `leads_to_suspension` TINYINT NOT NULL DEFAULT 0, `system_event` TINYINT NOT NULL DEFAULT 0,
  `source` VARCHAR(30) NOT NULL DEFAULT 'local', `source_checksum` CHAR(64) NULL, `metadata_json` LONGTEXT NULL, `published` TINYINT NOT NULL DEFAULT 1, `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0, `modified` DATETIME NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out` BIGINT UNSIGNED NULL, `checked_out_time` DATETIME NULL, `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_event_type_uuid` (`uuid`), UNIQUE KEY `uq_jl_event_type_code` (`sport_type_id`,`code`), KEY `idx_jl_event_type_state` (`published`,`ordering`),
  CONSTRAINT `fk_jl_event_type_sport_type` FOREIGN KEY (`sport_type_id`) REFERENCES `#__joomleague_sport_type` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_event_type_profile` FOREIGN KEY (`source_profile_version_id`) REFERENCES `#__joomleague_sport_profile_version` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_statistic` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `sport_type_id` BIGINT UNSIGNED NOT NULL, `source_profile_version_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(100) NOT NULL, `name` VARCHAR(255) NOT NULL DEFAULT '', `name_key` VARCHAR(255) NULL, `abbreviation_key` VARCHAR(255) NULL,
  `statistic_type` VARCHAR(100) NOT NULL DEFAULT 'basic', `scope` VARCHAR(100) NOT NULL, `value_type` VARCHAR(100) NOT NULL DEFAULT 'integer', `calculation_source` VARCHAR(100) NOT NULL DEFAULT 'manual',
  `source` VARCHAR(30) NOT NULL DEFAULT 'local', `source_checksum` CHAR(64) NULL, `metadata_json` LONGTEXT NULL, `published` TINYINT NOT NULL DEFAULT 1, `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0, `modified` DATETIME NULL, `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out` BIGINT UNSIGNED NULL, `checked_out_time` DATETIME NULL, `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_statistic_uuid` (`uuid`), UNIQUE KEY `uq_jl_statistic_code` (`sport_type_id`,`code`), KEY `idx_jl_statistic_state` (`published`,`ordering`),
  CONSTRAINT `fk_jl_statistic_sport_type` FOREIGN KEY (`sport_type_id`) REFERENCES `#__joomleague_sport_type` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_statistic_profile` FOREIGN KEY (`source_profile_version_id`) REFERENCES `#__joomleague_sport_profile_version` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
