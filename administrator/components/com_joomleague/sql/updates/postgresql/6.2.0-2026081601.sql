ALTER TABLE "#__joomleague_person" ADD COLUMN "club_id" BIGINT NULL DEFAULT NULL /** CAN FAIL **/;
ALTER TABLE "#__joomleague_person" ADD CONSTRAINT "fk_jl_person_club" FOREIGN KEY ("club_id") REFERENCES "#__joomleague_club" ("id") ON DELETE SET NULL /** CAN FAIL **/;
CREATE INDEX IF NOT EXISTS "idx_jl_person_club" ON "#__joomleague_person" ("club_id");
