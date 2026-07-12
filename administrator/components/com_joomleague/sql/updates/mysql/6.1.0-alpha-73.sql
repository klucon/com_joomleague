ALTER TABLE `#__joomleague_sports_type` ENGINE=InnoDB;
ALTER TABLE `#__joomleague_sports_type` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__joomleague_sports_type` MODIFY `name` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `#__joomleague_sports_type` MODIFY `periods` TINYINT NOT NULL DEFAULT 2;
ALTER TABLE `#__joomleague_sports_type` MODIFY `icon` VARCHAR(255) NOT NULL DEFAULT '';
