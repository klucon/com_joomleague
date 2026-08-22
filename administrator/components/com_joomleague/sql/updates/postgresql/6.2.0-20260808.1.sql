-- uq_jl_sport_type_profile_binding is ensured idempotently by installer preflight.
CREATE TABLE IF NOT EXISTS "#__joomleague_competition" (
  "id" BIGSERIAL PRIMARY KEY,
  "uuid" CHAR(36) NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "middle_name" VARCHAR(100) NOT NULL DEFAULT '',
  "short_name" VARCHAR(50) NOT NULL DEFAULT '',
  "alias" VARCHAR(255) NOT NULL DEFAULT '',
  "code" VARCHAR(100) NULL,
  "external_code" VARCHAR(191) NULL,
  "organisation" VARCHAR(255) NULL,
  "country_code" VARCHAR(8) NULL,
  "description" TEXT NULL,
  "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1,
  "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL,
  "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_competition_uuid" UNIQUE ("uuid")
);
CREATE INDEX "idx_jl_competition_code" ON "#__joomleague_competition" ("code") /** CAN FAIL **/;
CREATE INDEX "idx_jl_competition_state" ON "#__joomleague_competition" ("published", "ordering") /** CAN FAIL **/;
CREATE TABLE IF NOT EXISTS "#__joomleague_season" (
  "id" BIGSERIAL PRIMARY KEY,
  "uuid" CHAR(36) NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "alias" VARCHAR(255) NOT NULL DEFAULT '',
  "start_date" DATE NULL,
  "end_date" DATE NULL,
  "description" TEXT NULL,
  "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1,
  "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL,
  "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_season_uuid" UNIQUE ("uuid"),
  CONSTRAINT "chk_jl_season_dates" CHECK ("end_date" IS NULL OR "start_date" IS NULL OR "end_date" >= "start_date")
);
CREATE INDEX "idx_jl_season_dates" ON "#__joomleague_season" ("start_date", "end_date") /** CAN FAIL **/;
CREATE INDEX "idx_jl_season_state" ON "#__joomleague_season" ("published", "ordering") /** CAN FAIL **/;
CREATE TABLE IF NOT EXISTS "#__joomleague_project" (
  "id" BIGSERIAL PRIMARY KEY,
  "uuid" CHAR(36) NOT NULL,
  "competition_id" BIGINT NOT NULL,
  "season_id" BIGINT NOT NULL,
  "sport_type_id" BIGINT NOT NULL,
  "profile_version_id" BIGINT NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "alias" VARCHAR(255) NOT NULL DEFAULT '',
  "code" VARCHAR(100) NULL,
  "external_code" VARCHAR(191) NULL,
  "project_type" VARCHAR(100) NOT NULL,
  "timezone" VARCHAR(100) NULL,
  "start_date" DATE NULL,
  "end_date" DATE NULL,
  "default_start_time" VARCHAR(5) NULL,
  "current_round_mode" VARCHAR(30) NOT NULL DEFAULT 'manual',
  "auto_advance_seconds" INTEGER NULL,
  "lifecycle_state" VARCHAR(30) NOT NULL DEFAULT 'draft',
  "description" TEXT NULL,
  "picture" VARCHAR(255) NULL,
  "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 0,
  "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL,
  "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_project_competition" FOREIGN KEY ("competition_id") REFERENCES "#__joomleague_competition" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_project_season" FOREIGN KEY ("season_id") REFERENCES "#__joomleague_season" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_project_sport_binding" FOREIGN KEY ("sport_type_id", "profile_version_id") REFERENCES "#__joomleague_sport_type" ("id", "profile_version_id") ON DELETE RESTRICT,
  CONSTRAINT "chk_jl_project_dates" CHECK ("end_date" IS NULL OR "start_date" IS NULL OR "end_date" >= "start_date"),
  CONSTRAINT "chk_jl_project_auto_advance" CHECK ("auto_advance_seconds" IS NULL OR "auto_advance_seconds" >= 0)
);
CREATE INDEX "idx_jl_project_competition" ON "#__joomleague_project" ("competition_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_season" ON "#__joomleague_project" ("season_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_profile" ON "#__joomleague_project" ("profile_version_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_state" ON "#__joomleague_project" ("published", "lifecycle_state", "ordering") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_schedule" ON "#__joomleague_project" ("start_date", "end_date") /** CAN FAIL **/;
CREATE TABLE IF NOT EXISTS "#__joomleague_project_rule_config" (
  "id" BIGSERIAL PRIMARY KEY,
  "project_id" BIGINT NOT NULL,
  "schema_version" VARCHAR(50) NOT NULL,
  "overrides_json" TEXT NOT NULL,
  "overrides_checksum" CHAR(64) NOT NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_rule_config" UNIQUE ("project_id"),
  CONSTRAINT "fk_jl_project_rule_config_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS "#__joomleague_project_template_config" (
  "id" BIGSERIAL PRIMARY KEY,
  "project_id" BIGINT NOT NULL,
  "template_code" VARCHAR(100) NOT NULL,
  "schema_version" VARCHAR(50) NOT NULL,
  "params_json" TEXT NOT NULL,
  "params_checksum" CHAR(64) NOT NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_template_config" UNIQUE ("project_id", "template_code"),
  CONSTRAINT "fk_jl_project_template_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE
);
CREATE INDEX "idx_jl_project_template_code" ON "#__joomleague_project_template_config" ("template_code") /** CAN FAIL **/;