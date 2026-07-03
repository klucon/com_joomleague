ALTER TABLE `#__joomleague_club` ADD KEY `country` (`country`);
ALTER TABLE `#__joomleague_league` ADD KEY `country` (`country`);
ALTER TABLE `#__joomleague_person` ADD KEY `country` (`country`);
ALTER TABLE `#__joomleague_person` ADD KEY `address_country` (`address_country`);
ALTER TABLE `#__joomleague_playground` ADD KEY `country` (`country`);

UPDATE `#__joomleague_version`
SET `major` = 0,
	`minor` = 35,
	`build` = 2,
	`count` = 0,
	`revision` = '',
	`file` = '0.35.2.sql',
	`version` = '0.35.2';
