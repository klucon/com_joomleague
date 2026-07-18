ALTER TABLE `#__joomleague_match_player`
	ADD COLUMN `is_substitute` TINYINT NOT NULL DEFAULT 0 AFTER `project_position_id`;

ALTER TABLE `#__joomleague_match_event`
	ADD COLUMN `external_person_name` varchar(100) NOT NULL DEFAULT '' AFTER `teamplayer_id`;

ALTER TABLE `#__joomleague_match_referee`
	ADD COLUMN `external_referee_name` varchar(100) NOT NULL DEFAULT '' AFTER `project_referee_id`;

UPDATE `#__joomleague_position`
SET `persontype` = 3
WHERE `name` IN ('COM_JOOMLEAGUE_F_MATCH_DELEGATE', 'COM_JOOMLEAGUE_F_REFEREE_OBSERVER')
	AND `persontype` = 4;
