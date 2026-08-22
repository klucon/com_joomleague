CREATE TABLE IF NOT EXISTS "#__joomleague_match_lineup_member" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL,
  "match_id" BIGINT NOT NULL, "match_participant_id" BIGINT NOT NULL,
  "source_entry_member_id" BIGINT NULL, "person_id" BIGINT NOT NULL,
  "member_person_type" VARCHAR(50) NOT NULL, "role_code" VARCHAR(100) NULL,
  "shirt_number" VARCHAR(20) NULL, "lineup_status" VARCHAR(50) NOT NULL DEFAULT 'available',
  "is_captain" SMALLINT NOT NULL DEFAULT 0, "notes" TEXT NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_lineup_uuid" UNIQUE ("uuid"),
  CONSTRAINT "uq_jl_match_lineup_person" UNIQUE ("match_participant_id", "person_id"),
  CONSTRAINT "fk_jl_match_lineup_participant" FOREIGN KEY ("match_participant_id", "match_id") REFERENCES "#__joomleague_match_participant" ("id", "match_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_lineup_source" FOREIGN KEY ("source_entry_member_id") REFERENCES "#__joomleague_project_entry_member" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_match_lineup_person" FOREIGN KEY ("person_id") REFERENCES "#__joomleague_person" ("id") ON DELETE RESTRICT
);
CREATE INDEX "idx_jl_match_lineup_match" ON "#__joomleague_match_lineup_member" ("match_id", "match_participant_id", "member_person_type", "ordering") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_lineup_source" ON "#__joomleague_match_lineup_member" ("source_entry_member_id") /** CAN FAIL **/;
