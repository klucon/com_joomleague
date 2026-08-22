DROP INDEX IF EXISTS "idx_jl_project_stage_state";
ALTER TABLE "#__joomleague_project_stage" DROP COLUMN IF EXISTS "lifecycle_state";
CREATE INDEX "idx_jl_project_stage_state" ON "#__joomleague_project_stage" ("project_id", "published", "ordering");
