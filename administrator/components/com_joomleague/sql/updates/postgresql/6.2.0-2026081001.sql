CREATE TABLE IF NOT EXISTS "#__joomleague_project_stage" (
  "id" BIGSERIAL PRIMARY KEY,
  "uuid" CHAR(36) NOT NULL,
  "project_id" BIGINT NOT NULL,
  "parent_id" BIGINT NULL,
  "name" VARCHAR(255) NOT NULL,
  "alias" VARCHAR(255) NOT NULL DEFAULT '',
  "code" VARCHAR(100) NOT NULL,
  "stage_type" VARCHAR(100) NOT NULL,
  "sequence_number" INTEGER NULL,
  "start_date" DATE NULL,
  "end_date" DATE NULL,
  "description" TEXT NULL,
  "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 0,
  "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL,
  "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_stage_uuid" UNIQUE ("uuid"),
  CONSTRAINT "uq_jl_project_stage_code" UNIQUE ("project_id", "code"),
  CONSTRAINT "fk_jl_project_stage_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_stage_parent" FOREIGN KEY ("parent_id") REFERENCES "#__joomleague_project_stage" ("id") ON DELETE SET NULL,
  CONSTRAINT "chk_jl_project_stage_dates" CHECK ("end_date" IS NULL OR "start_date" IS NULL OR "end_date" >= "start_date")
);
CREATE INDEX "idx_jl_project_stage_parent" ON "#__joomleague_project_stage" ("parent_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_stage_state" ON "#__joomleague_project_stage" ("project_id", "published", "ordering") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_stage_dates" ON "#__joomleague_project_stage" ("start_date", "end_date") /** CAN FAIL **/;
