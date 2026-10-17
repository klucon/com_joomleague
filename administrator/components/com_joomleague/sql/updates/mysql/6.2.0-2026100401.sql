CREATE TABLE IF NOT EXISTS `#__joomleague_sport_variant` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `uuid` CHAR(36) NOT NULL,
 `profile_id` BIGINT UNSIGNED NOT NULL,
 `code` VARCHAR(100) NOT NULL,
 `name` VARCHAR(255) NOT NULL,
 `published` TINYINT NOT NULL DEFAULT 0,
 `created` DATETIME NOT NULL,
 `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_jl_variant_uuid` (`uuid`),
 UNIQUE KEY `uq_jl_variant_code` (`profile_id`, `code`),
 CONSTRAINT `fk_jl_variant_profile` FOREIGN KEY (`profile_id`) REFERENCES `#__joomleague_sport_profile` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_sport_variant_version` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `variant_id` BIGINT UNSIGNED NOT NULL,
 `base_profile_version_id` BIGINT UNSIGNED NOT NULL,
 `revision` VARCHAR(50) NOT NULL,
 `overrides_json` LONGTEXT NOT NULL,
 `overrides_checksum` CHAR(64) NOT NULL,
 `base_checksum` CHAR(64) NOT NULL,
 `payload_json` LONGTEXT NOT NULL,
 `payload_checksum` CHAR(64) NOT NULL,
 `created` DATETIME NOT NULL,
 `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_jl_variant_revision` (`variant_id`, `revision`),
 KEY `idx_jl_variant_base` (`base_profile_version_id`),
 CONSTRAINT `fk_jl_variant_revision_variant` FOREIGN KEY (`variant_id`) REFERENCES `#__joomleague_sport_variant` (`id`) ON DELETE RESTRICT,
 CONSTRAINT `fk_jl_variant_revision_base` FOREIGN KEY (`base_profile_version_id`) REFERENCES `#__joomleague_sport_profile_version` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
