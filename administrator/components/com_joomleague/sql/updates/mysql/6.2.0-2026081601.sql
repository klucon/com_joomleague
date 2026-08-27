ALTER TABLE `#__joomleague_person`
  ADD COLUMN `club_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `contact_id` /** CAN FAIL **/;
ALTER TABLE `#__joomleague_person`
  ADD CONSTRAINT `fk_jl_person_club` FOREIGN KEY (`club_id`) REFERENCES `#__joomleague_club` (`id`) ON DELETE SET NULL /** CAN FAIL **/;
ALTER TABLE `#__joomleague_person`
  ADD INDEX `idx_jl_person_club` (`club_id`) /** CAN FAIL **/;
