ALTER TABLE "#__joomleague_stage_entry" ADD COLUMN "manual_assignment" SMALLINT NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS "#__joomleague_stage_transition_run" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "transition_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "input_checksum" CHAR(64) NOT NULL, "selector_snapshot_json" TEXT NOT NULL, "resolved_entries_json" TEXT NOT NULL, "resolved_count" INTEGER NOT NULL DEFAULT 0,
  "status" VARCHAR(30) NOT NULL DEFAULT 'applied', "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_stage_transition_run_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_stage_transition_run_input" UNIQUE ("transition_id", "input_checksum"),
  CONSTRAINT "fk_jl_stage_transition_run_transition" FOREIGN KEY ("transition_id") REFERENCES "#__joomleague_stage_transition" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_transition_run_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_stage_transition_run_status" CHECK ("status" IN ('applied'))
);
CREATE INDEX "idx_jl_stage_transition_run_project" ON "#__joomleague_stage_transition_run" ("project_id", "created") /** CAN FAIL **/;

CREATE TABLE IF NOT EXISTS "#__joomleague_stage_transition_assignment" (
  "transition_id" BIGINT NOT NULL, "target_stage_id" BIGINT NOT NULL, "project_entry_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "run_id" BIGINT NOT NULL, "target_seed" INTEGER NULL, "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY ("transition_id", "project_entry_id"),
  CONSTRAINT "fk_jl_stage_transition_assignment_transition" FOREIGN KEY ("transition_id") REFERENCES "#__joomleague_stage_transition" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_transition_assignment_stage" FOREIGN KEY ("target_stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_transition_assignment_entry" FOREIGN KEY ("project_entry_id", "project_id") REFERENCES "#__joomleague_project_entry" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_transition_assignment_run" FOREIGN KEY ("run_id") REFERENCES "#__joomleague_stage_transition_run" ("id") ON DELETE CASCADE
);
CREATE INDEX "idx_jl_stage_transition_assignment_target" ON "#__joomleague_stage_transition_assignment" ("target_stage_id", "project_id", "project_entry_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_stage_transition_assignment_run" ON "#__joomleague_stage_transition_assignment" ("run_id") /** CAN FAIL **/;
