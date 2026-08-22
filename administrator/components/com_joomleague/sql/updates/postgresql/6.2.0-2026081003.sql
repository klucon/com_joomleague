CREATE TABLE IF NOT EXISTS "#__joomleague_project_round" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "project_id" BIGINT NOT NULL, "stage_id" BIGINT NOT NULL,
  "name" VARCHAR(255) NOT NULL, "alias" VARCHAR(255) NOT NULL DEFAULT '', "code" VARCHAR(100) NOT NULL,
  "round_type" VARCHAR(100) NOT NULL DEFAULT 'standard', "sequence_number" INTEGER NOT NULL,
  "start_date" DATE NULL, "end_date" DATE NULL, "lifecycle_state" VARCHAR(30) NOT NULL DEFAULT 'draft',
  "description" TEXT NULL, "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 0, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0, "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_round_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_project_round_code" UNIQUE ("stage_id", "code"),
  CONSTRAINT "uq_jl_project_round_sequence" UNIQUE ("stage_id", "sequence_number"), CONSTRAINT "uq_jl_project_round_owner" UNIQUE ("id", "project_id"),
  CONSTRAINT "fk_jl_project_round_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_round_stage" FOREIGN KEY ("stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_project_round_sequence" CHECK ("sequence_number" > 0),
  CONSTRAINT "chk_jl_project_round_dates" CHECK ("end_date" IS NULL OR "start_date" IS NULL OR "end_date" >= "start_date")
);
CREATE INDEX "idx_jl_project_round_state" ON "#__joomleague_project_round" ("project_id", "stage_id", "published", "ordering") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_round_dates" ON "#__joomleague_project_round" ("start_date", "end_date") /** CAN FAIL **/;
