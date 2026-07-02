CREATE TABLE IF NOT EXISTS `#__joomleague_sports_type` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`icon` VARCHAR(255) NULL DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 1,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` DATETIME NULL DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `idx_joomleague_sports_type_name` (`name`),
	KEY `idx_joomleague_sports_type_published_ordering` (`published`, `ordering`),
	KEY `idx_joomleague_sports_type_checked_out` (`checked_out`),
	KEY `idx_joomleague_sports_type_modified_by` (`modified_by`),
	CONSTRAINT `fk_joomleague_sports_type_checked_out`
		FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	CONSTRAINT `fk_joomleague_sports_type_modified_by`
		FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
