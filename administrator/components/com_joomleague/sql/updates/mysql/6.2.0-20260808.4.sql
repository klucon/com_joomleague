ALTER TABLE `#__joomleague_club`
  ADD COLUMN `logo` VARCHAR(255) NULL DEFAULT NULL AFTER `website` /** CAN FAIL **/;

ALTER TABLE `#__joomleague_team`
  ADD COLUMN `logo` VARCHAR(255) NULL DEFAULT NULL AFTER `website` /** CAN FAIL **/;
