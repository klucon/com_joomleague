ALTER TABLE `#__joomleague_club` MODIFY `notes` text NULL;

ALTER TABLE `#__joomleague_division` MODIFY `notes` text NULL;

ALTER TABLE `#__joomleague_match` MODIFY `summary` text NULL;
ALTER TABLE `#__joomleague_match` MODIFY `preview` text NULL;

ALTER TABLE `#__joomleague_match_event` MODIFY `notes` text NULL;

ALTER TABLE `#__joomleague_person` MODIFY `notes` text NULL;
ALTER TABLE `#__joomleague_person` MODIFY `email` varchar(50) NOT NULL DEFAULT "";
ALTER TABLE `#__joomleague_person` MODIFY `website` varchar(250) NOT NULL DEFAULT "";
ALTER TABLE `#__joomleague_person` MODIFY `position_id` INT DEFAULT NULL;

ALTER TABLE `#__joomleague_playground` MODIFY `notes` text NULL;

ALTER TABLE `#__joomleague_project_team` MODIFY `division_id` INT DEFAULT NULL;
ALTER TABLE `#__joomleague_project_team` MODIFY `notes` text NULL;
ALTER TABLE `#__joomleague_project_team` MODIFY `reason` varchar(150) NOT NULL DEFAULT "";

ALTER TABLE `#__joomleague_statistic` MODIFY `class` varchar(50) NOT NULL DEFAULT "";
ALTER TABLE `#__joomleague_statistic` MODIFY `calculated` TINYINT NOT NULL DEFAULT 0;
ALTER TABLE `#__joomleague_statistic` MODIFY `params` text NULL;
ALTER TABLE `#__joomleague_statistic` MODIFY `baseparams` text NULL;

ALTER TABLE `#__joomleague_team` MODIFY `notes` text NULL;

ALTER TABLE `#__joomleague_team_player` MODIFY `project_position_id` INT DEFAULT NULL;
ALTER TABLE `#__joomleague_team_player` MODIFY `notes` text NULL;
ALTER TABLE `#__joomleague_team_player` MODIFY `injury_detail` varchar(255) NOT NULL DEFAULT "";
ALTER TABLE `#__joomleague_team_player` MODIFY `suspension_detail` varchar(255) NOT NULL DEFAULT "";
ALTER TABLE `#__joomleague_team_player` MODIFY `away_detail` varchar(255) NOT NULL DEFAULT "";

ALTER TABLE `#__joomleague_team_staff` MODIFY `project_position_id` INT DEFAULT NULL;
ALTER TABLE `#__joomleague_team_staff` MODIFY `notes` text NULL;
ALTER TABLE `#__joomleague_team_staff` MODIFY `injury_detail` varchar(255) NOT NULL DEFAULT "";
ALTER TABLE `#__joomleague_team_staff` MODIFY `suspension_detail` varchar(255) NOT NULL DEFAULT "";
ALTER TABLE `#__joomleague_team_staff` MODIFY `away_detail` varchar(255) NOT NULL DEFAULT "";

ALTER TABLE `#__joomleague_team_trainingdata` MODIFY `notes` text NULL;

ALTER TABLE `#__joomleague_template_config` MODIFY `params` text NULL;

ALTER TABLE `#__joomleague_version` MODIFY `revision` varchar(128) NOT NULL DEFAULT "";
