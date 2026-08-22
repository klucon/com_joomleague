CREATE TABLE IF NOT EXISTS "#__joomleague_match_event" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "match_participant_id" BIGINT NULL, "source_event_type_id" BIGINT NULL, "event_code" VARCHAR(100) NOT NULL, "event_name_key" VARCHAR(255) NOT NULL,
  "event_person_type" VARCHAR(100) NULL, "sequence_number" INTEGER NOT NULL,
  "primary_lineup_member_id" BIGINT NULL, "primary_person_id" BIGINT NULL, "primary_name_snapshot" VARCHAR(255) NULL,
  "secondary_lineup_member_id" BIGINT NULL, "secondary_person_id" BIGINT NULL, "secondary_name_snapshot" VARCHAR(255) NULL,
  "source_match_actor_role_id" BIGINT NULL, "actor_name_snapshot" VARCHAR(255) NULL, "score_segment_id" BIGINT NULL,
  "phase_code" VARCHAR(100) NULL, "phase_sequence" INTEGER NULL, "clock_value" NUMERIC(30,9) NULL, "clock_unit" VARCHAR(50) NULL,
  "occurred_at" TIMESTAMP WITHOUT TIME ZONE NULL, "numeric_value" NUMERIC(30,9) NULL, "text_value" VARCHAR(255) NULL,
  "notes" TEXT NULL, "profile_metadata_json" TEXT NOT NULL, "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_event_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_event_sequence" UNIQUE ("match_id", "sequence_number"),
  CONSTRAINT "fk_jl_match_event_match" FOREIGN KEY ("match_id", "project_id") REFERENCES "#__joomleague_project_match" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_event_participant" FOREIGN KEY ("match_participant_id", "match_id") REFERENCES "#__joomleague_match_participant" ("id", "match_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_event_catalog" FOREIGN KEY ("source_event_type_id") REFERENCES "#__joomleague_event_type" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_match_event_primary_lineup" FOREIGN KEY ("primary_lineup_member_id") REFERENCES "#__joomleague_match_lineup_member" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_match_event_primary_person" FOREIGN KEY ("primary_person_id") REFERENCES "#__joomleague_person" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_match_event_secondary_lineup" FOREIGN KEY ("secondary_lineup_member_id") REFERENCES "#__joomleague_match_lineup_member" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_match_event_secondary_person" FOREIGN KEY ("secondary_person_id") REFERENCES "#__joomleague_person" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_match_event_actor" FOREIGN KEY ("source_match_actor_role_id") REFERENCES "#__joomleague_match_actor_role" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_match_event_segment" FOREIGN KEY ("score_segment_id") REFERENCES "#__joomleague_match_score_segment" ("id") ON DELETE SET NULL,
  CONSTRAINT "chk_jl_match_event_sequence" CHECK ("sequence_number" > 0), CONSTRAINT "chk_jl_match_event_phase" CHECK ("phase_sequence" IS NULL OR "phase_sequence" > 0),
  CONSTRAINT "chk_jl_match_event_clock" CHECK ("clock_value" IS NULL OR "clock_value" >= 0),
  CONSTRAINT "chk_jl_match_event_primary_snapshot" CHECK (("primary_person_id" IS NULL AND "primary_name_snapshot" IS NULL) OR ("primary_person_id" IS NOT NULL AND "primary_name_snapshot" IS NOT NULL)),
  CONSTRAINT "chk_jl_match_event_secondary_snapshot" CHECK (("secondary_person_id" IS NULL AND "secondary_name_snapshot" IS NULL) OR ("secondary_person_id" IS NOT NULL AND "secondary_name_snapshot" IS NOT NULL)),
  CONSTRAINT "chk_jl_match_event_people" CHECK ("secondary_person_id" IS NULL OR ("primary_person_id" IS NOT NULL AND "secondary_person_id" <> "primary_person_id")),
  CONSTRAINT "chk_jl_match_event_clock_unit" CHECK (("clock_value" IS NULL AND "clock_unit" IS NULL) OR ("clock_value" IS NOT NULL AND "clock_unit" IS NOT NULL))
);
CREATE INDEX "idx_jl_match_event_timeline" ON "#__joomleague_match_event" ("match_id", "phase_code", "phase_sequence", "clock_value", "sequence_number") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_event_participant" ON "#__joomleague_match_event" ("match_participant_id", "event_code") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_event_primary_person" ON "#__joomleague_match_event" ("primary_person_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_event_secondary_person" ON "#__joomleague_match_event" ("secondary_person_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_event_actor" ON "#__joomleague_match_event" ("source_match_actor_role_id") /** CAN FAIL **/;
