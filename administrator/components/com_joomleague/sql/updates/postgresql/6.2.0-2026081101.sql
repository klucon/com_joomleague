ALTER TABLE "#__joomleague_match_participant"
  ADD COLUMN "participation_status" VARCHAR(100) NULL;

ALTER TABLE "#__joomleague_match_score_segment"
  ADD COLUMN "segment_type_ordinal" INTEGER NOT NULL DEFAULT 0;

CREATE UNIQUE INDEX "uq_jl_match_score_segment_root"
  ON "#__joomleague_match_score_segment" ("match_id")
  WHERE "parent_id" IS NULL;
