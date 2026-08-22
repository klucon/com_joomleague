CREATE TABLE IF NOT EXISTS "#__joomleague_profile_template_config" (
  "id" BIGSERIAL PRIMARY KEY,
  "profile_version_id" BIGINT NOT NULL,
  "template_code" VARCHAR(100) NOT NULL,
  "schema_version" VARCHAR(50) NOT NULL,
  "params_json" TEXT NOT NULL,
  "params_checksum" CHAR(64) NOT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL,
  CONSTRAINT "uq_jl_profile_template" UNIQUE ("profile_version_id", "template_code"),
  CONSTRAINT "fk_jl_profile_template_version"
    FOREIGN KEY ("profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id")
    ON DELETE CASCADE
);
CREATE INDEX "idx_jl_profile_template_state" ON "#__joomleague_profile_template_config" ("published");
