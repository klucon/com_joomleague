ALTER TABLE "#__joomleague_match_lineup_member" ADD CONSTRAINT "uq_jl_match_lineup_scope" UNIQUE ("id", "match_id", "match_participant_id");
CREATE TABLE IF NOT EXISTS "#__joomleague_match_lineup_change" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL,
  "match_id" BIGINT NOT NULL, "match_participant_id" BIGINT NOT NULL,
  "outgoing_lineup_member_id" BIGINT NOT NULL, "incoming_lineup_member_id" BIGINT NOT NULL,
  "change_type" VARCHAR(50) NOT NULL DEFAULT 'substitution', "sequence_number" INTEGER NOT NULL,
  "phase_code" VARCHAR(100) NULL, "phase_sequence" INTEGER NULL, "clock_value" NUMERIC(30,9) NULL, "clock_unit" VARCHAR(50) NULL,
  "notes" TEXT NULL, "metadata_json" TEXT NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_lineup_change_uuid" UNIQUE ("uuid"),
  CONSTRAINT "uq_jl_match_lineup_change_sequence" UNIQUE ("match_participant_id", "sequence_number"),
  CONSTRAINT "fk_jl_lineup_change_outgoing" FOREIGN KEY ("outgoing_lineup_member_id", "match_id", "match_participant_id") REFERENCES "#__joomleague_match_lineup_member" ("id", "match_id", "match_participant_id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_lineup_change_incoming" FOREIGN KEY ("incoming_lineup_member_id", "match_id", "match_participant_id") REFERENCES "#__joomleague_match_lineup_member" ("id", "match_id", "match_participant_id") ON DELETE RESTRICT,
  CONSTRAINT "chk_jl_lineup_change_members" CHECK ("outgoing_lineup_member_id" <> "incoming_lineup_member_id"),
  CONSTRAINT "chk_jl_lineup_change_sequence" CHECK ("sequence_number" > 0),
  CONSTRAINT "chk_jl_lineup_change_phase" CHECK ("phase_sequence" IS NULL OR "phase_sequence" > 0),
  CONSTRAINT "chk_jl_lineup_change_clock" CHECK ("clock_value" IS NULL OR "clock_value" >= 0)
);
CREATE INDEX "idx_jl_match_lineup_change_match" ON "#__joomleague_match_lineup_change" ("match_id", "match_participant_id", "sequence_number") /** CAN FAIL **/;
