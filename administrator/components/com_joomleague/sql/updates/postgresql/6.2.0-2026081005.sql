ALTER TABLE "#__joomleague_match_participant" ADD CONSTRAINT "uq_jl_match_participant_scope" UNIQUE ("id", "match_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_match_result" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "result_type" VARCHAR(100) NOT NULL, "status_code" VARCHAR(100) NOT NULL DEFAULT 'draft', "outcome_code" VARCHAR(100) NULL, "finalized_at" TIMESTAMP WITHOUT TIME ZONE NULL, "notes" TEXT NULL, "metadata_json" TEXT NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0, "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_result_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_result_match" UNIQUE ("match_id"), CONSTRAINT "fk_jl_match_result_match" FOREIGN KEY ("match_id") REFERENCES "#__joomleague_project_match" ("id") ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS "#__joomleague_match_score_segment" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "parent_id" BIGINT NULL, "level_code" VARCHAR(100) NOT NULL, "sequence_number" INTEGER NOT NULL, "status_code" VARCHAR(100) NOT NULL DEFAULT 'completed', "metadata_json" TEXT NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0, "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_score_segment_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_score_segment_position" UNIQUE ("match_id", "parent_id", "level_code", "sequence_number"), CONSTRAINT "uq_jl_match_score_segment_scope" UNIQUE ("id", "match_id"),
  CONSTRAINT "fk_jl_match_score_segment_match" FOREIGN KEY ("match_id") REFERENCES "#__joomleague_project_match" ("id") ON DELETE CASCADE, CONSTRAINT "fk_jl_match_score_segment_parent" FOREIGN KEY ("parent_id", "match_id") REFERENCES "#__joomleague_match_score_segment" ("id", "match_id") ON DELETE CASCADE, CONSTRAINT "chk_jl_match_score_segment_sequence" CHECK ("sequence_number" > 0)
);
CREATE INDEX "idx_jl_match_score_segment_parent" ON "#__joomleague_match_score_segment" ("parent_id", "match_id") /** CAN FAIL **/;
CREATE TABLE IF NOT EXISTS "#__joomleague_match_score_value" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "segment_id" BIGINT NOT NULL, "participant_id" BIGINT NOT NULL, "numeric_value" NUMERIC(30,9) NULL, "text_value" VARCHAR(255) NULL, "status_code" VARCHAR(100) NULL, "result_rank" INTEGER NULL, "metadata_json" TEXT NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0, "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_score_value_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_score_value_participant" UNIQUE ("segment_id", "participant_id"), CONSTRAINT "fk_jl_match_score_value_segment" FOREIGN KEY ("segment_id", "match_id") REFERENCES "#__joomleague_match_score_segment" ("id", "match_id") ON DELETE CASCADE, CONSTRAINT "fk_jl_match_score_value_participant" FOREIGN KEY ("participant_id", "match_id") REFERENCES "#__joomleague_match_participant" ("id", "match_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_match_score_value_rank" CHECK ("result_rank" IS NULL OR "result_rank" > 0), CONSTRAINT "chk_jl_match_score_value_payload" CHECK ("numeric_value" IS NOT NULL OR "text_value" IS NOT NULL OR "status_code" IS NOT NULL OR "result_rank" IS NOT NULL)
);
CREATE INDEX "idx_jl_match_score_value_match" ON "#__joomleague_match_score_value" ("match_id", "result_rank") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_score_value_participant" ON "#__joomleague_match_score_value" ("participant_id", "match_id") /** CAN FAIL **/;
