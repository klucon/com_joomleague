ALTER TABLE `#__joomleague_sports_type`
	MODIFY `icon` VARCHAR(255) NOT NULL DEFAULT '';

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
	`country` varchar(3) DEFAULT NULL,
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
	`notes` text NOT NULL,
	`extended` text,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	`dissolved` DATE NULL DEFAULT NULL,
	`asset_id` INT UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`)
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
	`notes` text NOT NULL,
	`parent_id` INT DEFAULT NULL,
	`picture` varchar(128) NOT NULL DEFAULT '',
	`published` TINYINT NOT NULL DEFAULT 1,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
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
	`country` varchar(3) DEFAULT NULL,
	`extended` text,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomleague_match`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_match` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`round_id` INT NULL DEFAULT NULL,
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
	`summary` text NOT NULL,
	`show_report` TINYINT NOT NULL DEFAULT 0,
	`preview` text NOT NULL,
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
	`teamplayer_id2` INT NULL DEFAULT NULL,
	`event_time` varchar(20) NOT NULL DEFAULT '',
	`event_type_id` INT NULL DEFAULT NULL,
	`event_sum` double DEFAULT NULL,
	`notice` varchar(64) NOT NULL DEFAULT '',
	`notes` text NOT NULL,
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
	`country` varchar(3) DEFAULT NULL,
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
	`info` varchar(255) NOT NULL DEFAULT '',
	`notes` text NOT NULL,
	`phone` varchar(20) NOT NULL DEFAULT '',
	`mobile` varchar(20) NOT NULL DEFAULT '',
	`email` varchar(50) NOT NULL,
	`website` varchar(250) NOT NULL,
	`address` varchar(100) NOT NULL DEFAULT '',
	`zipcode` varchar(10) NOT NULL DEFAULT '',
	`location` varchar(50) NOT NULL DEFAULT '',
	`state` varchar(50) NOT NULL DEFAULT '',
	`address_country` varchar(3) DEFAULT NULL,
	`extended` text,
	`position_id` INT DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 0,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `contact_id` (`contact_id`),
	KEY `position_id` (`position_id`)
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
	`country` varchar(3) DEFAULT NULL,
	`max_visitors` INT DEFAULT NULL,
	`website` varchar(250) NOT NULL DEFAULT '',
	`picture` varchar(128) NOT NULL DEFAULT '',
	`notes` text NOT NULL,
	`club_id` INT NULL DEFAULT NULL,
	`extended` text,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	KEY `club_id` (`club_id`)
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
	`project_type` enum('SIMPLE_LEAGUE','DIVISIONS_LEAGUE','TOURNAMENT_MODE','FRIENDLY_MATCHES') NOT NULL DEFAULT 'SIMPLE_LEAGUE',
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
	`person_id` INT NULL DEFAULT NULL,
	`project_position_id` INT DEFAULT NULL,
	`notes` text NOT NULL,
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
	`info` varchar(255) NOT NULL DEFAULT '',
	`picture` varchar(128) DEFAULT NULL,
	`notes` text NOT NULL,
	`standard_playground` INT NULL DEFAULT NULL,
	`reason` varchar(150) NOT NULL,
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
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
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
	`icon` varchar(255) NOT NULL DEFAULT '',
	`published` TINYINT NOT NULL DEFAULT 1,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
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
	`class` varchar(50) NOT NULL COMMENT 'must be the name of the class handling it',
	`calculated` TINYINT NOT NULL,
	`params` text NOT NULL,
	`baseparams` text NOT NULL,
	`note` varchar(100) DEFAULT NULL,
	`sports_type_id` INT NULL DEFAULT NULL,
	`published` TINYINT NOT NULL DEFAULT 1,
	`ordering` INT NOT NULL DEFAULT 0,
	`checked_out` INT NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	`modified_by` INT NULL DEFAULT NULL,
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
	`info` varchar(255) NOT NULL DEFAULT '',
	`notes` text NOT NULL,
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
	`notes` text NOT NULL,
	`picture` varchar(128) NOT NULL DEFAULT '',
	`extended` text,
	`injury` TINYINT NOT NULL DEFAULT 0,
	`injury_date` INT NULL DEFAULT NULL,
	`injury_end` INT NULL DEFAULT NULL,
	`injury_detail` varchar(255) NOT NULL,
	`injury_date_start` DATE NULL DEFAULT NULL,
	`injury_date_end` DATE NULL DEFAULT NULL,
	`suspension` TINYINT NOT NULL DEFAULT 0,
	`suspension_date` INT NULL DEFAULT NULL,
	`suspension_end` INT NULL DEFAULT NULL,
	`suspension_detail` varchar(255) NOT NULL,
	`susp_date_start` DATE NULL DEFAULT NULL,
	`susp_date_end` DATE NULL DEFAULT NULL,
	`away` TINYINT NOT NULL DEFAULT 0,
	`away_date` INT NULL DEFAULT NULL,
	`away_end` INT NULL DEFAULT NULL,
	`away_detail` varchar(255) NOT NULL,
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
	`notes` text NOT NULL,
	`injury` TINYINT NOT NULL DEFAULT 0,
	`injury_date` INT NULL DEFAULT NULL,
	`injury_end` INT NULL DEFAULT NULL,
	`injury_detail` varchar(255) NOT NULL,
	`injury_date_start` DATE NULL DEFAULT NULL,
	`injury_date_end` DATE NULL DEFAULT NULL,
	`suspension` TINYINT NOT NULL DEFAULT 0,
	`suspension_date` INT NULL DEFAULT NULL,
	`suspension_end` INT NULL DEFAULT NULL,
	`suspension_detail` varchar(255) NOT NULL,
	`susp_date_start` DATE NULL DEFAULT NULL,
	`susp_date_end` DATE NULL DEFAULT NULL,
	`away` TINYINT NOT NULL DEFAULT 0,
	`away_date` INT NULL DEFAULT NULL,
	`away_end` INT NULL DEFAULT NULL,
	`away_detail` varchar(255) NOT NULL,
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
	`notes` text NOT NULL,
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
	`params` text NOT NULL,
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
-- Table structure for table `#__joomleague_version`
--

