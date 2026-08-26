ALTER TABLE `#__joomleague_competition` ADD COLUMN `access` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `published`;
ALTER TABLE `#__joomleague_season` ADD COLUMN `access` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `published`;
ALTER TABLE `#__joomleague_project` ADD COLUMN `access` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `published`;
ALTER TABLE `#__joomleague_club` ADD COLUMN `access` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `published`;
ALTER TABLE `#__joomleague_team` ADD COLUMN `access` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `published`;
ALTER TABLE `#__joomleague_person` ADD COLUMN `access` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `published`;
ALTER TABLE `#__joomleague_venue` ADD COLUMN `access` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `published`;
