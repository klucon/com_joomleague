SET @jl_fk_exists = (
	SELECT COUNT(*)
	FROM information_schema.TABLE_CONSTRAINTS
	WHERE CONSTRAINT_SCHEMA = DATABASE()
		AND TABLE_NAME = '#__joomleague_prediction_game'
		AND CONSTRAINT_NAME = 'fk_jl_prediction_game_project'
);
SET @jl_sql = IF(@jl_fk_exists = 0, 'ALTER TABLE `#__joomleague_prediction_game` ADD CONSTRAINT `fk_jl_prediction_game_project` FOREIGN KEY (`project_id`) REFERENCES `#__joomleague_project` (`id`) ON DELETE CASCADE', 'SELECT 1');
PREPARE jl_stmt FROM @jl_sql;
EXECUTE jl_stmt;
DEALLOCATE PREPARE jl_stmt;

SET @jl_fk_exists = (
	SELECT COUNT(*)
	FROM information_schema.TABLE_CONSTRAINTS
	WHERE CONSTRAINT_SCHEMA = DATABASE()
		AND TABLE_NAME = '#__joomleague_prediction_game'
		AND CONSTRAINT_NAME = 'fk_jl_prediction_game_checked_out'
);
SET @jl_sql = IF(@jl_fk_exists = 0, 'ALTER TABLE `#__joomleague_prediction_game` ADD CONSTRAINT `fk_jl_prediction_game_checked_out` FOREIGN KEY (`checked_out`) REFERENCES `#__users` (`id`) ON DELETE SET NULL', 'SELECT 1');
PREPARE jl_stmt FROM @jl_sql;
EXECUTE jl_stmt;
DEALLOCATE PREPARE jl_stmt;

SET @jl_fk_exists = (
	SELECT COUNT(*)
	FROM information_schema.TABLE_CONSTRAINTS
	WHERE CONSTRAINT_SCHEMA = DATABASE()
		AND TABLE_NAME = '#__joomleague_prediction_game'
		AND CONSTRAINT_NAME = 'fk_jl_prediction_game_created_by'
);
SET @jl_sql = IF(@jl_fk_exists = 0, 'ALTER TABLE `#__joomleague_prediction_game` ADD CONSTRAINT `fk_jl_prediction_game_created_by` FOREIGN KEY (`created_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL', 'SELECT 1');
PREPARE jl_stmt FROM @jl_sql;
EXECUTE jl_stmt;
DEALLOCATE PREPARE jl_stmt;

SET @jl_fk_exists = (
	SELECT COUNT(*)
	FROM information_schema.TABLE_CONSTRAINTS
	WHERE CONSTRAINT_SCHEMA = DATABASE()
		AND TABLE_NAME = '#__joomleague_prediction_game'
		AND CONSTRAINT_NAME = 'fk_jl_prediction_game_modified_by'
);
SET @jl_sql = IF(@jl_fk_exists = 0, 'ALTER TABLE `#__joomleague_prediction_game` ADD CONSTRAINT `fk_jl_prediction_game_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `#__users` (`id`) ON DELETE SET NULL', 'SELECT 1');
PREPARE jl_stmt FROM @jl_sql;
EXECUTE jl_stmt;
DEALLOCATE PREPARE jl_stmt;

SET @jl_fk_exists = (
	SELECT COUNT(*)
	FROM information_schema.TABLE_CONSTRAINTS
	WHERE CONSTRAINT_SCHEMA = DATABASE()
		AND TABLE_NAME = '#__joomleague_prediction_tip'
		AND CONSTRAINT_NAME = 'fk_jl_prediction_tip_game'
);
SET @jl_sql = IF(@jl_fk_exists = 0, 'ALTER TABLE `#__joomleague_prediction_tip` ADD CONSTRAINT `fk_jl_prediction_tip_game` FOREIGN KEY (`game_id`) REFERENCES `#__joomleague_prediction_game` (`id`) ON DELETE CASCADE', 'SELECT 1');
PREPARE jl_stmt FROM @jl_sql;
EXECUTE jl_stmt;
DEALLOCATE PREPARE jl_stmt;

