CREATE TABLE IF NOT EXISTS "#__joomleague_standing_freshness" (
  "project_id" BIGINT NOT NULL, "stage_key" BIGINT NOT NULL DEFAULT 0, "scope_code" VARCHAR(100) NOT NULL,
  "is_dirty" SMALLINT NOT NULL DEFAULT 1, "input_checksum" CHAR(64) NULL,
  "dirty_at" TIMESTAMP WITHOUT TIME ZONE NULL, "refreshed_at" TIMESTAMP WITHOUT TIME ZONE NULL, "refreshed_by" BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY ("project_id", "stage_key", "scope_code"),
  CONSTRAINT "fk_jl_standing_freshness_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_standing_freshness_dirty" CHECK ("is_dirty" IN (0, 1))
);
