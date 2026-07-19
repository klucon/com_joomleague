ALTER TABLE `#__joomleague_club`
	ADD COLUMN `info` text AFTER `standard_playground`;

ALTER TABLE `#__joomleague_playground`
	ADD COLUMN `info` text AFTER `picture`;

ALTER TABLE `#__joomleague_team`
	MODIFY `info` text;

ALTER TABLE `#__joomleague_project_team`
	MODIFY `info` text;
