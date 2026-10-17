ALTER TABLE `#__joomleague_sport_position` ADD COLUMN `role_domain` VARCHAR(100) NULL DEFAULT NULL AFTER `person_type`;

UPDATE `#__joomleague_sport_position`
SET `role_domain` = CASE
  WHEN `person_type` IN ('player', 'participant') THEN 'participant'
  WHEN `person_type` = 'staff' THEN 'team_staff'
  WHEN `code` IN ('arbiter','chief_arbiter','chair_umpire','course_referee','finish_referee','first_referee','fourth_official','judge','lane_official','line_judge','line_umpire','linesman_1','linesman_2','referee','second_referee','start_referee','steward','touch_judge','var_referee','assistant_referee_1','assistant_referee_2') THEN 'adjudicator'
  WHEN `code` IN ('admin','caller','delegate','marker','observer','scorer','table_official','timekeeper','video_goal_judge') THEN 'competition_official'
  ELSE 'event_staff'
END;

ALTER TABLE `#__joomleague_sport_position` MODIFY `role_domain` VARCHAR(100) NOT NULL;

ALTER TABLE `#__joomleague_project_actor_role` ADD COLUMN `role_domain` VARCHAR(100) NULL DEFAULT NULL AFTER `person_type`;
UPDATE `#__joomleague_project_actor_role` SET `role_domain` = CASE
  WHEN `role_code` IN ('arbiter','chief_arbiter','chair_umpire','course_referee','finish_referee','first_referee','fourth_official','judge','lane_official','line_judge','line_umpire','linesman_1','linesman_2','referee','second_referee','start_referee','steward','touch_judge','var_referee','assistant_referee_1','assistant_referee_2') THEN 'adjudicator'
  WHEN `role_code` IN ('admin','caller','delegate','marker','observer','scorer','table_official','timekeeper','video_goal_judge') THEN 'competition_official'
  ELSE 'event_staff'
END;
ALTER TABLE `#__joomleague_project_actor_role` MODIFY `role_domain` VARCHAR(100) NOT NULL;

ALTER TABLE `#__joomleague_match_actor_role`
  ADD COLUMN `role_domain` VARCHAR(100) NULL DEFAULT NULL AFTER `person_type`,
  ADD COLUMN `delegation_status` VARCHAR(30) NOT NULL DEFAULT 'delegated' AFTER `role_domain`,
  ADD UNIQUE KEY `uq_jl_match_actor_role_scope` (`id`, `match_id`);
UPDATE `#__joomleague_match_actor_role` SET `role_domain` = CASE
  WHEN `role_code` IN ('arbiter','chief_arbiter','chair_umpire','course_referee','finish_referee','first_referee','fourth_official','judge','lane_official','line_judge','line_umpire','linesman_1','linesman_2','referee','second_referee','start_referee','steward','touch_judge','var_referee','assistant_referee_1','assistant_referee_2') THEN 'adjudicator'
  WHEN `role_code` IN ('admin','caller','delegate','marker','observer','scorer','table_official','timekeeper','video_goal_judge') THEN 'competition_official'
  ELSE 'event_staff'
END;
ALTER TABLE `#__joomleague_match_actor_role` MODIFY `role_domain` VARCHAR(100) NOT NULL;

CREATE TABLE IF NOT EXISTS `#__joomleague_match_actor_role_change` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `match_id` BIGINT UNSIGNED NOT NULL, `project_id` BIGINT UNSIGNED NOT NULL,
  `outgoing_match_actor_role_id` BIGINT UNSIGNED NULL DEFAULT NULL, `incoming_match_actor_role_id` BIGINT UNSIGNED NOT NULL,
  `role_code` VARCHAR(100) NOT NULL, `role_domain` VARCHAR(100) NOT NULL, `sequence_number` INT UNSIGNED NOT NULL,
  `phase_code` VARCHAR(100) NULL DEFAULT NULL, `phase_sequence` INT UNSIGNED NULL DEFAULT NULL, `clock_value` DECIMAL(30,9) NULL DEFAULT NULL, `clock_unit` VARCHAR(50) NULL DEFAULT NULL,
  `occurred_at` DATETIME NULL DEFAULT NULL, `reason_code` VARCHAR(100) NOT NULL DEFAULT 'other', `notes` TEXT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_jl_match_actor_change_uuid` (`uuid`), UNIQUE KEY `uq_jl_match_actor_change_sequence` (`match_id`, `sequence_number`),
  KEY `idx_jl_match_actor_change_role` (`match_id`, `role_domain`, `role_code`), KEY `idx_jl_match_actor_change_outgoing` (`outgoing_match_actor_role_id`, `match_id`), KEY `idx_jl_match_actor_change_incoming` (`incoming_match_actor_role_id`, `match_id`),
  CONSTRAINT `fk_jl_match_actor_change_match` FOREIGN KEY (`match_id`, `project_id`) REFERENCES `#__joomleague_project_match` (`id`, `project_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_match_actor_change_outgoing` FOREIGN KEY (`outgoing_match_actor_role_id`, `match_id`) REFERENCES `#__joomleague_match_actor_role` (`id`, `match_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_jl_match_actor_change_incoming` FOREIGN KEY (`incoming_match_actor_role_id`, `match_id`) REFERENCES `#__joomleague_match_actor_role` (`id`, `match_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_jl_match_actor_change_people` CHECK (`outgoing_match_actor_role_id` IS NULL OR `outgoing_match_actor_role_id` <> `incoming_match_actor_role_id`),
  CONSTRAINT `chk_jl_match_actor_change_sequence` CHECK (`sequence_number` > 0), CONSTRAINT `chk_jl_match_actor_change_phase` CHECK (`phase_sequence` IS NULL OR `phase_sequence` > 0),
  CONSTRAINT `chk_jl_match_actor_change_clock` CHECK (`clock_value` IS NULL OR `clock_value` >= 0), CONSTRAINT `chk_jl_match_actor_change_clock_unit` CHECK ((`clock_value` IS NULL AND `clock_unit` IS NULL) OR (`clock_value` IS NOT NULL AND `clock_unit` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
