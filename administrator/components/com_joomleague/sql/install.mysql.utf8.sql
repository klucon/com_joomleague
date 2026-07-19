-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_club`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_club` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` varchar(100) NOT NULL DEFAULT '',
	`alias` varchar(255) NOT NULL DEFAULT '',
	`address` varchar(100) NOT NULL DEFAULT '',
	`zipcode` varchar(10) NOT NULL DEFAULT '',
	`location` varchar(50) NOT NULL DEFAULT '',
	`state` varchar(50) NOT NULL DEFAULT '',
	`country` varchar(6) DEFAULT NULL,
	`latitude` DECIMAL(10,7) NULL DEFAULT NULL,
	`longitude` DECIMAL(10,7) NULL DEFAULT NULL,
	`founded` DATE NULL DEFAULT NULL,
	`phone` varchar(20) NOT NULL DEFAULT '',
	`fax` varchar(20) NOT NULL DEFAULT '',
	`email` varchar(255) NOT NULL DEFAULT '',
	`website` varchar(250) NOT NULL DEFAULT '',
	`president` varchar(50) NOT NULL DEFAULT '',
	`manager` varchar(50) NOT NULL DEFAULT '',
	`logo_big` varchar(255) NOT NULL DEFAULT '',
	`logo_middle` varchar(255) NOT NULL DEFAULT '',
	`logo_small` varchar(255) NOT NULL DEFAULT '',
	`standard_playground` INT NULL DEFAULT NULL,
	`info` text,
	`notes` text,
	`extended` text,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`dissolved` DATE NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	KEY `country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_division`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_division` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NULL DEFAULT NULL,
	`name` varchar(75) NOT NULL DEFAULT '',
	`alias` varchar(75) NOT NULL DEFAULT '',
	`shortname` varchar(10) DEFAULT NULL,
	`notes` text,
	`parent_id` INT DEFAULT NULL,
	`picture` varchar(128) NOT NULL DEFAULT '',
	`published` TINYINT NOT NULL DEFAULT 1,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `project_id` (`project_id`),
	KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_eventtype`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_eventtype` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` varchar(75) NOT NULL DEFAULT '',
	`alias` varchar(75) NOT NULL DEFAULT '',
	`icon` varchar(128) NOT NULL DEFAULT '',
	`parent` INT NULL DEFAULT NULL,
	`splitt` TINYINT NOT NULL DEFAULT 0,
	`direction` char(4) NOT NULL DEFAULT 'DESC',
	`double` TINYINT NOT NULL DEFAULT 0,
	`suspension` TINYINT NOT NULL DEFAULT 0,
	`sports_type_id` INT NULL DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 1,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`,`parent`,`sports_type_id`),
	KEY `sports_type_id` (`sports_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_league`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_league` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` varchar(75) NOT NULL DEFAULT '',
	`short_name` varchar(15) NOT NULL DEFAULT '',
	`middle_name` varchar(25) NOT NULL DEFAULT '',
	`alias` varchar(75) NOT NULL DEFAULT '',
	`country` varchar(6) DEFAULT NULL,
	`extended` text,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	KEY `country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_match`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_match` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`round_id` INT NULL DEFAULT NULL,
	`article_id` INT NULL DEFAULT NULL,
	`match_number` varchar(10) DEFAULT NULL,
	`projectteam1_id` INT NULL DEFAULT NULL,
	`projectteam2_id` INT NULL DEFAULT NULL,
	`playground_id` INT DEFAULT NULL,
	`match_date` DATETIME NULL DEFAULT NULL,
	`time_present` time DEFAULT NULL,
	`team1_result` float DEFAULT NULL,
	`team2_result` float DEFAULT NULL,
	`team1_bonus` INT DEFAULT NULL,
	`team2_bonus` INT DEFAULT NULL,
	`team1_legs` float DEFAULT NULL,
	`team2_legs` float DEFAULT NULL,
	`team1_result_split` varchar(64) DEFAULT NULL,
	`team2_result_split` varchar(64) DEFAULT NULL,
	`match_result_type` TINYINT NOT NULL DEFAULT 0,
	`team_won` TINYINT NOT NULL DEFAULT 0,
	`team1_result_ot` float DEFAULT NULL,
	`team2_result_ot` float DEFAULT NULL,
	`team1_result_so` float DEFAULT NULL,
	`team2_result_so` float DEFAULT NULL,
	`alt_decision` TINYINT NOT NULL DEFAULT 0,
	`team1_result_decision` float DEFAULT NULL,
	`team2_result_decision` float DEFAULT NULL,
	`decision_info` varchar(128) NOT NULL DEFAULT '',
	`cancel` TINYINT NOT NULL DEFAULT 0,
	`cancel_reason` varchar(32) NOT NULL DEFAULT '',
	`count_result` TINYINT NOT NULL DEFAULT 1,
	`crowd` INT NOT NULL DEFAULT 0,
	`summary` text NULL,
	`show_report` TINYINT NOT NULL DEFAULT 0,
	`preview` text NULL,
	`match_result_detail` varchar(64) NOT NULL DEFAULT '',
	`new_match_id` INT NULL DEFAULT NULL,
	`old_match_id` INT NULL DEFAULT NULL,
	`extended` text,
	`published` TINYINT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	`alias` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	KEY `round_id` (`round_id`),
	KEY `projectteam1_id` (`projectteam1_id`),
	KEY `projectteam2_id` (`projectteam2_id`),
	KEY `playground_id` (`playground_id`),
	KEY `new_match_id` (`new_match_id`),
	KEY `old_match_id` (`old_match_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_match_event`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_match_event` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`match_id` INT NULL DEFAULT NULL,
	`projectteam_id` INT NULL DEFAULT NULL,
	`teamplayer_id` INT NULL DEFAULT NULL,
	`external_person_name` varchar(100) NOT NULL DEFAULT '',
	`teamplayer_id2` INT NULL DEFAULT NULL,
	`event_time` varchar(20) NOT NULL DEFAULT '',
	`event_type_id` INT NULL DEFAULT NULL,
	`event_sum` double DEFAULT NULL,
	`notice` varchar(64) NOT NULL DEFAULT '',
	`notes` text,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `match_id` (`match_id`),
	KEY `projectteam_id` (`projectteam_id`),
	KEY `teamplayer_id` (`teamplayer_id`),
	KEY `teamplayer_id2` (`teamplayer_id2`),
	KEY `event_type_id` (`event_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_match_player`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_match_player` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`match_id` INT NULL DEFAULT NULL,
	`teamplayer_id` INT NULL DEFAULT NULL,
	`project_position_id` INT NULL DEFAULT NULL,
	`is_substitute` TINYINT NOT NULL DEFAULT 0,
	`came_in` TINYINT NOT NULL DEFAULT 0,
	`in_for` INT NULL DEFAULT NULL,
	`out` TINYINT NOT NULL DEFAULT 0,
	`in_out_time` varchar(15) DEFAULT NULL,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `match_id` (`match_id`),
	KEY `teamplayer_id` (`teamplayer_id`),
	KEY `project_position_id` (`project_position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_match_referee`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_match_referee` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`match_id` INT NULL DEFAULT NULL,
	`project_referee_id` INT NULL DEFAULT NULL,
	`external_referee_name` varchar(100) NOT NULL DEFAULT '',
	`project_position_id` INT NULL DEFAULT NULL,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `match_id` (`match_id`),
	KEY `project_referee_id` (`project_referee_id`),
	KEY `project_position_id` (`project_position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_match_staff`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_match_staff` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`match_id` INT NULL DEFAULT NULL,
	`team_staff_id` INT NULL DEFAULT NULL,
	`project_position_id` INT NULL DEFAULT NULL,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `match_id` (`match_id`),
	KEY `team_staff_id` (`team_staff_id`),
	KEY `project_position_id` (`project_position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_match_staff_statistic`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_match_staff_statistic` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`match_id` INT NULL DEFAULT NULL,
	`projectteam_id` INT NOT NULL,
	`team_staff_id` INT NULL DEFAULT NULL,
	`statistic_id` INT NULL DEFAULT NULL,
	`value` double NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `match_id` (`match_id`),
	KEY `projectteam_id` (`projectteam_id`),
	KEY `team_staff_id` (`team_staff_id`),
	KEY `statistic_id` (`statistic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_match_statistic`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_match_statistic` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`match_id` INT NULL DEFAULT NULL,
	`projectteam_id` INT NOT NULL,
	`teamplayer_id` INT NULL DEFAULT NULL,
	`statistic_id` INT NULL DEFAULT NULL,
	`value` double NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `match_id` (`match_id`),
	KEY `projectteam_id` (`projectteam_id`),
	KEY `teamplayer_id` (`teamplayer_id`),
	KEY `statistic_id` (`statistic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_person`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_person` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`contact_id` INT NULL DEFAULT NULL,
	`firstname` varchar(45) NOT NULL DEFAULT '',
	`lastname` varchar(45) NOT NULL DEFAULT '',
	`nickname` varchar(45) NOT NULL DEFAULT '',
	`alias` varchar(90) NOT NULL DEFAULT '',
	`country` varchar(6) DEFAULT NULL,
	`knvbnr` varchar(10) NOT NULL DEFAULT '',
	`birthday` DATE NULL DEFAULT NULL,
	`deathday` DATE NULL DEFAULT NULL,
	`height` INT DEFAULT NULL,
	`weight` INT DEFAULT NULL,
	`picture` varchar(128) NOT NULL DEFAULT '',
	`show_pic` TINYINT NOT NULL DEFAULT 1,
	`show_persdata` TINYINT NOT NULL DEFAULT 1,
	`show_teamdata` TINYINT NOT NULL DEFAULT 1,
	`show_on_frontend` TINYINT NOT NULL DEFAULT 1,
	`info` text,
	`notes` text,
	`phone` varchar(20) NOT NULL DEFAULT '',
	`mobile` varchar(20) NOT NULL DEFAULT '',
	`email` varchar(50) NOT NULL DEFAULT '',
	`website` varchar(250) NOT NULL DEFAULT '',
	`address` varchar(100) NOT NULL DEFAULT '',
	`zipcode` varchar(10) NOT NULL DEFAULT '',
	`location` varchar(50) NOT NULL DEFAULT '',
	`state` varchar(50) NOT NULL DEFAULT '',
	`address_country` varchar(6) DEFAULT NULL,
	`extended` text,
	`position_id` INT DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 0,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `contact_id` (`contact_id`),
	KEY `position_id` (`position_id`),
	KEY `country` (`country`),
	KEY `address_country` (`address_country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_playground`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_playground` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` varchar(75) NOT NULL DEFAULT '',
	`short_name` varchar(15) NOT NULL DEFAULT '',
	`alias` varchar(75) NOT NULL DEFAULT '',
	`address` varchar(100) NOT NULL DEFAULT '',
	`zipcode` varchar(10) NOT NULL DEFAULT '',
	`city` varchar(64) NOT NULL DEFAULT '',
	`country` varchar(6) DEFAULT NULL,
	`latitude` DECIMAL(10,7) NULL DEFAULT NULL,
	`longitude` DECIMAL(10,7) NULL DEFAULT NULL,
	`max_visitors` INT DEFAULT NULL,
	`website` varchar(250) NOT NULL DEFAULT '',
	`picture` varchar(128) NOT NULL DEFAULT '',
	`info` text,
	`notes` text,
	`club_id` INT NULL DEFAULT NULL,
	`extended` text,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	KEY `club_id` (`club_id`),
	KEY `country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_position`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_position` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` varchar(75) NOT NULL DEFAULT '',
	`alias` varchar(75) NOT NULL DEFAULT '',
	`parent_id` INT NULL DEFAULT NULL,
	`persontype` TINYINT NOT NULL DEFAULT 1,
	`sports_type_id` INT NULL DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 0,
	`ordering` smallINT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`,`parent_id`,`persontype`,`sports_type_id`),
	KEY `parent_id` (`parent_id`),
	KEY `sports_type_id` (`sports_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_position_eventtype`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_position_eventtype` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`position_id` INT NULL DEFAULT NULL,
	`eventtype_id` INT NULL DEFAULT NULL,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `pos_et` (`position_id`,`eventtype_id`),
	KEY `position_id` (`position_id`),
	KEY `eventtype_id` (`eventtype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_position_statistic`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_position_statistic` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`position_id` INT NULL DEFAULT NULL,
	`statistic_id` INT NULL DEFAULT NULL,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `pos_et` (`position_id`,`statistic_id`),
	KEY `position_id` (`position_id`),
	KEY `statistic_id` (`statistic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_project`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_project` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` varchar(100) NOT NULL DEFAULT '',
	`alias` varchar(255) NOT NULL DEFAULT '',
	`league_id` INT NULL DEFAULT NULL,
	`season_id` INT NULL DEFAULT NULL,
	`master_template` INT NULL DEFAULT NULL,
	`sub_template_id` INT NULL DEFAULT NULL,
	`extension` varchar(80) DEFAULT NULL,
	`timezone` varchar(50) NOT NULL DEFAULT 'Europe/Amsterdam',
	`project_type` enum('SIMPLE_LEAGUE','DIVISIONS_LEAGUE','TOURNAMENT_MODE','FRIENDLY_MATCHES','RUNNING_RACE') NOT NULL DEFAULT 'SIMPLE_LEAGUE',
	`teams_as_referees` TINYINT NOT NULL DEFAULT 0,
	`sports_type_id` INT NULL DEFAULT NULL,
	`start_date` DATE NULL DEFAULT NULL,
	`start_time` varchar(5) NOT NULL DEFAULT '15:30',
	`current_round_auto` TINYINT NOT NULL DEFAULT 0,
	`current_round` varchar(32) NOT NULL DEFAULT 0,
	`auto_time` INT DEFAULT NULL,
	`game_regular_time` smallINT NOT NULL DEFAULT 90,
	`game_parts` smallINT NOT NULL DEFAULT 2,
	`halftime` smallINT NOT NULL DEFAULT 15,
	`points_after_regular_time` varchar(10) NOT NULL DEFAULT '3,1,0',
	`use_legs` TINYINT DEFAULT NULL,
	`allow_add_time` TINYINT NOT NULL DEFAULT 0,
	`add_time` smallINT NOT NULL DEFAULT 30,
	`points_after_add_time` varchar(10) NOT NULL DEFAULT '3,1,0',
	`points_after_penalty` varchar(10) NOT NULL DEFAULT '3,1,0',
	`fav_team` varchar(64) NOT NULL DEFAULT '',
	`fav_team_highlight_type` varchar(7) NOT NULL DEFAULT '',
	`fav_team_color` varchar(7) NOT NULL DEFAULT '',
	`fav_team_text_color` varchar(7) NOT NULL DEFAULT '',
	`fav_team_text_bold` varchar(7) NOT NULL DEFAULT '',
	`template` varchar(32) NOT NULL DEFAULT 'default',
	`enable_sb` TINYINT NOT NULL DEFAULT 0,
	`sb_catid` INT NOT NULL DEFAULT 0,
	`extended` text,
	`picture` varchar(128) DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 0,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	`is_utc_converted` TINYINT NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name, league, season` (`name`,`league_id`,`season_id`),
	KEY `league_id` (`league_id`),
	KEY `season_id` (`season_id`),
	KEY `sub_template_id` (`sub_template_id`),
	KEY `sports_type_id` (`sports_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_project_position`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_project_position` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NOT NULL,
	`position_id` INT NOT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `pos_proj` (`position_id`,`project_id`),
	KEY `project_id` (`project_id`),
	KEY `position_id` (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_project_referee`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_project_referee` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NULL DEFAULT NULL,
	`person_id` INT NOT NULL,
	`project_position_id` INT DEFAULT NULL,
	`notes` text,
	`picture` varchar(128) NOT NULL DEFAULT '',
	`published` INT NOT NULL DEFAULT 1,
	`extended` text,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	`alias` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	KEY `project_id` (`project_id`),
	KEY `person_id` (`person_id`),
	KEY `project_position_id` (`project_position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_project_team`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_project_team` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NULL DEFAULT NULL,
	`team_id` INT NULL DEFAULT NULL,
	`division_id` INT DEFAULT NULL,
	`start_points` smallINT NOT NULL DEFAULT 0,
	`points_finally` smallINT NOT NULL DEFAULT 0,
	`neg_points_finally` smallINT NOT NULL DEFAULT 0,
	`matches_finally` smallINT NOT NULL DEFAULT 0,
	`won_finally` smallINT NOT NULL DEFAULT 0,
	`draws_finally` smallINT NOT NULL DEFAULT 0,
	`lost_finally` smallINT NOT NULL DEFAULT 0,
	`homegoals_finally` smallINT NOT NULL DEFAULT 0,
	`guestgoals_finally` smallINT NOT NULL DEFAULT 0,
	`diffgoals_finally` smallINT NOT NULL DEFAULT 0,
	`is_in_score` TINYINT NOT NULL DEFAULT 1,
	`use_finally` TINYINT NOT NULL DEFAULT 0,
	`info` text,
	`picture` varchar(128) DEFAULT NULL,
	`notes` text,
	`standard_playground` INT NULL DEFAULT NULL,
	`reason` varchar(150) NOT NULL DEFAULT '',
	`extended` text,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`ordering` INT NOT NULL DEFAULT 0,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	`alias` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	UNIQUE KEY `combi` (`project_id`,`team_id`),
	KEY `project_id` (`project_id`),
	KEY `team_id` (`team_id`),
	KEY `division_id` (`division_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_round`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_round` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NULL DEFAULT NULL,
	`roundcode` INT NOT NULL DEFAULT 0,
	`name` varchar(75) NOT NULL DEFAULT '',
	`alias` varchar(75) NOT NULL DEFAULT '',
	`round_date_first` DATE NULL DEFAULT NULL,
	`round_date_last` DATE NULL DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 1,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_season`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_season` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` varchar(75) NOT NULL DEFAULT '',
	`alias` varchar(75) NOT NULL DEFAULT '',
	`extended` text,
	`published` TINYINT NOT NULL DEFAULT 0,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_sports_type`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_sports_type` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL DEFAULT '',
	`periods` TINYINT NOT NULL DEFAULT 2,
	`icon` varchar(255) NOT NULL DEFAULT '',
	`published` TINYINT NOT NULL DEFAULT 1,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_statistic`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_statistic` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` varchar(75) NOT NULL DEFAULT '',
	`alias` varchar(75) NOT NULL DEFAULT '',
	`short` varchar(10) NOT NULL DEFAULT '',
	`icon` varchar(128) NOT NULL DEFAULT '',
	`class` varchar(50) NOT NULL DEFAULT '' COMMENT 'must be the name of the class handling it',
	`calculated` TINYINT NOT NULL DEFAULT 0,
	`params` text NULL,
	`baseparams` text NULL,
	`note` varchar(100) DEFAULT NULL,
	`sports_type_id` INT NULL DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 1,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `sports_type_id` (`sports_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_team`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_team` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`club_id` INT DEFAULT NULL,
	`name` varchar(75) NOT NULL DEFAULT '',
	`short_name` varchar(15) NOT NULL DEFAULT '',
	`middle_name` varchar(25) NOT NULL DEFAULT '',
	`alias` varchar(255) NOT NULL DEFAULT '',
	`website` varchar(250) NOT NULL DEFAULT '',
	`info` text,
	`notes` text,
	`picture` varchar(128) NOT NULL DEFAULT '',
	`extended` text,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `club_id` (`club_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_team_player`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_team_player` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`projectteam_id` INT NULL DEFAULT NULL,
	`person_id` INT NULL DEFAULT NULL,
	`project_position_id` INT DEFAULT NULL,
	`active` TINYINT DEFAULT 1,
	`jerseynumber` INT DEFAULT NULL,
	`notes` text,
	`picture` varchar(128) NOT NULL DEFAULT '',
	`extended` text,
	`injury` TINYINT NOT NULL DEFAULT 0,
	`injury_date` INT NULL DEFAULT NULL,
	`injury_end` INT NULL DEFAULT NULL,
	`injury_detail` varchar(255) NOT NULL DEFAULT '',
	`injury_date_start` DATE NULL DEFAULT NULL,
	`injury_date_end` DATE NULL DEFAULT NULL,
	`suspension` TINYINT NOT NULL DEFAULT 0,
	`suspension_date` INT NULL DEFAULT NULL,
	`suspension_end` INT NULL DEFAULT NULL,
	`suspension_detail` varchar(255) NOT NULL DEFAULT '',
	`susp_date_start` DATE NULL DEFAULT NULL,
	`susp_date_end` DATE NULL DEFAULT NULL,
	`away` TINYINT NOT NULL DEFAULT 0,
	`away_date` INT NULL DEFAULT NULL,
	`away_end` INT NULL DEFAULT NULL,
	`away_detail` varchar(255) NOT NULL DEFAULT '',
	`away_date_start` DATE NULL DEFAULT NULL,
	`away_date_end` DATE NULL DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 0,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	`alias` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	KEY `projectteam_id` (`projectteam_id`),
	KEY `person_id` (`person_id`),
	KEY `project_position_id` (`project_position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_team_staff`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_team_staff` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`projectteam_id` INT NULL DEFAULT NULL,
	`person_id` INT NULL DEFAULT NULL,
	`project_position_id` INT DEFAULT NULL,
	`active` TINYINT DEFAULT 1,
	`notes` text,
	`injury` TINYINT NOT NULL DEFAULT 0,
	`injury_date` INT NULL DEFAULT NULL,
	`injury_end` INT NULL DEFAULT NULL,
	`injury_detail` varchar(255) NOT NULL DEFAULT '',
	`injury_date_start` DATE NULL DEFAULT NULL,
	`injury_date_end` DATE NULL DEFAULT NULL,
	`suspension` TINYINT NOT NULL DEFAULT 0,
	`suspension_date` INT NULL DEFAULT NULL,
	`suspension_end` INT NULL DEFAULT NULL,
	`suspension_detail` varchar(255) NOT NULL DEFAULT '',
	`susp_date_start` DATE NULL DEFAULT NULL,
	`susp_date_end` DATE NULL DEFAULT NULL,
	`away` TINYINT NOT NULL DEFAULT 0,
	`away_date` INT NULL DEFAULT NULL,
	`away_end` INT NULL DEFAULT NULL,
	`away_detail` varchar(255) NOT NULL DEFAULT '',
	`away_date_start` DATE NULL DEFAULT NULL,
	`away_date_end` DATE NULL DEFAULT NULL,
	`picture` varchar(128) NOT NULL DEFAULT '',
	`extended` text,
	`published` TINYINT NOT NULL DEFAULT 0,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	`alias` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	KEY `projectteam_id` (`projectteam_id`),
	KEY `person_id` (`person_id`),
	KEY `project_position_id` (`project_position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_team_trainingdata`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_team_trainingdata` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NULL DEFAULT NULL,
	`team_id` INT NULL DEFAULT NULL,
	`project_team_id` INT NULL DEFAULT NULL,
	`dayofweek` TINYINT NOT NULL DEFAULT 0,
	`time_start` INT NOT NULL DEFAULT 0,
	`time_end` INT NOT NULL DEFAULT 0,
	`place` varchar(255) NOT NULL DEFAULT '',
	`notes` text,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `project_id` (`project_id`),
	KEY `team_id` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_template_config`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_template_config` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NULL DEFAULT NULL,
	`template` varchar(64) NOT NULL DEFAULT '',
	`func` varchar(64) NOT NULL DEFAULT '',
	`title` varchar(255) NOT NULL DEFAULT '',
	`params` text NULL,
	`published` INT NOT NULL DEFAULT 1,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_treeto`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_treeto` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NULL DEFAULT NULL,
	`division_id` INT NULL DEFAULT NULL,
	`tree_i` INT NOT NULL DEFAULT 0,
	`name` varchar(128) DEFAULT NULL,
	`global_bestof` TINYINT NOT NULL DEFAULT 0,
	`global_matchday` TINYINT NOT NULL DEFAULT 0,
	`global_known` TINYINT NOT NULL DEFAULT 0,
	`global_fake` TINYINT NOT NULL DEFAULT 0,
	`leafed` TINYINT NOT NULL DEFAULT 0,
	`mirror` TINYINT NOT NULL DEFAULT 0,
	`hide` TINYINT NOT NULL DEFAULT 0,
	`trophypic` varchar(128) DEFAULT NULL,
	`extended` text,
	`published` TINYINT NOT NULL DEFAULT 1,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_treeto_match`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_treeto_match` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`node_id` INT NULL DEFAULT NULL,
	`match_id` INT NULL DEFAULT NULL,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `combi` (`node_id`,`match_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_treeto_node`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_treeto_node` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`treeto_id` INT NULL DEFAULT NULL,
	`node` INT NOT NULL DEFAULT 0,
	`row` INT NOT NULL DEFAULT 0,
	`bestof` TINYINT NOT NULL DEFAULT 1,
	`title` varchar(50) NOT NULL DEFAULT '',
	`content` varchar(50) NOT NULL DEFAULT '',
	`team_id` INT NULL DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 1,
	`is_leaf` TINYINT NOT NULL DEFAULT 0,
	`is_lock` TINYINT NOT NULL DEFAULT 0,
	`is_ready` TINYINT NOT NULL DEFAULT 0,
	`got_lc` TINYINT NOT NULL DEFAULT 0,
	`got_rc` TINYINT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_prediction_game`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_prediction_tip`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_prediction_score`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_version`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_version` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`major` INT NOT NULL,
	`minor` INT NOT NULL,
	`build` INT NOT NULL,
	`count` INT NOT NULL,
	`revision` varchar(128) NOT NULL DEFAULT '',
	`file` varchar(255) NOT NULL DEFAULT '',
	`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`version` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


-- Relational integrity is added after all tables exist to avoid creation-order coupling.
ALTER TABLE `#__joomleague_club`
	ADD CONSTRAINT `fk_jl_club_playground` FOREIGN KEY (`standard_playground`) REFERENCES `#__joomleague_playground` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_club_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_club_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_club_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_division`
	ADD CONSTRAINT `fk_jl_division_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_division_parent` FOREIGN KEY (`parent_id`) REFERENCES `#__joomleague_division` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_division_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_division_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_division_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_eventtype`
	ADD CONSTRAINT `fk_jl_eventtype_parent` FOREIGN KEY (`parent`) REFERENCES `#__joomleague_eventtype` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_eventtype_sport` FOREIGN KEY (`sports_type_id`) REFERENCES `#__joomleague_sports_type` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_eventtype_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_eventtype_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_eventtype_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_league`
	ADD CONSTRAINT `fk_jl_league_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_league_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_league_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_match`
	ADD CONSTRAINT `fk_jl_match_round` FOREIGN KEY (`round_id`) REFERENCES `#__joomleague_round` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_home_team` FOREIGN KEY (`projectteam1_id`) REFERENCES `#__joomleague_project_team` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_match_away_team` FOREIGN KEY (`projectteam2_id`) REFERENCES `#__joomleague_project_team` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_match_playground` FOREIGN KEY (`playground_id`) REFERENCES `#__joomleague_playground` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_new_match` FOREIGN KEY (`new_match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_old_match` FOREIGN KEY (`old_match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_match_event`
	ADD CONSTRAINT `fk_jl_match_event_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_event_team` FOREIGN KEY (`projectteam_id`) REFERENCES `#__joomleague_project_team` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_event_player1` FOREIGN KEY (`teamplayer_id`) REFERENCES `#__joomleague_team_player` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_event_player2` FOREIGN KEY (`teamplayer_id2`) REFERENCES `#__joomleague_team_player` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_event_type` FOREIGN KEY (`event_type_id`) REFERENCES `#__joomleague_eventtype` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_match_event_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_event_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_match_player`
	ADD CONSTRAINT `fk_jl_match_player_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_player_player` FOREIGN KEY (`teamplayer_id`) REFERENCES `#__joomleague_team_player` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_player_position` FOREIGN KEY (`project_position_id`) REFERENCES `#__joomleague_project_position` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_player_in_for` FOREIGN KEY (`in_for`) REFERENCES `#__joomleague_team_player` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_player_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_player_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_match_referee`
	ADD CONSTRAINT `fk_jl_match_referee_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_referee_referee` FOREIGN KEY (`project_referee_id`) REFERENCES `#__joomleague_project_referee` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_referee_position` FOREIGN KEY (`project_position_id`) REFERENCES `#__joomleague_project_position` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_referee_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_referee_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_match_staff`
	ADD CONSTRAINT `fk_jl_match_staff_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_staff_staff` FOREIGN KEY (`team_staff_id`) REFERENCES `#__joomleague_team_staff` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_staff_position` FOREIGN KEY (`project_position_id`) REFERENCES `#__joomleague_project_position` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_staff_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_staff_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_match_staff_statistic`
	ADD CONSTRAINT `fk_jl_mstaff_stat_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_mstaff_stat_team` FOREIGN KEY (`projectteam_id`) REFERENCES `#__joomleague_project_team` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_mstaff_stat_staff` FOREIGN KEY (`team_staff_id`) REFERENCES `#__joomleague_team_staff` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_mstaff_stat_stat` FOREIGN KEY (`statistic_id`) REFERENCES `#__joomleague_statistic` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_mstaff_stat_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_mstaff_stat_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_match_statistic`
	ADD CONSTRAINT `fk_jl_match_stat_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_stat_team` FOREIGN KEY (`projectteam_id`) REFERENCES `#__joomleague_project_team` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_stat_player` FOREIGN KEY (`teamplayer_id`) REFERENCES `#__joomleague_team_player` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_match_stat_stat` FOREIGN KEY (`statistic_id`) REFERENCES `#__joomleague_statistic` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_match_stat_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_match_stat_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_person`
	ADD CONSTRAINT `fk_jl_person_contact` FOREIGN KEY (`contact_id`) REFERENCES `#__contact_details` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_person_position` FOREIGN KEY (`position_id`) REFERENCES `#__joomleague_position` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_person_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_person_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_person_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_playground`
	ADD CONSTRAINT `fk_jl_playground_club` FOREIGN KEY (`club_id`) REFERENCES `#__joomleague_club` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_playground_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_playground_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_playground_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_position`
	ADD CONSTRAINT `fk_jl_position_parent` FOREIGN KEY (`parent_id`) REFERENCES `#__joomleague_position` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_position_sport` FOREIGN KEY (`sports_type_id`) REFERENCES `#__joomleague_sports_type` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_position_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_position_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_position_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_position_eventtype`
	ADD CONSTRAINT `fk_jl_position_event_position` FOREIGN KEY (`position_id`) REFERENCES `#__joomleague_position` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_position_event_event` FOREIGN KEY (`eventtype_id`) REFERENCES `#__joomleague_eventtype` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_position_event_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_position_event_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_position_statistic`
	ADD CONSTRAINT `fk_jl_position_stat_position` FOREIGN KEY (`position_id`) REFERENCES `#__joomleague_position` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_position_stat_stat` FOREIGN KEY (`statistic_id`) REFERENCES `#__joomleague_statistic` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_position_stat_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_position_stat_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_project`
	ADD CONSTRAINT `fk_jl_project_league` FOREIGN KEY (`league_id`) REFERENCES `#__joomleague_league` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_project_season` FOREIGN KEY (`season_id`) REFERENCES `#__joomleague_season` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_project_master` FOREIGN KEY (`master_template`) REFERENCES `#__joomleague_project` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_subtemplate` FOREIGN KEY (`sub_template_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_sport` FOREIGN KEY (`sports_type_id`) REFERENCES `#__joomleague_sports_type` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_project_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_project_position`
	ADD CONSTRAINT `fk_jl_project_position_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_project_position_position` FOREIGN KEY (`position_id`) REFERENCES `#__joomleague_position` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_project_position_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_project_referee`
	ADD CONSTRAINT `fk_jl_project_referee_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_project_referee_person` FOREIGN KEY (`person_id`) REFERENCES `#__joomleague_person` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_project_referee_position` FOREIGN KEY (`project_position_id`) REFERENCES `#__joomleague_project_position` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_referee_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_referee_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_referee_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_project_team`
	ADD CONSTRAINT `fk_jl_project_team_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_project_team_team` FOREIGN KEY (`team_id`) REFERENCES `#__joomleague_team` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_project_team_division` FOREIGN KEY (`division_id`) REFERENCES `#__joomleague_division` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_team_playground` FOREIGN KEY (`standard_playground`) REFERENCES `#__joomleague_playground` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_team_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_team_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_project_team_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_round`
	ADD CONSTRAINT `fk_jl_round_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_round_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_round_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_round_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_season`
	ADD CONSTRAINT `fk_jl_season_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_season_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_season_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_sports_type`
	ADD KEY `idx_jl_sport_published_ordering` (`published`, `ordering`),
	ADD CONSTRAINT `fk_jl_sport_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_sport_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_sport_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_statistic`
	ADD CONSTRAINT `fk_jl_statistic_sport` FOREIGN KEY (`sports_type_id`) REFERENCES `#__joomleague_sports_type` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_statistic_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_statistic_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_statistic_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_team`
	ADD CONSTRAINT `fk_jl_team_club` FOREIGN KEY (`club_id`) REFERENCES `#__joomleague_club` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_team_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_team_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_team_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_team_player`
	ADD CONSTRAINT `fk_jl_team_player_projectteam` FOREIGN KEY (`projectteam_id`) REFERENCES `#__joomleague_project_team` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_team_player_person` FOREIGN KEY (`person_id`) REFERENCES `#__joomleague_person` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_team_player_position` FOREIGN KEY (`project_position_id`) REFERENCES `#__joomleague_project_position` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_team_player_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_team_player_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_team_player_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_team_staff`
	ADD CONSTRAINT `fk_jl_team_staff_projectteam` FOREIGN KEY (`projectteam_id`) REFERENCES `#__joomleague_project_team` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_team_staff_person` FOREIGN KEY (`person_id`) REFERENCES `#__joomleague_person` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_team_staff_position` FOREIGN KEY (`project_position_id`) REFERENCES `#__joomleague_project_position` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_team_staff_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_team_staff_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_team_staff_asset` FOREIGN KEY (`asset_id`) REFERENCES `#__assets` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_team_trainingdata`
	ADD CONSTRAINT `fk_jl_training_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_training_team` FOREIGN KEY (`team_id`) REFERENCES `#__joomleague_team` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_training_projectteam` FOREIGN KEY (`project_team_id`) REFERENCES `#__joomleague_project_team` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_training_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_training_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_template_config`
	ADD CONSTRAINT `fk_jl_template_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_template_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_template_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_treeto`
	ADD CONSTRAINT `fk_jl_treeto_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_treeto_division` FOREIGN KEY (`division_id`) REFERENCES `#__joomleague_division` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_treeto_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_treeto_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_treeto_match`
	ADD CONSTRAINT `fk_jl_treeto_match_node` FOREIGN KEY (`node_id`) REFERENCES `#__joomleague_treeto_node` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_treeto_match_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_treeto_match_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_treeto_match_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_treeto_node`
	ADD CONSTRAINT `fk_jl_treeto_node_tree` FOREIGN KEY (`treeto_id`) REFERENCES `#__joomleague_treeto` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_treeto_node_team` FOREIGN KEY (`team_id`) REFERENCES `#__joomleague_team` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_treeto_node_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_treeto_node_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_prediction_game`
	ADD CONSTRAINT `fk_jl_prediction_game_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_prediction_game_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_prediction_game_created_by` FOREIGN KEY (`created_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_prediction_game_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_prediction_tip`
	ADD CONSTRAINT `fk_jl_prediction_tip_game` FOREIGN KEY (`game_id`) REFERENCES `#__joomleague_prediction_game` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_prediction_tip_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_prediction_tip_user` FOREIGN KEY (`user_id`) REFERENCES `#__users` (`id`) ON DELETE CASCADE;

ALTER TABLE `#__joomleague_prediction_score`
	ADD CONSTRAINT `fk_jl_prediction_score_game` FOREIGN KEY (`game_id`) REFERENCES `#__joomleague_prediction_game` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_prediction_score_user` FOREIGN KEY (`user_id`) REFERENCES `#__users` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `fk_jl_prediction_score_round` FOREIGN KEY (`round_id`) REFERENCES `#__joomleague_round` (`id`) ON DELETE CASCADE;

-- Číselník států (názvy jako jazykové konstanty, překlady v com_joomleague.ini)
CREATE TABLE IF NOT EXISTS `#__joomleague_country` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`code` varchar(6) NOT NULL DEFAULT '',
	`name` varchar(150) NOT NULL DEFAULT '',
	`published` TINYINT NOT NULL DEFAULT 1,
	`ordering` INT NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__joomleague_country` (`code`, `name`) VALUES
	('ad', 'COM_JOOMLEAGUE_COUNTRY_AD'),
	('ae', 'COM_JOOMLEAGUE_COUNTRY_AE'),
	('af', 'COM_JOOMLEAGUE_COUNTRY_AF'),
	('ag', 'COM_JOOMLEAGUE_COUNTRY_AG'),
	('ai', 'COM_JOOMLEAGUE_COUNTRY_AI'),
	('al', 'COM_JOOMLEAGUE_COUNTRY_AL'),
	('am', 'COM_JOOMLEAGUE_COUNTRY_AM'),
	('ao', 'COM_JOOMLEAGUE_COUNTRY_AO'),
	('aq', 'COM_JOOMLEAGUE_COUNTRY_AQ'),
	('ar', 'COM_JOOMLEAGUE_COUNTRY_AR'),
	('as', 'COM_JOOMLEAGUE_COUNTRY_AS'),
	('at', 'COM_JOOMLEAGUE_COUNTRY_AT'),
	('au', 'COM_JOOMLEAGUE_COUNTRY_AU'),
	('aw', 'COM_JOOMLEAGUE_COUNTRY_AW'),
	('ax', 'COM_JOOMLEAGUE_COUNTRY_AX'),
	('az', 'COM_JOOMLEAGUE_COUNTRY_AZ'),
	('ba', 'COM_JOOMLEAGUE_COUNTRY_BA'),
	('bb', 'COM_JOOMLEAGUE_COUNTRY_BB'),
	('bd', 'COM_JOOMLEAGUE_COUNTRY_BD'),
	('be', 'COM_JOOMLEAGUE_COUNTRY_BE'),
	('bf', 'COM_JOOMLEAGUE_COUNTRY_BF'),
	('bg', 'COM_JOOMLEAGUE_COUNTRY_BG'),
	('bh', 'COM_JOOMLEAGUE_COUNTRY_BH'),
	('bi', 'COM_JOOMLEAGUE_COUNTRY_BI'),
	('bj', 'COM_JOOMLEAGUE_COUNTRY_BJ'),
	('bl', 'COM_JOOMLEAGUE_COUNTRY_BL'),
	('bm', 'COM_JOOMLEAGUE_COUNTRY_BM'),
	('bn', 'COM_JOOMLEAGUE_COUNTRY_BN'),
	('bo', 'COM_JOOMLEAGUE_COUNTRY_BO'),
	('bq', 'COM_JOOMLEAGUE_COUNTRY_BQ'),
	('br', 'COM_JOOMLEAGUE_COUNTRY_BR'),
	('bs', 'COM_JOOMLEAGUE_COUNTRY_BS'),
	('bt', 'COM_JOOMLEAGUE_COUNTRY_BT'),
	('bv', 'COM_JOOMLEAGUE_COUNTRY_BV'),
	('bw', 'COM_JOOMLEAGUE_COUNTRY_BW'),
	('by', 'COM_JOOMLEAGUE_COUNTRY_BY'),
	('bz', 'COM_JOOMLEAGUE_COUNTRY_BZ'),
	('ca', 'COM_JOOMLEAGUE_COUNTRY_CA'),
	('cc', 'COM_JOOMLEAGUE_COUNTRY_CC'),
	('cd', 'COM_JOOMLEAGUE_COUNTRY_CD'),
	('cf', 'COM_JOOMLEAGUE_COUNTRY_CF'),
	('cg', 'COM_JOOMLEAGUE_COUNTRY_CG'),
	('ci', 'COM_JOOMLEAGUE_COUNTRY_CI'),
	('ck', 'COM_JOOMLEAGUE_COUNTRY_CK'),
	('cl', 'COM_JOOMLEAGUE_COUNTRY_CL'),
	('cm', 'COM_JOOMLEAGUE_COUNTRY_CM'),
	('cn', 'COM_JOOMLEAGUE_COUNTRY_CN'),
	('co', 'COM_JOOMLEAGUE_COUNTRY_CO'),
	('cr', 'COM_JOOMLEAGUE_COUNTRY_CR'),
	('cu', 'COM_JOOMLEAGUE_COUNTRY_CU'),
	('cv', 'COM_JOOMLEAGUE_COUNTRY_CV'),
	('cw', 'COM_JOOMLEAGUE_COUNTRY_CW'),
	('cx', 'COM_JOOMLEAGUE_COUNTRY_CX'),
	('cy', 'COM_JOOMLEAGUE_COUNTRY_CY'),
	('cz', 'COM_JOOMLEAGUE_COUNTRY_CZ'),
	('de', 'COM_JOOMLEAGUE_COUNTRY_DE'),
	('dj', 'COM_JOOMLEAGUE_COUNTRY_DJ'),
	('dk', 'COM_JOOMLEAGUE_COUNTRY_DK'),
	('dm', 'COM_JOOMLEAGUE_COUNTRY_DM'),
	('do', 'COM_JOOMLEAGUE_COUNTRY_DO'),
	('dz', 'COM_JOOMLEAGUE_COUNTRY_DZ'),
	('ec', 'COM_JOOMLEAGUE_COUNTRY_EC'),
	('ee', 'COM_JOOMLEAGUE_COUNTRY_EE'),
	('eg', 'COM_JOOMLEAGUE_COUNTRY_EG'),
	('eh', 'COM_JOOMLEAGUE_COUNTRY_EH'),
	('er', 'COM_JOOMLEAGUE_COUNTRY_ER'),
	('es', 'COM_JOOMLEAGUE_COUNTRY_ES'),
	('et', 'COM_JOOMLEAGUE_COUNTRY_ET'),
	('fi', 'COM_JOOMLEAGUE_COUNTRY_FI'),
	('fj', 'COM_JOOMLEAGUE_COUNTRY_FJ'),
	('fk', 'COM_JOOMLEAGUE_COUNTRY_FK'),
	('fm', 'COM_JOOMLEAGUE_COUNTRY_FM'),
	('fo', 'COM_JOOMLEAGUE_COUNTRY_FO'),
	('fr', 'COM_JOOMLEAGUE_COUNTRY_FR'),
	('ga', 'COM_JOOMLEAGUE_COUNTRY_GA'),
	('gb', 'COM_JOOMLEAGUE_COUNTRY_GB'),
	('gb-eng', 'COM_JOOMLEAGUE_COUNTRY_GB_ENG'),
	('gb-nir', 'COM_JOOMLEAGUE_COUNTRY_GB_NIR'),
	('gb-sct', 'COM_JOOMLEAGUE_COUNTRY_GB_SCT'),
	('gb-wls', 'COM_JOOMLEAGUE_COUNTRY_GB_WLS'),
	('gd', 'COM_JOOMLEAGUE_COUNTRY_GD'),
	('ge', 'COM_JOOMLEAGUE_COUNTRY_GE'),
	('gf', 'COM_JOOMLEAGUE_COUNTRY_GF'),
	('gg', 'COM_JOOMLEAGUE_COUNTRY_GG'),
	('gh', 'COM_JOOMLEAGUE_COUNTRY_GH'),
	('gi', 'COM_JOOMLEAGUE_COUNTRY_GI'),
	('gl', 'COM_JOOMLEAGUE_COUNTRY_GL'),
	('gm', 'COM_JOOMLEAGUE_COUNTRY_GM'),
	('gn', 'COM_JOOMLEAGUE_COUNTRY_GN'),
	('gp', 'COM_JOOMLEAGUE_COUNTRY_GP'),
	('gq', 'COM_JOOMLEAGUE_COUNTRY_GQ'),
	('gr', 'COM_JOOMLEAGUE_COUNTRY_GR'),
	('gs', 'COM_JOOMLEAGUE_COUNTRY_GS'),
	('gt', 'COM_JOOMLEAGUE_COUNTRY_GT'),
	('gu', 'COM_JOOMLEAGUE_COUNTRY_GU'),
	('gw', 'COM_JOOMLEAGUE_COUNTRY_GW'),
	('gy', 'COM_JOOMLEAGUE_COUNTRY_GY'),
	('hk', 'COM_JOOMLEAGUE_COUNTRY_HK'),
	('hm', 'COM_JOOMLEAGUE_COUNTRY_HM'),
	('hn', 'COM_JOOMLEAGUE_COUNTRY_HN'),
	('hr', 'COM_JOOMLEAGUE_COUNTRY_HR'),
	('ht', 'COM_JOOMLEAGUE_COUNTRY_HT'),
	('hu', 'COM_JOOMLEAGUE_COUNTRY_HU'),
	('ch', 'COM_JOOMLEAGUE_COUNTRY_CH'),
	('id', 'COM_JOOMLEAGUE_COUNTRY_ID'),
	('ie', 'COM_JOOMLEAGUE_COUNTRY_IE'),
	('il', 'COM_JOOMLEAGUE_COUNTRY_IL'),
	('im', 'COM_JOOMLEAGUE_COUNTRY_IM'),
	('in', 'COM_JOOMLEAGUE_COUNTRY_IN'),
	('io', 'COM_JOOMLEAGUE_COUNTRY_IO'),
	('iq', 'COM_JOOMLEAGUE_COUNTRY_IQ'),
	('ir', 'COM_JOOMLEAGUE_COUNTRY_IR'),
	('is', 'COM_JOOMLEAGUE_COUNTRY_IS'),
	('it', 'COM_JOOMLEAGUE_COUNTRY_IT'),
	('je', 'COM_JOOMLEAGUE_COUNTRY_JE'),
	('jm', 'COM_JOOMLEAGUE_COUNTRY_JM'),
	('jo', 'COM_JOOMLEAGUE_COUNTRY_JO'),
	('jp', 'COM_JOOMLEAGUE_COUNTRY_JP'),
	('ke', 'COM_JOOMLEAGUE_COUNTRY_KE'),
	('kg', 'COM_JOOMLEAGUE_COUNTRY_KG'),
	('kh', 'COM_JOOMLEAGUE_COUNTRY_KH'),
	('ki', 'COM_JOOMLEAGUE_COUNTRY_KI'),
	('km', 'COM_JOOMLEAGUE_COUNTRY_KM'),
	('kn', 'COM_JOOMLEAGUE_COUNTRY_KN'),
	('kp', 'COM_JOOMLEAGUE_COUNTRY_KP'),
	('kr', 'COM_JOOMLEAGUE_COUNTRY_KR'),
	('kw', 'COM_JOOMLEAGUE_COUNTRY_KW'),
	('ky', 'COM_JOOMLEAGUE_COUNTRY_KY'),
	('kz', 'COM_JOOMLEAGUE_COUNTRY_KZ'),
	('la', 'COM_JOOMLEAGUE_COUNTRY_LA'),
	('lb', 'COM_JOOMLEAGUE_COUNTRY_LB'),
	('lc', 'COM_JOOMLEAGUE_COUNTRY_LC'),
	('li', 'COM_JOOMLEAGUE_COUNTRY_LI'),
	('lk', 'COM_JOOMLEAGUE_COUNTRY_LK'),
	('lr', 'COM_JOOMLEAGUE_COUNTRY_LR'),
	('ls', 'COM_JOOMLEAGUE_COUNTRY_LS'),
	('lt', 'COM_JOOMLEAGUE_COUNTRY_LT'),
	('lu', 'COM_JOOMLEAGUE_COUNTRY_LU'),
	('lv', 'COM_JOOMLEAGUE_COUNTRY_LV'),
	('ly', 'COM_JOOMLEAGUE_COUNTRY_LY'),
	('ma', 'COM_JOOMLEAGUE_COUNTRY_MA'),
	('mc', 'COM_JOOMLEAGUE_COUNTRY_MC'),
	('md', 'COM_JOOMLEAGUE_COUNTRY_MD'),
	('me', 'COM_JOOMLEAGUE_COUNTRY_ME'),
	('mf', 'COM_JOOMLEAGUE_COUNTRY_MF'),
	('mg', 'COM_JOOMLEAGUE_COUNTRY_MG'),
	('mh', 'COM_JOOMLEAGUE_COUNTRY_MH'),
	('mk', 'COM_JOOMLEAGUE_COUNTRY_MK'),
	('ml', 'COM_JOOMLEAGUE_COUNTRY_ML'),
	('mm', 'COM_JOOMLEAGUE_COUNTRY_MM'),
	('mn', 'COM_JOOMLEAGUE_COUNTRY_MN'),
	('mo', 'COM_JOOMLEAGUE_COUNTRY_MO'),
	('mp', 'COM_JOOMLEAGUE_COUNTRY_MP'),
	('mq', 'COM_JOOMLEAGUE_COUNTRY_MQ'),
	('mr', 'COM_JOOMLEAGUE_COUNTRY_MR'),
	('ms', 'COM_JOOMLEAGUE_COUNTRY_MS'),
	('mt', 'COM_JOOMLEAGUE_COUNTRY_MT'),
	('mu', 'COM_JOOMLEAGUE_COUNTRY_MU'),
	('mv', 'COM_JOOMLEAGUE_COUNTRY_MV'),
	('mw', 'COM_JOOMLEAGUE_COUNTRY_MW'),
	('mx', 'COM_JOOMLEAGUE_COUNTRY_MX'),
	('my', 'COM_JOOMLEAGUE_COUNTRY_MY'),
	('mz', 'COM_JOOMLEAGUE_COUNTRY_MZ'),
	('na', 'COM_JOOMLEAGUE_COUNTRY_NA'),
	('nc', 'COM_JOOMLEAGUE_COUNTRY_NC'),
	('ne', 'COM_JOOMLEAGUE_COUNTRY_NE'),
	('nf', 'COM_JOOMLEAGUE_COUNTRY_NF'),
	('ng', 'COM_JOOMLEAGUE_COUNTRY_NG'),
	('ni', 'COM_JOOMLEAGUE_COUNTRY_NI'),
	('nl', 'COM_JOOMLEAGUE_COUNTRY_NL'),
	('no', 'COM_JOOMLEAGUE_COUNTRY_NO'),
	('np', 'COM_JOOMLEAGUE_COUNTRY_NP'),
	('nr', 'COM_JOOMLEAGUE_COUNTRY_NR'),
	('nu', 'COM_JOOMLEAGUE_COUNTRY_NU'),
	('nz', 'COM_JOOMLEAGUE_COUNTRY_NZ'),
	('om', 'COM_JOOMLEAGUE_COUNTRY_OM'),
	('pa', 'COM_JOOMLEAGUE_COUNTRY_PA'),
	('pe', 'COM_JOOMLEAGUE_COUNTRY_PE'),
	('pf', 'COM_JOOMLEAGUE_COUNTRY_PF'),
	('pg', 'COM_JOOMLEAGUE_COUNTRY_PG'),
	('ph', 'COM_JOOMLEAGUE_COUNTRY_PH'),
	('pk', 'COM_JOOMLEAGUE_COUNTRY_PK'),
	('pl', 'COM_JOOMLEAGUE_COUNTRY_PL'),
	('pm', 'COM_JOOMLEAGUE_COUNTRY_PM'),
	('pn', 'COM_JOOMLEAGUE_COUNTRY_PN'),
	('pr', 'COM_JOOMLEAGUE_COUNTRY_PR'),
	('ps', 'COM_JOOMLEAGUE_COUNTRY_PS'),
	('pt', 'COM_JOOMLEAGUE_COUNTRY_PT'),
	('pw', 'COM_JOOMLEAGUE_COUNTRY_PW'),
	('py', 'COM_JOOMLEAGUE_COUNTRY_PY'),
	('qa', 'COM_JOOMLEAGUE_COUNTRY_QA'),
	('re', 'COM_JOOMLEAGUE_COUNTRY_RE'),
	('ro', 'COM_JOOMLEAGUE_COUNTRY_RO'),
	('rs', 'COM_JOOMLEAGUE_COUNTRY_RS'),
	('ru', 'COM_JOOMLEAGUE_COUNTRY_RU'),
	('rw', 'COM_JOOMLEAGUE_COUNTRY_RW'),
	('sa', 'COM_JOOMLEAGUE_COUNTRY_SA'),
	('sb', 'COM_JOOMLEAGUE_COUNTRY_SB'),
	('sc', 'COM_JOOMLEAGUE_COUNTRY_SC'),
	('sd', 'COM_JOOMLEAGUE_COUNTRY_SD'),
	('se', 'COM_JOOMLEAGUE_COUNTRY_SE'),
	('sg', 'COM_JOOMLEAGUE_COUNTRY_SG'),
	('sh', 'COM_JOOMLEAGUE_COUNTRY_SH'),
	('si', 'COM_JOOMLEAGUE_COUNTRY_SI'),
	('sj', 'COM_JOOMLEAGUE_COUNTRY_SJ'),
	('sk', 'COM_JOOMLEAGUE_COUNTRY_SK'),
	('sl', 'COM_JOOMLEAGUE_COUNTRY_SL'),
	('sm', 'COM_JOOMLEAGUE_COUNTRY_SM'),
	('sn', 'COM_JOOMLEAGUE_COUNTRY_SN'),
	('so', 'COM_JOOMLEAGUE_COUNTRY_SO'),
	('sr', 'COM_JOOMLEAGUE_COUNTRY_SR'),
	('ss', 'COM_JOOMLEAGUE_COUNTRY_SS'),
	('st', 'COM_JOOMLEAGUE_COUNTRY_ST'),
	('sv', 'COM_JOOMLEAGUE_COUNTRY_SV'),
	('sx', 'COM_JOOMLEAGUE_COUNTRY_SX'),
	('sy', 'COM_JOOMLEAGUE_COUNTRY_SY'),
	('sz', 'COM_JOOMLEAGUE_COUNTRY_SZ'),
	('tc', 'COM_JOOMLEAGUE_COUNTRY_TC'),
	('td', 'COM_JOOMLEAGUE_COUNTRY_TD'),
	('tf', 'COM_JOOMLEAGUE_COUNTRY_TF'),
	('tg', 'COM_JOOMLEAGUE_COUNTRY_TG'),
	('th', 'COM_JOOMLEAGUE_COUNTRY_TH'),
	('tj', 'COM_JOOMLEAGUE_COUNTRY_TJ'),
	('tk', 'COM_JOOMLEAGUE_COUNTRY_TK'),
	('tl', 'COM_JOOMLEAGUE_COUNTRY_TL'),
	('tm', 'COM_JOOMLEAGUE_COUNTRY_TM'),
	('tn', 'COM_JOOMLEAGUE_COUNTRY_TN'),
	('to', 'COM_JOOMLEAGUE_COUNTRY_TO'),
	('tr', 'COM_JOOMLEAGUE_COUNTRY_TR'),
	('tt', 'COM_JOOMLEAGUE_COUNTRY_TT'),
	('tv', 'COM_JOOMLEAGUE_COUNTRY_TV'),
	('tw', 'COM_JOOMLEAGUE_COUNTRY_TW'),
	('tz', 'COM_JOOMLEAGUE_COUNTRY_TZ'),
	('ua', 'COM_JOOMLEAGUE_COUNTRY_UA'),
	('ug', 'COM_JOOMLEAGUE_COUNTRY_UG'),
	('um', 'COM_JOOMLEAGUE_COUNTRY_UM'),
	('us', 'COM_JOOMLEAGUE_COUNTRY_US'),
	('uy', 'COM_JOOMLEAGUE_COUNTRY_UY'),
	('uz', 'COM_JOOMLEAGUE_COUNTRY_UZ'),
	('va', 'COM_JOOMLEAGUE_COUNTRY_VA'),
	('vc', 'COM_JOOMLEAGUE_COUNTRY_VC'),
	('ve', 'COM_JOOMLEAGUE_COUNTRY_VE'),
	('vg', 'COM_JOOMLEAGUE_COUNTRY_VG'),
	('vi', 'COM_JOOMLEAGUE_COUNTRY_VI'),
	('vn', 'COM_JOOMLEAGUE_COUNTRY_VN'),
	('vu', 'COM_JOOMLEAGUE_COUNTRY_VU'),
	('wf', 'COM_JOOMLEAGUE_COUNTRY_WF'),
	('ws', 'COM_JOOMLEAGUE_COUNTRY_WS'),
	('xk', 'COM_JOOMLEAGUE_COUNTRY_XK'),
	('ye', 'COM_JOOMLEAGUE_COUNTRY_YE'),
	('yt', 'COM_JOOMLEAGUE_COUNTRY_YT'),
	('za', 'COM_JOOMLEAGUE_COUNTRY_ZA'),
	('zm', 'COM_JOOMLEAGUE_COUNTRY_ZM'),
	('zw', 'COM_JOOMLEAGUE_COUNTRY_ZW');

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_race_category`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_race_category` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NOT NULL,
	`name` varchar(100) NOT NULL DEFAULT '',
	`alias` varchar(255) NOT NULL DEFAULT '',
	`sex` enum('ANY','M','F','X') NOT NULL DEFAULT 'ANY',
	`age_min` SMALLINT NULL DEFAULT NULL,
	`age_max` SMALLINT NULL DEFAULT NULL,
	`ordering` INT NOT NULL DEFAULT 0,
	`published` TINYINT NOT NULL DEFAULT 1,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`created` DATETIME NULL DEFAULT NULL,
	`created_by` INT NULL DEFAULT NULL,
	`modified` DATETIME NULL DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_race_category_project` (`project_id`),
	KEY `idx_race_category_alias` (`alias`),
	CONSTRAINT `fk_jl_race_category_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_race_participant`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_race_participant` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NOT NULL,
	`person_id` INT NULL DEFAULT NULL,
	`category_id` INT NULL DEFAULT NULL,
	`bib_number` varchar(32) NOT NULL DEFAULT '',
	`club_id` INT NULL DEFAULT NULL,
	`team_id` INT NULL DEFAULT NULL,
	`country` char(3) NOT NULL DEFAULT '',
	`sex` char(1) NOT NULL DEFAULT '',
	`date_of_birth` DATE NULL DEFAULT NULL,
	`note` TEXT NULL DEFAULT NULL,
	`ordering` INT NOT NULL DEFAULT 0,
	`published` TINYINT NOT NULL DEFAULT 1,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`created` DATETIME NULL DEFAULT NULL,
	`created_by` INT NULL DEFAULT NULL,
	`modified` DATETIME NULL DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `idx_race_participant_bib` (`project_id`, `bib_number`),
	KEY `idx_race_participant_person` (`person_id`),
	KEY `idx_race_participant_category` (`category_id`),
	KEY `idx_race_participant_club` (`club_id`),
	KEY `idx_race_participant_team` (`team_id`),
	CONSTRAINT `fk_jl_race_participant_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_jl_race_participant_person` FOREIGN KEY (`person_id`) REFERENCES `#__joomleague_person` (`id`) ON DELETE SET NULL,
	CONSTRAINT `fk_jl_race_participant_category` FOREIGN KEY (`category_id`) REFERENCES `#__joomleague_race_category` (`id`) ON DELETE SET NULL,
	CONSTRAINT `fk_jl_race_participant_club` FOREIGN KEY (`club_id`) REFERENCES `#__joomleague_club` (`id`) ON DELETE SET NULL,
	CONSTRAINT `fk_jl_race_participant_team` FOREIGN KEY (`team_id`) REFERENCES `#__joomleague_team` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_race_result`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_race_result` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`project_id` INT NOT NULL,
	`round_id` INT NULL DEFAULT NULL,
	`participant_id` INT NOT NULL,
	`person_id` INT NULL DEFAULT NULL,
	`category_id` INT NULL DEFAULT NULL,
	`overall_place` INT NULL DEFAULT NULL,
	`category_place` INT NULL DEFAULT NULL,
	`sex_place` INT NULL DEFAULT NULL,
	`bib_number` varchar(32) NOT NULL DEFAULT '',
	`start_time` DATETIME NULL DEFAULT NULL,
	`finish_time` DATETIME NULL DEFAULT NULL,
	`duration_ms` BIGINT NULL DEFAULT NULL,
	`duration_text` varchar(32) NOT NULL DEFAULT '',
	`status` enum('FINISHED','DNS','DNF','DSQ','NC') NOT NULL DEFAULT 'FINISHED',
	`status_note` varchar(255) NOT NULL DEFAULT '',
	`ordering` INT NOT NULL DEFAULT 0,
	`published` TINYINT NOT NULL DEFAULT 1,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`created` DATETIME NULL DEFAULT NULL,
	`created_by` INT NULL DEFAULT NULL,
	`modified` DATETIME NULL DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `idx_race_result_round_participant` (`round_id`, `participant_id`),
	KEY `idx_race_result_project` (`project_id`),
	KEY `idx_race_result_participant` (`participant_id`),
	KEY `idx_race_result_person` (`person_id`),
	KEY `idx_race_result_category` (`category_id`),
	KEY `idx_race_result_duration` (`status`, `duration_ms`),
	CONSTRAINT `fk_jl_race_result_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_jl_race_result_round` FOREIGN KEY (`round_id`) REFERENCES `#__joomleague_round` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_jl_race_result_participant` FOREIGN KEY (`participant_id`) REFERENCES `#__joomleague_race_participant` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_jl_race_result_person` FOREIGN KEY (`person_id`) REFERENCES `#__joomleague_person` (`id`) ON DELETE SET NULL,
	CONSTRAINT `fk_jl_race_result_category` FOREIGN KEY (`category_id`) REFERENCES `#__joomleague_race_category` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__joomleague_version` (`major`, `minor`, `build`, `count`, `revision`, `file`, `version`)
VALUES (0, 35, 2, 0, '', 'install.mysql.utf8.sql', '0.35.2');
