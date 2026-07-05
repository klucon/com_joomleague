ALTER TABLE `#__joomleague_prediction_game`
	ADD FOREIGN KEY IF NOT EXISTS `fk_jl_prediction_game_project` (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE,
	ADD FOREIGN KEY IF NOT EXISTS `fk_jl_prediction_game_checked_out` (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD FOREIGN KEY IF NOT EXISTS `fk_jl_prediction_game_created_by` (`created_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL,
	ADD FOREIGN KEY IF NOT EXISTS `fk_jl_prediction_game_modified_by` (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL;

ALTER TABLE `#__joomleague_prediction_tip`
	ADD FOREIGN KEY IF NOT EXISTS `fk_jl_prediction_tip_game` (`game_id`) REFERENCES `#__joomleague_prediction_game` (`id`) ON DELETE CASCADE,
	ADD FOREIGN KEY IF NOT EXISTS `fk_jl_prediction_tip_match` (`match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE CASCADE,
	ADD FOREIGN KEY IF NOT EXISTS `fk_jl_prediction_tip_user` (`user_id`) REFERENCES `#__users` (`id`) ON DELETE CASCADE;

ALTER TABLE `#__joomleague_prediction_score`
	ADD FOREIGN KEY IF NOT EXISTS `fk_jl_prediction_score_game` (`game_id`) REFERENCES `#__joomleague_prediction_game` (`id`) ON DELETE CASCADE,
	ADD FOREIGN KEY IF NOT EXISTS `fk_jl_prediction_score_user` (`user_id`) REFERENCES `#__users` (`id`) ON DELETE CASCADE,
	ADD FOREIGN KEY IF NOT EXISTS `fk_jl_prediction_score_round` (`round_id`) REFERENCES `#__joomleague_round` (`id`) ON DELETE CASCADE;
