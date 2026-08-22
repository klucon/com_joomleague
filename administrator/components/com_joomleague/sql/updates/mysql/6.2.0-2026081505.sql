ALTER TABLE `#__joomleague_project_stage`
  DROP INDEX `idx_jl_project_stage_state`,
  DROP COLUMN `lifecycle_state`,
  ADD INDEX `idx_jl_project_stage_state` (`project_id`, `published`, `ordering`);
