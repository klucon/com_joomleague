CREATE TABLE IF NOT EXISTS "#__joomleague_match_statistic_value" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "source_statistic_id" BIGINT NULL, "statistic_code" VARCHAR(100) NOT NULL, "statistic_name_key" VARCHAR(255) NOT NULL, "abbreviation_key" VARCHAR(255) NULL,
  "statistic_type" VARCHAR(100) NOT NULL, "scope_code" VARCHAR(100) NOT NULL, "value_type" VARCHAR(100) NOT NULL, "calculation_source" VARCHAR(100) NOT NULL, "target_kind" VARCHAR(20) NOT NULL,
  "match_participant_id" BIGINT NOT NULL, "lineup_member_id" BIGINT NULL, "person_id" BIGINT NULL,
  "target_key" VARCHAR(191) NOT NULL, "target_name_snapshot" VARCHAR(255) NOT NULL, "score_segment_id" BIGINT NULL, "segment_key" BIGINT NOT NULL DEFAULT 0,
  "segment_code_snapshot" VARCHAR(100) NULL, "segment_sequence_snapshot" INTEGER NULL, "numeric_value" NUMERIC(30,9) NULL, "text_value" VARCHAR(1000) NULL,
  "notes" TEXT NULL, "profile_metadata_json" TEXT NOT NULL, "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_stat_value_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_stat_value_target" UNIQUE ("match_id", "statistic_code", "target_key", "segment_key"),
  CONSTRAINT "fk_jl_match_stat_value_match" FOREIGN KEY ("match_id", "project_id") REFERENCES "#__joomleague_project_match" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_stat_value_participant" FOREIGN KEY ("match_participant_id", "match_id") REFERENCES "#__joomleague_match_participant" ("id", "match_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_stat_value_catalog" FOREIGN KEY ("source_statistic_id") REFERENCES "#__joomleague_statistic" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_match_stat_value_lineup" FOREIGN KEY ("lineup_member_id") REFERENCES "#__joomleague_match_lineup_member" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_match_stat_value_person" FOREIGN KEY ("person_id") REFERENCES "#__joomleague_person" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_match_stat_value_segment" FOREIGN KEY ("score_segment_id") REFERENCES "#__joomleague_match_score_segment" ("id") ON DELETE SET NULL,
  CONSTRAINT "chk_jl_match_stat_value_target" CHECK (("target_kind" = 'participant' AND "person_id" IS NULL) OR ("target_kind" = 'person' AND "person_id" IS NOT NULL)),
  CONSTRAINT "chk_jl_match_stat_value_payload" CHECK (("numeric_value" IS NOT NULL AND "text_value" IS NULL) OR ("numeric_value" IS NULL AND "text_value" IS NOT NULL)),
  CONSTRAINT "chk_jl_match_stat_value_segment" CHECK (("segment_key" = 0 AND "segment_code_snapshot" IS NULL AND "segment_sequence_snapshot" IS NULL) OR ("segment_key" > 0 AND "segment_code_snapshot" IS NOT NULL AND "segment_sequence_snapshot" IS NOT NULL)),
  CONSTRAINT "chk_jl_match_stat_value_segment_sequence" CHECK ("segment_sequence_snapshot" IS NULL OR "segment_sequence_snapshot" > 0)
);
CREATE INDEX "idx_jl_match_stat_value_match" ON "#__joomleague_match_statistic_value" ("match_id", "scope_code", "statistic_code") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_stat_value_participant" ON "#__joomleague_match_statistic_value" ("match_participant_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_stat_value_person" ON "#__joomleague_match_statistic_value" ("person_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_stat_value_catalog" ON "#__joomleague_match_statistic_value" ("source_statistic_id") /** CAN FAIL **/;
