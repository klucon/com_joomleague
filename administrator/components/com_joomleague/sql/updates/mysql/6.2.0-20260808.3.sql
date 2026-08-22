ALTER TABLE `#__joomleague_project`
  MODIFY `current_round_mode` VARCHAR(30) NOT NULL DEFAULT 'start';

ALTER TABLE `#__joomleague_project`
  MODIFY `auto_advance_seconds` INT UNSIGNED NULL DEFAULT 7200;
