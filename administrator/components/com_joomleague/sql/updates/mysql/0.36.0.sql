CREATE TABLE IF NOT EXISTS `#__joomleague_prediction_game` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NOT NULL,
	`name` varchar(128) NOT NULL DEFAULT '',
	`deadline_minutes` INT NOT NULL DEFAULT 0,
	`points_exact` INT NOT NULL DEFAULT 3,
	`points_tendency` INT NOT NULL DEFAULT 1,
	`points_goal_diff` INT NOT NULL DEFAULT 0,
	`show_ranking` TINYINT NOT NULL DEFAULT 1,
	`published` TINYINT NOT NULL DEFAULT 1,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`created` DATETIME NULL DEFAULT NULL,
	`created_by` INT NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `project_id` (`project_id`),
	KEY `published` (`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_prediction_tip` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`game_id` INT NOT NULL,
	`match_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`home_score` INT NULL DEFAULT NULL,
	`away_score` INT NULL DEFAULT NULL,
	`points` INT NOT NULL DEFAULT 0,
	`calculated` TINYINT NOT NULL DEFAULT 0,
	`locked` TINYINT NOT NULL DEFAULT 0,
	`created` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `game_match_user` (`game_id`,`match_id`,`user_id`),
	KEY `match_id` (`match_id`),
	KEY `user_id` (`user_id`),
	KEY `calculated` (`calculated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomleague_prediction_score` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`game_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`round_id` INT NULL DEFAULT NULL,
	`tips` INT NOT NULL DEFAULT 0,
	`points` INT NOT NULL DEFAULT 0,
	`exact_hits` INT NOT NULL DEFAULT 0,
	`tendency_hits` INT NOT NULL DEFAULT 0,
	`modified` datetime DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `game_user_round` (`game_id`,`user_id`,`round_id`),
	KEY `user_id` (`user_id`),
	KEY `round_id` (`round_id`),
	KEY `points` (`points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
