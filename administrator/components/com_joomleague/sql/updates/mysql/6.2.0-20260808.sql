CREATE TABLE IF NOT EXISTS `#__joomleague_profile_template_config` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_version_id` BIGINT UNSIGNED NOT NULL,
  `template_code` VARCHAR(100) NOT NULL,
  `schema_version` VARCHAR(50) NOT NULL,
  `params_json` LONGTEXT NOT NULL,
  `params_checksum` CHAR(64) NOT NULL,
  `published` TINYINT NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NULL DEFAULT NULL,
  `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out` BIGINT UNSIGNED NULL DEFAULT NULL,
  `checked_out_time` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jl_profile_template` (`profile_version_id`, `template_code`),
  KEY `idx_jl_profile_template_state` (`published`),
  CONSTRAINT `fk_jl_profile_template_version`
    FOREIGN KEY (`profile_version_id`) REFERENCES `#__joomleague_sport_profile_version` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
