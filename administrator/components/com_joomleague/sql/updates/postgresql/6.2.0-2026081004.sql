ALTER TABLE "#__joomleague_project_round" ADD CONSTRAINT "uq_jl_project_round_scope" UNIQUE ("id", "stage_id", "project_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_project_match" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "project_id" BIGINT NOT NULL, "stage_id" BIGINT NOT NULL, "round_id" BIGINT NOT NULL,
  "code" VARCHAR(100) NULL, "match_number" VARCHAR(100) NULL, "contest_type" VARCHAR(100) NOT NULL DEFAULT 'head_to_head',
  "scheduled_start" TIMESTAMP WITHOUT TIME ZONE NULL, "timezone" VARCHAR(100) NULL, "duration_minutes" INTEGER NULL, "venue_id" BIGINT NULL, "attendance" BIGINT NULL,
  "status_code" VARCHAR(100) NOT NULL DEFAULT 'scheduled', "description" TEXT NULL, "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 0, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0, "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_match_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_project_match_code" UNIQUE ("round_id", "code"), CONSTRAINT "uq_jl_project_match_owner" UNIQUE ("id", "project_id"),
  CONSTRAINT "fk_jl_project_match_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_match_round" FOREIGN KEY ("round_id", "stage_id", "project_id") REFERENCES "#__joomleague_project_round" ("id", "stage_id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_match_venue" FOREIGN KEY ("venue_id") REFERENCES "#__joomleague_venue" ("id") ON DELETE SET NULL
);
CREATE INDEX "idx_jl_project_match_schedule" ON "#__joomleague_project_match" ("project_id", "scheduled_start") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_match_round" ON "#__joomleague_project_match" ("round_id", "status_code", "ordering") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_match_venue" ON "#__joomleague_project_match" ("venue_id") /** CAN FAIL **/;

CREATE TABLE IF NOT EXISTS "#__joomleague_match_participant" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL, "project_entry_id" BIGINT NOT NULL,
  "role_code" VARCHAR(100) NOT NULL DEFAULT 'participant', "slot_number" INTEGER NOT NULL, "result_status" VARCHAR(100) NOT NULL DEFAULT 'scheduled', "result_rank" INTEGER NULL,
  "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0, "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_participant_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_participant_entry" UNIQUE ("match_id", "project_entry_id"), CONSTRAINT "uq_jl_match_participant_slot" UNIQUE ("match_id", "slot_number"),
  CONSTRAINT "fk_jl_match_participant_match" FOREIGN KEY ("match_id", "project_id") REFERENCES "#__joomleague_project_match" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_participant_entry" FOREIGN KEY ("project_entry_id", "project_id") REFERENCES "#__joomleague_project_entry" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_match_participant_slot" CHECK ("slot_number" > 0), CONSTRAINT "chk_jl_match_participant_rank" CHECK ("result_rank" IS NULL OR "result_rank" > 0)
);
CREATE INDEX "idx_jl_match_participant_entry" ON "#__joomleague_match_participant" ("project_entry_id", "project_id") /** CAN FAIL **/;