SET @jl_fk_exists = (
	SELECT COUNT(*)
	FROM information_schema.TABLE_CONSTRAINTS
	WHERE CONSTRAINT_SCHEMA = DATABASE()
		AND TABLE_NAME = '#__joomleague_prediction_tip'
		AND CONSTRAINT_NAME = 'fk_jl_prediction_tip_match'
);
SET @jl_sql = IF(@jl_fk_exists = 0, 'ALTER TABLE `#__joomleague_prediction_tip` ADD CONSTRAINT `fk_jl_prediction_tip_match` FOREIGN KEY (`match_id`) REFERENCES `#__joomleague_match` (`id`) ON DELETE CASCADE', 'SELECT 1');
PREPARE jl_stmt FROM @jl_sql;
EXECUTE jl_stmt;
DEALLOCATE PREPARE jl_stmt;

SET @jl_fk_exists = (
	SELECT COUNT(*)
	FROM information_schema.TABLE_CONSTRAINTS
	WHERE CONSTRAINT_SCHEMA = DATABASE()
		AND TABLE_NAME = '#__joomleague_prediction_tip'
		AND CONSTRAINT_NAME = 'fk_jl_prediction_tip_user'
);
SET @jl_sql = IF(@jl_fk_exists = 0, 'ALTER TABLE `#__joomleague_prediction_tip` ADD CONSTRAINT `fk_jl_prediction_tip_user` FOREIGN KEY (`user_id`) REFERENCES `#__users` (`id`) ON DELETE CASCADE', 'SELECT 1');
PREPARE jl_stmt FROM @jl_sql;
EXECUTE jl_stmt;
DEALLOCATE PREPARE jl_stmt;

SET @jl_fk_exists = (
	SELECT COUNT(*)
	FROM information_schema.TABLE_CONSTRAINTS
	WHERE CONSTRAINT_SCHEMA = DATABASE()
		AND TABLE_NAME = '#__joomleague_prediction_score'
		AND CONSTRAINT_NAME = 'fk_jl_prediction_score_game'
);
SET @jl_sql = IF(@jl_fk_exists = 0, 'ALTER TABLE `#__joomleague_prediction_score` ADD CONSTRAINT `fk_jl_prediction_score_game` FOREIGN KEY (`game_id`) REFERENCES `#__joomleague_prediction_game` (`id`) ON DELETE CASCADE', 'SELECT 1');
PREPARE jl_stmt FROM @jl_sql;
EXECUTE jl_stmt;
DEALLOCATE PREPARE jl_stmt;

SET @jl_fk_exists = (
	SELECT COUNT(*)
	FROM information_schema.TABLE_CONSTRAINTS
	WHERE CONSTRAINT_SCHEMA = DATABASE()
		AND TABLE_NAME = '#__joomleague_prediction_score'
		AND CONSTRAINT_NAME = 'fk_jl_prediction_score_user'
);
SET @jl_sql = IF(@jl_fk_exists = 0, 'ALTER TABLE `#__joomleague_prediction_score` ADD CONSTRAINT `fk_jl_prediction_score_user` FOREIGN KEY (`user_id`) REFERENCES `#__users` (`id`) ON DELETE CASCADE', 'SELECT 1');
PREPARE jl_stmt FROM @jl_sql;
EXECUTE jl_stmt;
DEALLOCATE PREPARE jl_stmt;

SET @jl_fk_exists = (
	SELECT COUNT(*)
	FROM information_schema.TABLE_CONSTRAINTS
	WHERE CONSTRAINT_SCHEMA = DATABASE()
		AND TABLE_NAME = '#__joomleague_prediction_score'
		AND CONSTRAINT_NAME = 'fk_jl_prediction_score_round'
);
SET @jl_sql = IF(@jl_fk_exists = 0, 'ALTER TABLE `#__joomleague_prediction_score` ADD CONSTRAINT `fk_jl_prediction_score_round` FOREIGN KEY (`round_id`) REFERENCES `#__joomleague_round` (`id`) ON DELETE CASCADE', 'SELECT 1');
PREPARE jl_stmt FROM @jl_sql;
EXECUTE jl_stmt;
DEALLOCATE PREPARE jl_stmt;