CREATE TABLE IF NOT EXISTS `#__joomleague_version` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`major` INT NOT NULL,
	`minor` INT NOT NULL,
	`build` INT NOT NULL,
	`count` INT NOT NULL,
	`revision` varchar(128) NOT NULL,
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
	ADD CONSTRAINT `fk_jl_division_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_eventtype`
	ADD CONSTRAINT `fk_jl_eventtype_parent` FOREIGN KEY (`parent`) REFERENCES `#__joomleague_eventtype` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_eventtype_sport` FOREIGN KEY (`sports_type_id`) REFERENCES `#__joomleague_sports_type` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_eventtype_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_eventtype_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_league`
	ADD CONSTRAINT `fk_jl_league_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_league_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

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
	ADD CONSTRAINT `fk_jl_person_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_playground`
	ADD CONSTRAINT `fk_jl_playground_club` FOREIGN KEY (`club_id`) REFERENCES `#__joomleague_club` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_playground_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_playground_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_position`
	ADD CONSTRAINT `fk_jl_position_parent` FOREIGN KEY (`parent_id`) REFERENCES `#__joomleague_position` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_position_sport` FOREIGN KEY (`sports_type_id`) REFERENCES `#__joomleague_sports_type` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_position_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_position_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

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
	ADD CONSTRAINT `fk_jl_round_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_season`
	ADD CONSTRAINT `fk_jl_season_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_season_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;


ALTER TABLE `#__joomleague_statistic`
	ADD CONSTRAINT `fk_jl_statistic_sport` FOREIGN KEY (`sports_type_id`) REFERENCES `#__joomleague_sports_type` (`id`) ON DELETE RESTRICT,
	ADD CONSTRAINT `fk_jl_statistic_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `fk_jl_statistic_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

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
