-- JoomLeague 6.2 canonical MariaDB/MySQL foundation schema.

CREATE TABLE IF NOT EXISTS `#__joomleague_sport_profile` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(100) NOT NULL,
  `name_key` VARCHAR(255) NOT NULL,
  `description_key` VARCHAR(255) NOT NULL,
  `published` TINYINT NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jl_sport_profile_code` (`code`),
  KEY `idx_jl_sport_profile_published` (`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_sport_profile_version` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` BIGINT UNSIGNED NOT NULL,
  `schema_version` VARCHAR(50) NOT NULL,
  `profile_version` VARCHAR(50) NOT NULL,
  `payload_json` LONGTEXT NOT NULL,
  `payload_checksum` CHAR(64) NOT NULL,
  `source` VARCHAR(50) NOT NULL DEFAULT 'bundled',
  `state` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jl_profile_version` (`profile_id`, `profile_version`),
  UNIQUE KEY `uq_jl_profile_payload` (`profile_id`, `payload_checksum`),
  KEY `idx_jl_profile_version_state` (`state`),
  CONSTRAINT `fk_jl_profile_version_profile`
    FOREIGN KEY (`profile_id`) REFERENCES `#__joomleague_sport_profile` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_sport_type` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_version_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `code` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(255) NOT NULL DEFAULT '',
  `overrides_json` LONGTEXT NULL,
  `published` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out` BIGINT UNSIGNED NULL DEFAULT NULL,
  `checked_out_time` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jl_sport_type_code` (`code`),
  KEY `idx_jl_sport_type_profile` (`profile_version_id`),
  KEY `idx_jl_sport_type_state` (`published`, `ordering`),
  CONSTRAINT `fk_jl_sport_type_profile_version`
    FOREIGN KEY (`profile_version_id`) REFERENCES `#__joomleague_sport_profile_version` (`id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_migration_batch` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_uuid` CHAR(36) NOT NULL,
  `source_product` VARCHAR(100) NOT NULL,
  `source_version` VARCHAR(100) NOT NULL,
  `source_fingerprint` CHAR(64) NOT NULL,
  `state` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `options_json` LONGTEXT NULL,
  `total_records` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `processed_records` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `imported_records` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `skipped_records` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `failed_records` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `started` DATETIME NULL DEFAULT NULL,
  `finished` DATETIME NULL DEFAULT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jl_migration_batch_uuid` (`batch_uuid`),
  UNIQUE KEY `uq_jl_migration_source` (`source_product`, `source_fingerprint`),
  KEY `idx_jl_migration_batch_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_migration_record` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `source_table` VARCHAR(191) NOT NULL,
  `source_identity_json` TEXT NOT NULL,
  `source_identity_hash` CHAR(64) NOT NULL,
  `source_payload_json` LONGTEXT NULL,
  `source_payload_checksum` CHAR(64) NULL,
  `target_entity` VARCHAR(100) NULL,
  `target_identity` VARCHAR(191) NULL,
  `outcome` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `message_code` VARCHAR(191) NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jl_migration_record` (`batch_id`, `source_table`, `source_identity_hash`),
  KEY `idx_jl_migration_record_outcome` (`batch_id`, `outcome`),
  KEY `idx_jl_migration_record_target` (`target_entity`, `target_identity`),
  CONSTRAINT `fk_jl_migration_record_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `#__joomleague_migration_batch` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_migration_issue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `record_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `severity` VARCHAR(20) NOT NULL,
  `issue_code` VARCHAR(191) NOT NULL,
  `message` TEXT NOT NULL,
  `context_json` LONGTEXT NULL,
  `state` VARCHAR(30) NOT NULL DEFAULT 'open',
  `resolution_json` LONGTEXT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved` DATETIME NULL DEFAULT NULL,
  `resolved_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_jl_migration_issue_batch` (`batch_id`, `state`, `severity`),
  KEY `idx_jl_migration_issue_record` (`record_id`),
  CONSTRAINT `fk_jl_migration_issue_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `#__joomleague_migration_batch` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_jl_migration_issue_record`
    FOREIGN KEY (`record_id`) REFERENCES `#__joomleague_migration_record` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
