CREATE TABLE IF NOT EXISTS "#__joomleague_schedule_generation" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "project_id" BIGINT NOT NULL, "stage_id" BIGINT NOT NULL,
  "input_checksum" CHAR(64) NOT NULL, "options_json" TEXT NOT NULL, "round_count" INTEGER NOT NULL DEFAULT 0, "match_count" INTEGER NOT NULL DEFAULT 0,
  "conflict_count" INTEGER NOT NULL DEFAULT 0, "status" VARCHAR(30) NOT NULL DEFAULT 'applied', "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_schedule_generation_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_schedule_generation_input" UNIQUE ("stage_id", "input_checksum"),
  CONSTRAINT "fk_jl_schedule_generation_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_schedule_generation_stage" FOREIGN KEY ("stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_schedule_generation_status" CHECK ("status" IN ('applied'))
);
CREATE INDEX "idx_jl_schedule_generation_project" ON "#__joomleague_schedule_generation" ("project_id", "created") /** CAN FAIL **/;
CREATE TABLE IF NOT EXISTS "#__joomleague_schedule_generation_match" (
  "generation_id" BIGINT NOT NULL, "match_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL, "round_sequence" INTEGER NOT NULL, "match_sequence" INTEGER NOT NULL,
  PRIMARY KEY ("generation_id", "match_id"), CONSTRAINT "uq_jl_schedule_generation_match" UNIQUE ("match_id"),
  CONSTRAINT "fk_jl_schedule_generation_match_generation" FOREIGN KEY ("generation_id") REFERENCES "#__joomleague_schedule_generation" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_schedule_generation_match_match" FOREIGN KEY ("match_id", "project_id") REFERENCES "#__joomleague_project_match" ("id", "project_id") ON DELETE CASCADE
);
