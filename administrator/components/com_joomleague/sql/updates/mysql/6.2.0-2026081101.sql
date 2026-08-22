ALTER TABLE `#__joomleague_match_participant`
  ADD COLUMN `participation_status` VARCHAR(100) NULL DEFAULT NULL AFTER `slot_number`;

ALTER TABLE `#__joomleague_match_score_segment`
  ADD COLUMN `segment_type_ordinal` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `level_code`,
  ADD COLUMN `root_marker` TINYINT GENERATED ALWAYS AS (CASE WHEN `parent_id` IS NULL THEN 0 ELSE NULL END) STORED AFTER `sequence_number`,
  ADD UNIQUE KEY `uq_jl_match_score_segment_root` (`match_id`, `root_marker`);
