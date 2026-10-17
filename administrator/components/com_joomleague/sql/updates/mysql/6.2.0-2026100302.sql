CREATE TABLE IF NOT EXISTS `#__joomleague_sport_category` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `uuid` CHAR(36) NOT NULL,
 `code` VARCHAR(100) NOT NULL,
 `parent_id` BIGINT UNSIGNED NULL DEFAULT NULL,
 `name` VARCHAR(255) NOT NULL,
 `description` TEXT NOT NULL,
 `published` TINYINT NOT NULL DEFAULT 1,
 `ordering` INT NOT NULL DEFAULT 0,
 `created` DATETIME NOT NULL,
 `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 `modified` DATETIME NULL DEFAULT NULL,
 `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_jl_sport_category_uuid` (`uuid`),
 UNIQUE KEY `uq_jl_sport_category_code` (`code`),
 KEY `idx_jl_sport_category_parent` (`parent_id`),
 CONSTRAINT `fk_jl_sport_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `#__joomleague_sport_category` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__joomleague_sport_category_lock` (
 `id` INT NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
INSERT INTO `#__joomleague_sport_category_lock` (`id`) VALUES (1);
CREATE TABLE IF NOT EXISTS `#__joomleague_sport_profile_category` (
 `profile_id` BIGINT UNSIGNED NOT NULL,
 `category_id` BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY (`profile_id`, `category_id`),
 KEY `idx_jl_profile_category_category` (`category_id`),
 CONSTRAINT `fk_jl_profile_category_profile` FOREIGN KEY (`profile_id`) REFERENCES `#__joomleague_sport_profile` (`id`) ON DELETE CASCADE,
 CONSTRAINT `fk_jl_profile_category_category` FOREIGN KEY (`category_id`) REFERENCES `#__joomleague_sport_category` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
