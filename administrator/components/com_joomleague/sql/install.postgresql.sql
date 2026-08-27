-- JoomLeague 6.2 canonical PostgreSQL foundation schema.

CREATE TABLE IF NOT EXISTS "#__joomleague_sport_profile" (
  "id" BIGSERIAL PRIMARY KEY,
  "code" VARCHAR(100) NOT NULL,
  "name_key" VARCHAR(255) NOT NULL,
  "description_key" VARCHAR(255) NOT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  CONSTRAINT "uq_jl_sport_profile_code" UNIQUE ("code")
);
CREATE INDEX IF NOT EXISTS "idx_jl_sport_profile_published" ON "#__joomleague_sport_profile" ("published");

CREATE TABLE IF NOT EXISTS "#__joomleague_sport_profile_version" (
  "id" BIGSERIAL PRIMARY KEY,
  "profile_id" BIGINT NOT NULL,
  "schema_version" VARCHAR(50) NOT NULL,
  "profile_version" VARCHAR(50) NOT NULL,
  "payload_json" TEXT NOT NULL,
  "payload_checksum" CHAR(64) NOT NULL,
  "source" VARCHAR(50) NOT NULL DEFAULT 'bundled',
  "state" VARCHAR(30) NOT NULL DEFAULT 'active',
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "uq_jl_profile_version" UNIQUE ("profile_id", "profile_version"),
  CONSTRAINT "uq_jl_profile_payload" UNIQUE ("profile_id", "payload_checksum"),
  CONSTRAINT "fk_jl_profile_version_profile"
    FOREIGN KEY ("profile_id") REFERENCES "#__joomleague_sport_profile" ("id")
    ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS "idx_jl_profile_version_state" ON "#__joomleague_sport_profile_version" ("state");

CREATE TABLE IF NOT EXISTS "#__joomleague_sport_type" (
  "id" BIGSERIAL PRIMARY KEY,
  "profile_version_id" BIGINT NULL,
  "code" VARCHAR(100) NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "alias" VARCHAR(255) NOT NULL DEFAULT '',
  "overrides_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1,
  "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL,
  CONSTRAINT "uq_jl_sport_type_code" UNIQUE ("code"),
  CONSTRAINT "uq_jl_sport_type_profile_binding" UNIQUE ("id", "profile_version_id"),
  CONSTRAINT "fk_jl_sport_type_profile_version"
    FOREIGN KEY ("profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id")
    ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS "idx_jl_sport_type_profile" ON "#__joomleague_sport_type" ("profile_version_id");
CREATE INDEX IF NOT EXISTS "idx_jl_sport_type_state" ON "#__joomleague_sport_type" ("published", "ordering");

CREATE TABLE IF NOT EXISTS "#__joomleague_sport_position" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "sport_type_id" BIGINT NOT NULL, "source_profile_version_id" BIGINT NULL,
  "code" VARCHAR(100) NOT NULL, "name" VARCHAR(255) NOT NULL DEFAULT '', "name_key" VARCHAR(255) NULL,
  "person_type" VARCHAR(100) NOT NULL, "lineup_group" VARCHAR(100) NULL, "parent_id" BIGINT NULL,
  "has_events" SMALLINT NULL, "has_statistics" SMALLINT NULL, "source" VARCHAR(30) NOT NULL DEFAULT 'local', "source_checksum" CHAR(64) NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0, "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_sport_position_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_sport_position_code" UNIQUE ("sport_type_id", "code"), CONSTRAINT "uq_jl_sport_position_owner" UNIQUE ("id", "sport_type_id"),
  CONSTRAINT "fk_jl_sport_position_type" FOREIGN KEY ("sport_type_id") REFERENCES "#__joomleague_sport_type" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_sport_position_profile" FOREIGN KEY ("source_profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_sport_position_parent" FOREIGN KEY ("parent_id") REFERENCES "#__joomleague_sport_position" ("id") ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS "idx_jl_sport_position_parent" ON "#__joomleague_sport_position" ("parent_id");
CREATE INDEX IF NOT EXISTS "idx_jl_sport_position_state" ON "#__joomleague_sport_position" ("published", "ordering");

CREATE TABLE IF NOT EXISTS "#__joomleague_event_type" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "sport_type_id" BIGINT NOT NULL, "source_profile_version_id" BIGINT NULL,
  "code" VARCHAR(100) NOT NULL, "name" VARCHAR(255) NOT NULL DEFAULT '', "name_key" VARCHAR(255) NULL, "person_type" VARCHAR(100) NULL,
  "timeline" SMALLINT NOT NULL DEFAULT 0, "direction" SMALLINT NOT NULL DEFAULT 0, "affects_score" SMALLINT NOT NULL DEFAULT 0,
  "score_delta" NUMERIC(12,4) NULL, "score_target" VARCHAR(30) NULL, "requires_second_person" SMALLINT NOT NULL DEFAULT 0,
  "leads_to_suspension" SMALLINT NOT NULL DEFAULT 0, "system_event" SMALLINT NOT NULL DEFAULT 0,
  "source" VARCHAR(30) NOT NULL DEFAULT 'local', "source_checksum" CHAR(64) NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0, "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_event_type_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_event_type_code" UNIQUE ("sport_type_id", "code"), CONSTRAINT "uq_jl_event_type_owner" UNIQUE ("id", "sport_type_id"),
  CONSTRAINT "fk_jl_event_type_sport_type" FOREIGN KEY ("sport_type_id") REFERENCES "#__joomleague_sport_type" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_event_type_profile" FOREIGN KEY ("source_profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS "idx_jl_event_type_state" ON "#__joomleague_event_type" ("published", "ordering");

CREATE TABLE IF NOT EXISTS "#__joomleague_statistic" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "sport_type_id" BIGINT NOT NULL, "source_profile_version_id" BIGINT NULL,
  "code" VARCHAR(100) NOT NULL, "name" VARCHAR(255) NOT NULL DEFAULT '', "name_key" VARCHAR(255) NULL, "abbreviation_key" VARCHAR(255) NULL,
  "statistic_type" VARCHAR(100) NOT NULL DEFAULT 'basic', "scope" VARCHAR(100) NOT NULL, "value_type" VARCHAR(100) NOT NULL DEFAULT 'integer',
  "calculation_source" VARCHAR(100) NOT NULL DEFAULT 'manual', "source" VARCHAR(30) NOT NULL DEFAULT 'local', "source_checksum" CHAR(64) NULL,
  "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0, "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_statistic_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_statistic_code" UNIQUE ("sport_type_id", "code"), CONSTRAINT "uq_jl_statistic_owner" UNIQUE ("id", "sport_type_id"),
  CONSTRAINT "fk_jl_statistic_sport_type" FOREIGN KEY ("sport_type_id") REFERENCES "#__joomleague_sport_type" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_statistic_profile" FOREIGN KEY ("source_profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS "idx_jl_statistic_state" ON "#__joomleague_statistic" ("published", "ordering");

CREATE TABLE IF NOT EXISTS "#__joomleague_position_event_type" (
  "position_id" BIGINT NOT NULL, "event_type_id" BIGINT NOT NULL, "sport_type_id" BIGINT NOT NULL,
  "ordering" INTEGER NOT NULL DEFAULT 0, "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "pk_jl_position_event_type" PRIMARY KEY ("position_id", "event_type_id"),
  CONSTRAINT "fk_jl_position_event_position" FOREIGN KEY ("position_id", "sport_type_id") REFERENCES "#__joomleague_sport_position" ("id", "sport_type_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_position_event_event" FOREIGN KEY ("event_type_id", "sport_type_id") REFERENCES "#__joomleague_event_type" ("id", "sport_type_id") ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS "idx_jl_position_event_type_event" ON "#__joomleague_position_event_type" ("event_type_id", "sport_type_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_position_statistic" (
  "position_id" BIGINT NOT NULL, "statistic_id" BIGINT NOT NULL, "sport_type_id" BIGINT NOT NULL,
  "ordering" INTEGER NOT NULL DEFAULT 0, "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "pk_jl_position_statistic" PRIMARY KEY ("position_id", "statistic_id"),
  CONSTRAINT "fk_jl_position_stat_position" FOREIGN KEY ("position_id", "sport_type_id") REFERENCES "#__joomleague_sport_position" ("id", "sport_type_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_position_stat_stat" FOREIGN KEY ("statistic_id", "sport_type_id") REFERENCES "#__joomleague_statistic" ("id", "sport_type_id") ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS "idx_jl_position_statistic_stat" ON "#__joomleague_position_statistic" ("statistic_id", "sport_type_id");

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
CREATE INDEX IF NOT EXISTS "idx_jl_profile_template_state" ON "#__joomleague_profile_template_config" ("published");

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
  "access" INTEGER NOT NULL DEFAULT 1,
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
CREATE INDEX IF NOT EXISTS "idx_jl_competition_code" ON "#__joomleague_competition" ("code");
CREATE INDEX IF NOT EXISTS "idx_jl_competition_state" ON "#__joomleague_competition" ("published", "ordering");

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
  "access" INTEGER NOT NULL DEFAULT 1,
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
CREATE INDEX IF NOT EXISTS "idx_jl_season_dates" ON "#__joomleague_season" ("start_date", "end_date");
CREATE INDEX IF NOT EXISTS "idx_jl_season_state" ON "#__joomleague_season" ("published", "ordering");

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
  "current_round_mode" VARCHAR(30) NOT NULL DEFAULT 'start',
  "auto_advance_seconds" INTEGER NULL DEFAULT 7200,
  "lifecycle_state" VARCHAR(30) NOT NULL DEFAULT 'draft',
  "description" TEXT NULL,
  "picture" VARCHAR(255) NULL,
  "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 0,
  "access" INTEGER NOT NULL DEFAULT 1,
  "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL,
  "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_project_competition"
    FOREIGN KEY ("competition_id") REFERENCES "#__joomleague_competition" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_project_season"
    FOREIGN KEY ("season_id") REFERENCES "#__joomleague_season" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_project_sport_binding"
    FOREIGN KEY ("sport_type_id", "profile_version_id")
    REFERENCES "#__joomleague_sport_type" ("id", "profile_version_id") ON DELETE RESTRICT,
  CONSTRAINT "chk_jl_project_dates" CHECK ("end_date" IS NULL OR "start_date" IS NULL OR "end_date" >= "start_date"),
  CONSTRAINT "chk_jl_project_auto_advance" CHECK ("auto_advance_seconds" IS NULL OR "auto_advance_seconds" >= 0)
);
CREATE INDEX IF NOT EXISTS "idx_jl_project_competition" ON "#__joomleague_project" ("competition_id");
CREATE INDEX IF NOT EXISTS "idx_jl_project_season" ON "#__joomleague_project" ("season_id");
CREATE INDEX IF NOT EXISTS "idx_jl_project_profile" ON "#__joomleague_project" ("profile_version_id");
CREATE INDEX IF NOT EXISTS "idx_jl_project_state" ON "#__joomleague_project" ("published", "lifecycle_state", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_project_schedule" ON "#__joomleague_project" ("start_date", "end_date");

CREATE TABLE IF NOT EXISTS "#__joomleague_project_stage" (
  "id" BIGSERIAL PRIMARY KEY,
  "uuid" CHAR(36) NOT NULL,
  "project_id" BIGINT NOT NULL,
  "parent_id" BIGINT NULL,
  "name" VARCHAR(255) NOT NULL,
  "alias" VARCHAR(255) NOT NULL DEFAULT '',
  "code" VARCHAR(100) NOT NULL,
  "stage_type" VARCHAR(100) NOT NULL,
  "entry_selection_mode" VARCHAR(30) NOT NULL DEFAULT 'inherit_project',
  "sequence_number" INTEGER NULL,
  "start_date" DATE NULL,
  "end_date" DATE NULL,
  "description" TEXT NULL,
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
  CONSTRAINT "uq_jl_project_stage_uuid" UNIQUE ("uuid"),
  CONSTRAINT "uq_jl_project_stage_code" UNIQUE ("project_id", "code"),
  CONSTRAINT "uq_jl_project_stage_owner" UNIQUE ("id", "project_id"),
  CONSTRAINT "fk_jl_project_stage_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_stage_parent" FOREIGN KEY ("parent_id") REFERENCES "#__joomleague_project_stage" ("id") ON DELETE SET NULL,
  CONSTRAINT "chk_jl_project_stage_entry_mode" CHECK ("entry_selection_mode" IN ('inherit_project', 'explicit')),
  CONSTRAINT "chk_jl_project_stage_dates" CHECK ("end_date" IS NULL OR "start_date" IS NULL OR "end_date" >= "start_date")
);
CREATE INDEX IF NOT EXISTS "idx_jl_project_stage_parent" ON "#__joomleague_project_stage" ("parent_id");
CREATE INDEX IF NOT EXISTS "idx_jl_project_stage_state" ON "#__joomleague_project_stage" ("project_id", "published", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_project_stage_dates" ON "#__joomleague_project_stage" ("start_date", "end_date");

CREATE TABLE IF NOT EXISTS "#__joomleague_project_round" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "project_id" BIGINT NOT NULL, "stage_id" BIGINT NOT NULL,
  "name" VARCHAR(255) NOT NULL, "alias" VARCHAR(255) NOT NULL DEFAULT '', "code" VARCHAR(100) NOT NULL,
  "round_type" VARCHAR(100) NOT NULL DEFAULT 'standard', "sequence_number" INTEGER NOT NULL,
  "start_date" DATE NULL, "end_date" DATE NULL, "lifecycle_state" VARCHAR(30) NOT NULL DEFAULT 'draft',
  "description" TEXT NULL, "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 0, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_round_uuid" UNIQUE ("uuid"),
  CONSTRAINT "uq_jl_project_round_code" UNIQUE ("stage_id", "code"),
  CONSTRAINT "uq_jl_project_round_sequence" UNIQUE ("stage_id", "sequence_number"),
  CONSTRAINT "uq_jl_project_round_owner" UNIQUE ("id", "project_id"),
  CONSTRAINT "uq_jl_project_round_scope" UNIQUE ("id", "stage_id", "project_id"),
  CONSTRAINT "fk_jl_project_round_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_round_stage" FOREIGN KEY ("stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_project_round_sequence" CHECK ("sequence_number" > 0),
  CONSTRAINT "chk_jl_project_round_dates" CHECK ("end_date" IS NULL OR "start_date" IS NULL OR "end_date" >= "start_date")
);
CREATE INDEX IF NOT EXISTS "idx_jl_project_round_state" ON "#__joomleague_project_round" ("project_id", "stage_id", "published", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_project_round_dates" ON "#__joomleague_project_round" ("start_date", "end_date");

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
  CONSTRAINT "fk_jl_project_rule_config_project"
    FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE
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
  CONSTRAINT "fk_jl_project_template_project"
    FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS "idx_jl_project_template_code" ON "#__joomleague_project_template_config" ("template_code");

CREATE TABLE IF NOT EXISTS "#__joomleague_club" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL,
  "alias" VARCHAR(255) NOT NULL DEFAULT '', "short_name" VARCHAR(100) NOT NULL DEFAULT '',
  "country_code" VARCHAR(8) NULL, "website" VARCHAR(2048) NULL, "logo" VARCHAR(255) NULL, "founded_date" DATE NULL,
  "dissolved_date" DATE NULL, "description" TEXT NULL, "external_code" VARCHAR(191) NULL,
  "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "access" INTEGER NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_club_uuid" UNIQUE ("uuid"),
  CONSTRAINT "chk_jl_club_dates" CHECK ("dissolved_date" IS NULL OR "founded_date" IS NULL OR "dissolved_date" >= "founded_date")
);
CREATE INDEX IF NOT EXISTS "idx_jl_club_state" ON "#__joomleague_club" ("published", "ordering");

CREATE TABLE IF NOT EXISTS "#__joomleague_venue" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "owner_club_id" BIGINT NULL,
  "name" VARCHAR(255) NOT NULL, "alias" VARCHAR(255) NOT NULL DEFAULT '',
  "short_name" VARCHAR(100) NOT NULL DEFAULT '', "nickname" VARCHAR(100) NOT NULL DEFAULT '',
  "address" VARCHAR(255) NOT NULL DEFAULT '', "postal_code" VARCHAR(20) NOT NULL DEFAULT '',
  "city" VARCHAR(100) NOT NULL DEFAULT '', "region" VARCHAR(100) NOT NULL DEFAULT '',
  "country_code" VARCHAR(8) NULL, "latitude" NUMERIC(10,7) NULL, "longitude" NUMERIC(10,7) NULL,
  "timezone" VARCHAR(64) NULL, "capacity" BIGINT NULL, "website" VARCHAR(2048) NULL,
  "picture" VARCHAR(255) NULL, "description" TEXT NULL, "external_code" VARCHAR(191) NULL,
  "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "access" INTEGER NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_venue_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_venue_owner_club" FOREIGN KEY ("owner_club_id") REFERENCES "#__joomleague_club" ("id") ON DELETE SET NULL,
  CONSTRAINT "chk_jl_venue_latitude" CHECK ("latitude" IS NULL OR ("latitude" >= -90 AND "latitude" <= 90)),
  CONSTRAINT "chk_jl_venue_longitude" CHECK ("longitude" IS NULL OR ("longitude" >= -180 AND "longitude" <= 180)),
  CONSTRAINT "chk_jl_venue_capacity" CHECK ("capacity" IS NULL OR "capacity" >= 0)
);
CREATE INDEX IF NOT EXISTS "idx_jl_venue_owner_club" ON "#__joomleague_venue" ("owner_club_id");
CREATE INDEX IF NOT EXISTS "idx_jl_venue_state" ON "#__joomleague_venue" ("published", "ordering");

CREATE TABLE IF NOT EXISTS "#__joomleague_team" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "club_id" BIGINT NULL, "name" VARCHAR(255) NOT NULL,
  "middle_name" VARCHAR(100) NOT NULL DEFAULT '', "short_name" VARCHAR(50) NOT NULL DEFAULT '',
  "alias" VARCHAR(255) NOT NULL DEFAULT '', "website" VARCHAR(2048) NULL, "logo" VARCHAR(255) NULL, "picture" VARCHAR(255) NULL,
  "description" TEXT NULL, "external_code" VARCHAR(191) NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "access" INTEGER NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_team_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_team_club" FOREIGN KEY ("club_id") REFERENCES "#__joomleague_club" ("id") ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS "idx_jl_team_club" ON "#__joomleague_team" ("club_id");
CREATE INDEX IF NOT EXISTS "idx_jl_team_state" ON "#__joomleague_team" ("published", "ordering");

CREATE TABLE IF NOT EXISTS "#__joomleague_organization_name_history" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL,
  "club_id" BIGINT NULL, "team_id" BIGINT NULL,
  "name" VARCHAR(255) NOT NULL, "short_name" VARCHAR(100) NOT NULL DEFAULT '',
  "valid_from" DATE NULL, "valid_to" DATE NULL, "notes" TEXT NULL, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_org_name_history_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_org_name_history_club" FOREIGN KEY ("club_id") REFERENCES "#__joomleague_club" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_org_name_history_team" FOREIGN KEY ("team_id") REFERENCES "#__joomleague_team" ("id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_org_name_history_owner" CHECK (("club_id" IS NOT NULL AND "team_id" IS NULL) OR ("club_id" IS NULL AND "team_id" IS NOT NULL)),
  CONSTRAINT "chk_jl_org_name_history_dates" CHECK ("valid_to" IS NULL OR "valid_from" IS NULL OR "valid_to" >= "valid_from")
);
CREATE INDEX IF NOT EXISTS "idx_jl_org_name_history_club" ON "#__joomleague_organization_name_history" ("club_id", "valid_from", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_org_name_history_team" ON "#__joomleague_organization_name_history" ("team_id", "valid_from", "ordering");

CREATE TABLE IF NOT EXISTS "#__joomleague_organization_media_history" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL,
  "club_id" BIGINT NULL, "team_id" BIGINT NULL,
  "media_type" VARCHAR(100) NOT NULL DEFAULT 'logo', "media_path" VARCHAR(255) NOT NULL,
  "alt_text" VARCHAR(255) NOT NULL DEFAULT '', "valid_from" DATE NULL, "valid_to" DATE NULL,
  "notes" TEXT NULL, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_org_media_history_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_org_media_history_club" FOREIGN KEY ("club_id") REFERENCES "#__joomleague_club" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_org_media_history_team" FOREIGN KEY ("team_id") REFERENCES "#__joomleague_team" ("id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_org_media_history_owner" CHECK (("club_id" IS NOT NULL AND "team_id" IS NULL) OR ("club_id" IS NULL AND "team_id" IS NOT NULL)),
  CONSTRAINT "chk_jl_org_media_history_dates" CHECK ("valid_to" IS NULL OR "valid_from" IS NULL OR "valid_to" >= "valid_from")
);
CREATE INDEX IF NOT EXISTS "idx_jl_org_media_history_club" ON "#__joomleague_organization_media_history" ("club_id", "media_type", "valid_from", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_org_media_history_team" ON "#__joomleague_organization_media_history" ("team_id", "media_type", "valid_from", "ordering");

CREATE TABLE IF NOT EXISTS "#__joomleague_person" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "contact_id" BIGINT NULL, "club_id" BIGINT NULL,
  "first_name" VARCHAR(100) NOT NULL DEFAULT '', "last_name" VARCHAR(100) NOT NULL DEFAULT '',
  "nickname" VARCHAR(100) NOT NULL DEFAULT '', "alias" VARCHAR(255) NOT NULL DEFAULT '',
  "country_code" VARCHAR(8) NULL, "birth_date" DATE NULL, "death_date" DATE NULL,
  "picture" VARCHAR(255) NULL, "description" TEXT NULL, "external_code" VARCHAR(191) NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "access" INTEGER NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_person_uuid" UNIQUE ("uuid"),
  CONSTRAINT "chk_jl_person_dates" CHECK ("death_date" IS NULL OR "birth_date" IS NULL OR "death_date" >= "birth_date"),
  CONSTRAINT "fk_jl_person_club" FOREIGN KEY ("club_id") REFERENCES "#__joomleague_club" ("id") ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS "idx_jl_person_name" ON "#__joomleague_person" ("last_name", "first_name");
CREATE INDEX IF NOT EXISTS "idx_jl_person_state" ON "#__joomleague_person" ("published", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_person_club" ON "#__joomleague_person" ("club_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_project_entry" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "project_id" BIGINT NOT NULL,
  "entry_kind" VARCHAR(20) NOT NULL, "team_id" BIGINT NULL, "person_id" BIGINT NULL,
  "display_name" VARCHAR(255) NOT NULL DEFAULT '', "entry_code" VARCHAR(100) NULL,
  "seed_number" INTEGER NULL, "bib_number" VARCHAR(50) NULL, "included_in_standings" SMALLINT NOT NULL DEFAULT 1,
  "lifecycle_state" VARCHAR(30) NOT NULL DEFAULT 'active', "notes" TEXT NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_entry_uuid" UNIQUE ("uuid"),
  CONSTRAINT "uq_jl_project_entry_team" UNIQUE ("project_id", "team_id"),
  CONSTRAINT "uq_jl_project_entry_person" UNIQUE ("project_id", "person_id"),
  CONSTRAINT "uq_jl_project_entry_owner" UNIQUE ("id", "project_id"),
  CONSTRAINT "fk_jl_project_entry_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_entry_team" FOREIGN KEY ("team_id") REFERENCES "#__joomleague_team" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_project_entry_person" FOREIGN KEY ("person_id") REFERENCES "#__joomleague_person" ("id") ON DELETE RESTRICT,
  CONSTRAINT "chk_jl_project_entry_kind" CHECK ("entry_kind" IN ('team', 'person', 'group')),
  CONSTRAINT "chk_jl_project_entry_target" CHECK (("entry_kind" = 'team' AND "team_id" IS NOT NULL AND "person_id" IS NULL) OR ("entry_kind" = 'person' AND "person_id" IS NOT NULL AND "team_id" IS NULL) OR ("entry_kind" = 'group' AND "team_id" IS NULL AND "person_id" IS NULL AND "display_name" <> ''))
);
CREATE INDEX IF NOT EXISTS "idx_jl_project_entry_state" ON "#__joomleague_project_entry" ("project_id", "published", "ordering");

CREATE TABLE IF NOT EXISTS "#__joomleague_stage_entry" (
  "stage_id" BIGINT NOT NULL, "entry_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "ordering" INTEGER NOT NULL DEFAULT 0, "seed_number" INTEGER NULL, "metadata_json" TEXT NULL, "manual_assignment" SMALLINT NOT NULL DEFAULT 1,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "pk_jl_stage_entry" PRIMARY KEY ("stage_id", "entry_id"),
  CONSTRAINT "fk_jl_stage_entry_stage" FOREIGN KEY ("stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_entry_entry" FOREIGN KEY ("entry_id", "project_id") REFERENCES "#__joomleague_project_entry" ("id", "project_id") ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS "idx_jl_stage_entry_entry" ON "#__joomleague_stage_entry" ("entry_id", "project_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_project_match" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "project_id" BIGINT NOT NULL, "stage_id" BIGINT NOT NULL, "round_id" BIGINT NOT NULL,
  "code" VARCHAR(100) NULL, "match_number" VARCHAR(100) NULL, "contest_type" VARCHAR(100) NOT NULL DEFAULT 'head_to_head',
  "scheduled_start" TIMESTAMP WITHOUT TIME ZONE NULL, "timezone" VARCHAR(100) NULL, "duration_minutes" INTEGER NULL,
  "venue_id" BIGINT NULL, "attendance" BIGINT NULL, "status_code" VARCHAR(100) NOT NULL DEFAULT 'scheduled',
  "description" TEXT NULL, "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 0, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0, "checked_out" BIGINT NULL,
  "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_match_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_project_match_code" UNIQUE ("round_id", "code"),
  CONSTRAINT "uq_jl_project_match_owner" UNIQUE ("id", "project_id"),
  CONSTRAINT "fk_jl_project_match_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_match_round" FOREIGN KEY ("round_id", "stage_id", "project_id") REFERENCES "#__joomleague_project_round" ("id", "stage_id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_match_venue" FOREIGN KEY ("venue_id") REFERENCES "#__joomleague_venue" ("id") ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS "idx_jl_project_match_schedule" ON "#__joomleague_project_match" ("project_id", "scheduled_start");
CREATE INDEX IF NOT EXISTS "idx_jl_project_match_round" ON "#__joomleague_project_match" ("round_id", "status_code", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_project_match_venue" ON "#__joomleague_project_match" ("venue_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_match_participant" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "project_entry_id" BIGINT NOT NULL, "role_code" VARCHAR(100) NOT NULL DEFAULT 'participant', "slot_number" INTEGER NOT NULL,
  "participation_status" VARCHAR(100) NULL, "result_status" VARCHAR(100) NOT NULL DEFAULT 'scheduled', "result_rank" INTEGER NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_participant_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_participant_entry" UNIQUE ("match_id", "project_entry_id"),
  CONSTRAINT "uq_jl_match_participant_slot" UNIQUE ("match_id", "slot_number"),
  CONSTRAINT "uq_jl_match_participant_scope" UNIQUE ("id", "match_id"),
  CONSTRAINT "fk_jl_match_participant_match" FOREIGN KEY ("match_id", "project_id") REFERENCES "#__joomleague_project_match" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_participant_entry" FOREIGN KEY ("project_entry_id", "project_id") REFERENCES "#__joomleague_project_entry" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_match_participant_slot" CHECK ("slot_number" > 0),
  CONSTRAINT "chk_jl_match_participant_rank" CHECK ("result_rank" IS NULL OR "result_rank" > 0)
);
CREATE INDEX IF NOT EXISTS "idx_jl_match_participant_entry" ON "#__joomleague_match_participant" ("project_entry_id", "project_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_match_result" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "result_type" VARCHAR(100) NOT NULL,
  "status_code" VARCHAR(100) NOT NULL DEFAULT 'draft', "outcome_code" VARCHAR(100) NULL, "finalized_at" TIMESTAMP WITHOUT TIME ZONE NULL,
  "notes" TEXT NULL, "metadata_json" TEXT NULL, "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0, "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_result_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_result_match" UNIQUE ("match_id"),
  CONSTRAINT "fk_jl_match_result_match" FOREIGN KEY ("match_id") REFERENCES "#__joomleague_project_match" ("id") ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS "#__joomleague_match_score_segment" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "parent_id" BIGINT NULL,
  "level_code" VARCHAR(100) NOT NULL, "segment_type_ordinal" INTEGER NOT NULL DEFAULT 0, "sequence_number" INTEGER NOT NULL, "status_code" VARCHAR(100) NOT NULL DEFAULT 'completed',
  "metadata_json" TEXT NULL, "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_score_segment_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_score_segment_position" UNIQUE ("match_id", "parent_id", "level_code", "sequence_number"),
  CONSTRAINT "uq_jl_match_score_segment_scope" UNIQUE ("id", "match_id"),
  CONSTRAINT "fk_jl_match_score_segment_match" FOREIGN KEY ("match_id") REFERENCES "#__joomleague_project_match" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_score_segment_parent" FOREIGN KEY ("parent_id", "match_id") REFERENCES "#__joomleague_match_score_segment" ("id", "match_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_match_score_segment_sequence" CHECK ("sequence_number" > 0)
);
CREATE INDEX IF NOT EXISTS "idx_jl_match_score_segment_parent" ON "#__joomleague_match_score_segment" ("parent_id", "match_id");
CREATE UNIQUE INDEX IF NOT EXISTS "uq_jl_match_score_segment_root" ON "#__joomleague_match_score_segment" ("match_id") WHERE "parent_id" IS NULL;

CREATE TABLE IF NOT EXISTS "#__joomleague_match_score_value" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "segment_id" BIGINT NOT NULL, "participant_id" BIGINT NOT NULL,
  "numeric_value" NUMERIC(30,9) NULL, "text_value" VARCHAR(255) NULL, "status_code" VARCHAR(100) NULL, "result_rank" INTEGER NULL,
  "metadata_json" TEXT NULL, "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_score_value_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_score_value_participant" UNIQUE ("segment_id", "participant_id"),
  CONSTRAINT "fk_jl_match_score_value_segment" FOREIGN KEY ("segment_id", "match_id") REFERENCES "#__joomleague_match_score_segment" ("id", "match_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_score_value_participant" FOREIGN KEY ("participant_id", "match_id") REFERENCES "#__joomleague_match_participant" ("id", "match_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_match_score_value_rank" CHECK ("result_rank" IS NULL OR "result_rank" > 0),
  CONSTRAINT "chk_jl_match_score_value_payload" CHECK ("numeric_value" IS NOT NULL OR "text_value" IS NOT NULL OR "status_code" IS NOT NULL OR "result_rank" IS NOT NULL)
);
CREATE INDEX IF NOT EXISTS "idx_jl_match_score_value_match" ON "#__joomleague_match_score_value" ("match_id", "result_rank");
CREATE INDEX IF NOT EXISTS "idx_jl_match_score_value_participant" ON "#__joomleague_match_score_value" ("participant_id", "match_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_project_entry_member" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "entry_id" BIGINT NOT NULL, "person_id" BIGINT NOT NULL,
  "member_person_type" VARCHAR(50) NOT NULL, "role_code" VARCHAR(100) NULL, "shirt_number" VARCHAR(20) NULL,
  "is_captain" SMALLINT NOT NULL DEFAULT 0, "valid_from" DATE NULL, "valid_until" DATE NULL,
  "lifecycle_state" VARCHAR(30) NOT NULL DEFAULT 'active', "notes" TEXT NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_entry_member_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_entry_member_entry" FOREIGN KEY ("entry_id") REFERENCES "#__joomleague_project_entry" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_entry_member_person" FOREIGN KEY ("person_id") REFERENCES "#__joomleague_person" ("id") ON DELETE RESTRICT,
  CONSTRAINT "chk_jl_entry_member_dates" CHECK ("valid_until" IS NULL OR "valid_from" IS NULL OR "valid_until" >= "valid_from")
);
CREATE INDEX IF NOT EXISTS "idx_jl_entry_member_entry" ON "#__joomleague_project_entry_member" ("entry_id", "published", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_entry_member_person" ON "#__joomleague_project_entry_member" ("person_id", "valid_from", "valid_until");

CREATE TABLE IF NOT EXISTS "#__joomleague_match_lineup_member" (
  "id" BIGSERIAL PRIMARY KEY,
  "uuid" CHAR(36) NOT NULL,
  "match_id" BIGINT NOT NULL,
  "match_participant_id" BIGINT NOT NULL,
  "source_entry_member_id" BIGINT NULL,
  "person_id" BIGINT NOT NULL,
  "member_person_type" VARCHAR(50) NOT NULL,
  "role_code" VARCHAR(100) NULL,
  "shirt_number" VARCHAR(20) NULL,
  "lineup_status" VARCHAR(50) NOT NULL DEFAULT 'available',
  "is_captain" SMALLINT NOT NULL DEFAULT 0,
  "notes" TEXT NULL,
  "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1,
  "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_lineup_uuid" UNIQUE ("uuid"),
  CONSTRAINT "uq_jl_match_lineup_person" UNIQUE ("match_participant_id", "person_id"),
  CONSTRAINT "uq_jl_match_lineup_scope" UNIQUE ("id", "match_id", "match_participant_id"),
  CONSTRAINT "fk_jl_match_lineup_participant" FOREIGN KEY ("match_participant_id", "match_id") REFERENCES "#__joomleague_match_participant" ("id", "match_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_lineup_source" FOREIGN KEY ("source_entry_member_id") REFERENCES "#__joomleague_project_entry_member" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_match_lineup_person" FOREIGN KEY ("person_id") REFERENCES "#__joomleague_person" ("id") ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS "idx_jl_match_lineup_match" ON "#__joomleague_match_lineup_member" ("match_id", "match_participant_id", "member_person_type", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_match_lineup_source" ON "#__joomleague_match_lineup_member" ("source_entry_member_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_match_lineup_change" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL,
  "match_id" BIGINT NOT NULL, "match_participant_id" BIGINT NOT NULL,
  "outgoing_lineup_member_id" BIGINT NOT NULL, "incoming_lineup_member_id" BIGINT NOT NULL,
  "change_type" VARCHAR(50) NOT NULL DEFAULT 'substitution', "sequence_number" INTEGER NOT NULL,
  "phase_code" VARCHAR(100) NULL, "phase_sequence" INTEGER NULL,
  "clock_value" NUMERIC(30,9) NULL, "clock_unit" VARCHAR(50) NULL,
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
CREATE INDEX IF NOT EXISTS "idx_jl_match_lineup_change_match" ON "#__joomleague_match_lineup_change" ("match_id", "match_participant_id", "sequence_number");

CREATE TABLE IF NOT EXISTS "#__joomleague_project_actor_role" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "project_id" BIGINT NOT NULL,
  "actor_kind" VARCHAR(20) NOT NULL, "person_id" BIGINT NULL, "team_id" BIGINT NULL,
  "role_code" VARCHAR(100) NOT NULL, "person_type" VARCHAR(100) NOT NULL,
  "valid_from" DATE NULL, "valid_until" DATE NULL, "lifecycle_state" VARCHAR(30) NOT NULL DEFAULT 'active',
  "notes" TEXT NULL, "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_project_actor_role_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_project_actor_role_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_actor_role_person" FOREIGN KEY ("person_id") REFERENCES "#__joomleague_person" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_project_actor_role_team" FOREIGN KEY ("team_id") REFERENCES "#__joomleague_team" ("id") ON DELETE RESTRICT,
  CONSTRAINT "chk_jl_project_actor_role_actor" CHECK (("actor_kind" = 'person' AND "person_id" IS NOT NULL AND "team_id" IS NULL) OR ("actor_kind" = 'team' AND "team_id" IS NOT NULL AND "person_id" IS NULL)),
  CONSTRAINT "chk_jl_project_actor_role_dates" CHECK ("valid_until" IS NULL OR "valid_from" IS NULL OR "valid_until" >= "valid_from")
);
CREATE INDEX IF NOT EXISTS "idx_jl_project_actor_role_project" ON "#__joomleague_project_actor_role" ("project_id", "person_type", "published", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_project_actor_role_person" ON "#__joomleague_project_actor_role" ("person_id", "valid_from", "valid_until");
CREATE INDEX IF NOT EXISTS "idx_jl_project_actor_role_team" ON "#__joomleague_project_actor_role" ("team_id", "valid_from", "valid_until");

CREATE TABLE IF NOT EXISTS "#__joomleague_match_actor_role" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "source_project_actor_role_id" BIGINT NULL, "actor_kind" VARCHAR(20) NOT NULL, "person_id" BIGINT NULL, "team_id" BIGINT NULL,
  "role_code" VARCHAR(100) NOT NULL, "person_type" VARCHAR(100) NOT NULL, "display_name_snapshot" VARCHAR(255) NOT NULL,
  "notes" TEXT NULL, "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_actor_role_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_actor_role_source" UNIQUE ("match_id", "source_project_actor_role_id"),
  CONSTRAINT "fk_jl_match_actor_role_match" FOREIGN KEY ("match_id", "project_id") REFERENCES "#__joomleague_project_match" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_actor_role_source" FOREIGN KEY ("source_project_actor_role_id") REFERENCES "#__joomleague_project_actor_role" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_match_actor_role_person" FOREIGN KEY ("person_id") REFERENCES "#__joomleague_person" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_match_actor_role_team" FOREIGN KEY ("team_id") REFERENCES "#__joomleague_team" ("id") ON DELETE RESTRICT,
  CONSTRAINT "chk_jl_match_actor_role_actor" CHECK (("actor_kind" = 'person' AND "person_id" IS NOT NULL AND "team_id" IS NULL) OR ("actor_kind" = 'team' AND "team_id" IS NOT NULL AND "person_id" IS NULL))
);
CREATE INDEX IF NOT EXISTS "idx_jl_match_actor_role_match" ON "#__joomleague_match_actor_role" ("match_id", "person_type", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_match_actor_role_project" ON "#__joomleague_match_actor_role" ("project_id");
CREATE INDEX IF NOT EXISTS "idx_jl_match_actor_role_person" ON "#__joomleague_match_actor_role" ("person_id");
CREATE INDEX IF NOT EXISTS "idx_jl_match_actor_role_team" ON "#__joomleague_match_actor_role" ("team_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_match_event" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "match_participant_id" BIGINT NULL, "source_event_type_id" BIGINT NULL,
  "event_code" VARCHAR(100) NOT NULL, "event_name_key" VARCHAR(255) NOT NULL, "event_person_type" VARCHAR(100) NULL,
  "sequence_number" INTEGER NOT NULL,
  "primary_lineup_member_id" BIGINT NULL, "primary_person_id" BIGINT NULL, "primary_name_snapshot" VARCHAR(255) NULL,
  "secondary_lineup_member_id" BIGINT NULL, "secondary_person_id" BIGINT NULL, "secondary_name_snapshot" VARCHAR(255) NULL,
  "source_match_actor_role_id" BIGINT NULL, "actor_name_snapshot" VARCHAR(255) NULL, "score_segment_id" BIGINT NULL,
  "phase_code" VARCHAR(100) NULL, "phase_sequence" INTEGER NULL, "clock_value" NUMERIC(30,9) NULL, "clock_unit" VARCHAR(50) NULL,
  "occurred_at" TIMESTAMP WITHOUT TIME ZONE NULL, "numeric_value" NUMERIC(30,9) NULL, "text_value" VARCHAR(255) NULL,
  "notes" TEXT NULL, "profile_metadata_json" TEXT NOT NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
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
  CONSTRAINT "chk_jl_match_event_sequence" CHECK ("sequence_number" > 0),
  CONSTRAINT "chk_jl_match_event_phase" CHECK ("phase_sequence" IS NULL OR "phase_sequence" > 0),
  CONSTRAINT "chk_jl_match_event_clock" CHECK ("clock_value" IS NULL OR "clock_value" >= 0),
  CONSTRAINT "chk_jl_match_event_primary_snapshot" CHECK (("primary_person_id" IS NULL AND "primary_name_snapshot" IS NULL) OR ("primary_person_id" IS NOT NULL AND "primary_name_snapshot" IS NOT NULL)),
  CONSTRAINT "chk_jl_match_event_secondary_snapshot" CHECK (("secondary_person_id" IS NULL AND "secondary_name_snapshot" IS NULL) OR ("secondary_person_id" IS NOT NULL AND "secondary_name_snapshot" IS NOT NULL)),
  CONSTRAINT "chk_jl_match_event_people" CHECK ("secondary_person_id" IS NULL OR ("primary_person_id" IS NOT NULL AND "secondary_person_id" <> "primary_person_id")),
  CONSTRAINT "chk_jl_match_event_clock_unit" CHECK (("clock_value" IS NULL AND "clock_unit" IS NULL) OR ("clock_value" IS NOT NULL AND "clock_unit" IS NOT NULL))
);
CREATE INDEX IF NOT EXISTS "idx_jl_match_event_timeline" ON "#__joomleague_match_event" ("match_id", "phase_code", "phase_sequence", "clock_value", "sequence_number");
CREATE INDEX IF NOT EXISTS "idx_jl_match_event_participant" ON "#__joomleague_match_event" ("match_participant_id", "event_code");
CREATE INDEX IF NOT EXISTS "idx_jl_match_event_primary_person" ON "#__joomleague_match_event" ("primary_person_id");
CREATE INDEX IF NOT EXISTS "idx_jl_match_event_secondary_person" ON "#__joomleague_match_event" ("secondary_person_id");
CREATE INDEX IF NOT EXISTS "idx_jl_match_event_actor" ON "#__joomleague_match_event" ("source_match_actor_role_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_match_statistic_value" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "source_statistic_id" BIGINT NULL, "statistic_code" VARCHAR(100) NOT NULL, "statistic_name_key" VARCHAR(255) NOT NULL,
  "abbreviation_key" VARCHAR(255) NULL, "statistic_type" VARCHAR(100) NOT NULL, "scope_code" VARCHAR(100) NOT NULL,
  "value_type" VARCHAR(100) NOT NULL, "calculation_source" VARCHAR(100) NOT NULL, "target_kind" VARCHAR(20) NOT NULL,
  "match_participant_id" BIGINT NOT NULL, "lineup_member_id" BIGINT NULL, "person_id" BIGINT NULL,
  "target_key" VARCHAR(191) NOT NULL, "target_name_snapshot" VARCHAR(255) NOT NULL,
  "score_segment_id" BIGINT NULL, "segment_key" BIGINT NOT NULL DEFAULT 0, "segment_code_snapshot" VARCHAR(100) NULL,
  "segment_sequence_snapshot" INTEGER NULL, "numeric_value" NUMERIC(30,9) NULL, "text_value" VARCHAR(1000) NULL,
  "notes" TEXT NULL, "profile_metadata_json" TEXT NOT NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_stat_value_uuid" UNIQUE ("uuid"),
  CONSTRAINT "uq_jl_match_stat_value_target" UNIQUE ("match_id", "statistic_code", "target_key", "segment_key"),
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
CREATE INDEX IF NOT EXISTS "idx_jl_match_stat_value_match" ON "#__joomleague_match_statistic_value" ("match_id", "scope_code", "statistic_code");
CREATE INDEX IF NOT EXISTS "idx_jl_match_stat_value_participant" ON "#__joomleague_match_statistic_value" ("match_participant_id");
CREATE INDEX IF NOT EXISTS "idx_jl_match_stat_value_person" ON "#__joomleague_match_statistic_value" ("person_id");
CREATE INDEX IF NOT EXISTS "idx_jl_match_stat_value_catalog" ON "#__joomleague_match_statistic_value" ("source_statistic_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_standing_adjustment" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL,
  "project_id" BIGINT NOT NULL, "stage_id" BIGINT NULL, "stage_key" BIGINT NOT NULL DEFAULT 0,
  "project_entry_id" BIGINT NOT NULL, "scope_code" VARCHAR(100) NOT NULL DEFAULT 'all', "metric_code" VARCHAR(100) NOT NULL,
  "adjustment_value" NUMERIC(30,9) NOT NULL, "reason" VARCHAR(500) NOT NULL, "effective_date" DATE NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_standing_adjustment_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_standing_adjustment_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_standing_adjustment_stage" FOREIGN KEY ("stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_standing_adjustment_entry" FOREIGN KEY ("project_entry_id", "project_id") REFERENCES "#__joomleague_project_entry" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_standing_adjustment_stage" CHECK (("stage_id" IS NULL AND "stage_key" = 0) OR ("stage_id" IS NOT NULL AND "stage_key" = "stage_id")),
  CONSTRAINT "chk_jl_standing_adjustment_scope" CHECK ("scope_code" ~ '^[a-z][a-z0-9_]*$'),
  CONSTRAINT "chk_jl_standing_adjustment_metric" CHECK ("metric_code" ~ '^[a-z][a-z0-9_]*$'),
  CONSTRAINT "chk_jl_standing_adjustment_value" CHECK ("adjustment_value" <> 0), CONSTRAINT "chk_jl_standing_adjustment_published" CHECK ("published" IN (0,1))
);
CREATE INDEX IF NOT EXISTS "idx_jl_standing_adjustment_context" ON "#__joomleague_standing_adjustment" ("project_id", "stage_key", "scope_code", "published", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_standing_adjustment_entry" ON "#__joomleague_standing_adjustment" ("project_entry_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_standing_snapshot" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "project_id" BIGINT NOT NULL, "stage_id" BIGINT NULL, "stage_key" BIGINT NOT NULL DEFAULT 0,
  "scope_code" VARCHAR(100) NOT NULL, "standings_type" VARCHAR(100) NOT NULL, "profile_version_id" BIGINT NOT NULL,
  "profile_checksum" CHAR(64) NOT NULL, "input_checksum" CHAR(64) NOT NULL, "contract_json" TEXT NOT NULL,
  "row_count" INTEGER NOT NULL DEFAULT 0, "generated_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "generated_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_standing_snapshot_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_standing_snapshot_input" UNIQUE ("project_id", "stage_key", "scope_code", "input_checksum"),
  CONSTRAINT "uq_jl_standing_snapshot_owner" UNIQUE ("id", "project_id"),
  CONSTRAINT "fk_jl_standing_snapshot_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_standing_snapshot_stage" FOREIGN KEY ("stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_standing_snapshot_profile" FOREIGN KEY ("profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE RESTRICT,
  CONSTRAINT "chk_jl_standing_snapshot_stage" CHECK (("stage_id" IS NULL AND "stage_key" = 0) OR ("stage_id" IS NOT NULL AND "stage_key" = "stage_id")),
  CONSTRAINT "chk_jl_standing_snapshot_rows" CHECK ("row_count" >= 0)
);
CREATE INDEX IF NOT EXISTS "idx_jl_standing_snapshot_stage" ON "#__joomleague_standing_snapshot" ("stage_id", "generated_at");

CREATE TABLE IF NOT EXISTS "#__joomleague_standing_snapshot_row" (
  "id" BIGSERIAL PRIMARY KEY, "snapshot_id" BIGINT NOT NULL, "project_entry_id" BIGINT NULL, "entry_id_snapshot" BIGINT NOT NULL,
  "entry_name_snapshot" VARCHAR(255) NOT NULL, "rank_number" INTEGER NOT NULL, "sequence_number" INTEGER NOT NULL, "metrics_json" TEXT NOT NULL,
  CONSTRAINT "uq_jl_standing_row_entry" UNIQUE ("snapshot_id", "entry_id_snapshot"),
  CONSTRAINT "fk_jl_standing_row_snapshot" FOREIGN KEY ("snapshot_id") REFERENCES "#__joomleague_standing_snapshot" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_standing_row_entry" FOREIGN KEY ("project_entry_id") REFERENCES "#__joomleague_project_entry" ("id") ON DELETE SET NULL,
  CONSTRAINT "chk_jl_standing_row_rank" CHECK ("rank_number" > 0 AND "sequence_number" > 0)
);
CREATE INDEX IF NOT EXISTS "idx_jl_standing_row_order" ON "#__joomleague_standing_snapshot_row" ("snapshot_id", "sequence_number");
CREATE INDEX IF NOT EXISTS "idx_jl_standing_row_entry" ON "#__joomleague_standing_snapshot_row" ("project_entry_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_standing_current" (
  "project_id" BIGINT NOT NULL, "stage_key" BIGINT NOT NULL DEFAULT 0, "scope_code" VARCHAR(100) NOT NULL, "snapshot_id" BIGINT NOT NULL,
  "updated_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "updated_by" BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY ("project_id", "stage_key", "scope_code"), CONSTRAINT "uq_jl_standing_current_snapshot" UNIQUE ("snapshot_id"),
  CONSTRAINT "fk_jl_standing_current_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_standing_current_snapshot" FOREIGN KEY ("snapshot_id", "project_id") REFERENCES "#__joomleague_standing_snapshot" ("id", "project_id") ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS "#__joomleague_stage_transition" (
  "id" BIGSERIAL PRIMARY KEY,
  "uuid" CHAR(36) NOT NULL,
  "project_id" BIGINT NOT NULL,
  "source_stage_id" BIGINT NOT NULL,
  "target_stage_id" BIGINT NOT NULL,
  "code" VARCHAR(100) NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "selector_type" VARCHAR(100) NOT NULL,
  "selector_config_json" TEXT NULL,
  "carry_over_mode" VARCHAR(100) NOT NULL DEFAULT 'none',
  "target_seed_start" INTEGER NULL,
  "published" SMALLINT NOT NULL DEFAULT 1,
  "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL,
  "modified_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_stage_transition_uuid" UNIQUE ("uuid"),
  CONSTRAINT "uq_jl_stage_transition_code" UNIQUE ("project_id", "code"),
  CONSTRAINT "fk_jl_stage_transition_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_transition_source" FOREIGN KEY ("source_stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_transition_target" FOREIGN KEY ("target_stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_stage_transition_distinct" CHECK ("source_stage_id" <> "target_stage_id"),
  CONSTRAINT "chk_jl_stage_transition_selector" CHECK ("selector_type" ~ '^[a-z][a-z0-9_]*$'),
  CONSTRAINT "chk_jl_stage_transition_carry" CHECK ("carry_over_mode" ~ '^[a-z][a-z0-9_]*$'),
  CONSTRAINT "chk_jl_stage_transition_seed" CHECK ("target_seed_start" IS NULL OR "target_seed_start" > 0),
  CONSTRAINT "chk_jl_stage_transition_published" CHECK ("published" IN (0,1))
);
CREATE INDEX IF NOT EXISTS "idx_jl_stage_transition_source" ON "#__joomleague_stage_transition" ("source_stage_id", "project_id", "published", "ordering");
CREATE INDEX IF NOT EXISTS "idx_jl_stage_transition_target" ON "#__joomleague_stage_transition" ("target_stage_id", "project_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_stage_transition_run" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "transition_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "input_checksum" CHAR(64) NOT NULL, "selector_snapshot_json" TEXT NOT NULL, "resolved_entries_json" TEXT NOT NULL,
  "resolved_count" INTEGER NOT NULL DEFAULT 0, "status" VARCHAR(30) NOT NULL DEFAULT 'applied',
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_stage_transition_run_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_stage_transition_run_input" UNIQUE ("transition_id", "input_checksum"),
  CONSTRAINT "fk_jl_stage_transition_run_transition" FOREIGN KEY ("transition_id") REFERENCES "#__joomleague_stage_transition" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_transition_run_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_stage_transition_run_status" CHECK ("status" IN ('applied'))
);
CREATE INDEX IF NOT EXISTS "idx_jl_stage_transition_run_project" ON "#__joomleague_stage_transition_run" ("project_id", "created");

CREATE TABLE IF NOT EXISTS "#__joomleague_stage_transition_assignment" (
  "transition_id" BIGINT NOT NULL, "target_stage_id" BIGINT NOT NULL, "project_entry_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "run_id" BIGINT NOT NULL, "target_seed" INTEGER NULL, "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY ("transition_id", "project_entry_id"),
  CONSTRAINT "fk_jl_stage_transition_assignment_transition" FOREIGN KEY ("transition_id") REFERENCES "#__joomleague_stage_transition" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_transition_assignment_stage" FOREIGN KEY ("target_stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_transition_assignment_entry" FOREIGN KEY ("project_entry_id", "project_id") REFERENCES "#__joomleague_project_entry" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_transition_assignment_run" FOREIGN KEY ("run_id") REFERENCES "#__joomleague_stage_transition_run" ("id") ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS "idx_jl_stage_transition_assignment_target" ON "#__joomleague_stage_transition_assignment" ("target_stage_id", "project_id", "project_entry_id");
CREATE INDEX IF NOT EXISTS "idx_jl_stage_transition_assignment_run" ON "#__joomleague_stage_transition_assignment" ("run_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_schedule_generation" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "project_id" BIGINT NOT NULL, "stage_id" BIGINT NOT NULL,
  "input_checksum" CHAR(64) NOT NULL, "options_json" TEXT NOT NULL, "round_count" INTEGER NOT NULL DEFAULT 0, "match_count" INTEGER NOT NULL DEFAULT 0,
  "conflict_count" INTEGER NOT NULL DEFAULT 0, "status" VARCHAR(30) NOT NULL DEFAULT 'applied', "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_schedule_generation_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_schedule_generation_input" UNIQUE ("stage_id", "input_checksum"),
  CONSTRAINT "fk_jl_schedule_generation_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_schedule_generation_stage" FOREIGN KEY ("stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "chk_jl_schedule_generation_status" CHECK ("status" IN ('applied'))
);
CREATE INDEX IF NOT EXISTS "idx_jl_schedule_generation_project" ON "#__joomleague_schedule_generation" ("project_id", "created");

CREATE TABLE IF NOT EXISTS "#__joomleague_schedule_generation_match" (
  "generation_id" BIGINT NOT NULL, "match_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL, "round_sequence" INTEGER NOT NULL, "match_sequence" INTEGER NOT NULL,
  PRIMARY KEY ("generation_id", "match_id"), CONSTRAINT "uq_jl_schedule_generation_match" UNIQUE ("match_id"),
  CONSTRAINT "fk_jl_schedule_generation_match_generation" FOREIGN KEY ("generation_id") REFERENCES "#__joomleague_schedule_generation" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_schedule_generation_match_match" FOREIGN KEY ("match_id", "project_id") REFERENCES "#__joomleague_project_match" ("id", "project_id") ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS "#__joomleague_migration_batch" (
  "id" BIGSERIAL PRIMARY KEY,
  "batch_uuid" CHAR(36) NOT NULL,
  "source_product" VARCHAR(100) NOT NULL,
  "source_version" VARCHAR(100) NOT NULL,
  "source_fingerprint" CHAR(64) NOT NULL,
  "state" VARCHAR(30) NOT NULL DEFAULT 'pending',
  "options_json" TEXT NULL,
  "total_records" BIGINT NOT NULL DEFAULT 0,
  "processed_records" BIGINT NOT NULL DEFAULT 0,
  "imported_records" BIGINT NOT NULL DEFAULT 0,
  "skipped_records" BIGINT NOT NULL DEFAULT 0,
  "failed_records" BIGINT NOT NULL DEFAULT 0,
  "started" TIMESTAMP WITHOUT TIME ZONE NULL,
  "finished" TIMESTAMP WITHOUT TIME ZONE NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_migration_batch_uuid" UNIQUE ("batch_uuid"),
  CONSTRAINT "uq_jl_migration_source" UNIQUE ("source_product", "source_fingerprint")
);
CREATE INDEX IF NOT EXISTS "idx_jl_migration_batch_state" ON "#__joomleague_migration_batch" ("state");

CREATE TABLE IF NOT EXISTS "#__joomleague_migration_record" (
  "id" BIGSERIAL PRIMARY KEY,
  "batch_id" BIGINT NOT NULL,
  "source_table" VARCHAR(191) NOT NULL,
  "source_identity_json" TEXT NOT NULL,
  "source_identity_hash" CHAR(64) NOT NULL,
  "source_payload_json" TEXT NULL,
  "source_payload_checksum" CHAR(64) NULL,
  "target_entity" VARCHAR(100) NULL,
  "target_identity" VARCHAR(191) NULL,
  "outcome" VARCHAR(30) NOT NULL DEFAULT 'pending',
  "message_code" VARCHAR(191) NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "processed" TIMESTAMP WITHOUT TIME ZONE NULL,
  CONSTRAINT "uq_jl_migration_record" UNIQUE ("batch_id", "source_table", "source_identity_hash"),
  CONSTRAINT "fk_jl_migration_record_batch"
    FOREIGN KEY ("batch_id") REFERENCES "#__joomleague_migration_batch" ("id")
    ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS "idx_jl_migration_record_outcome" ON "#__joomleague_migration_record" ("batch_id", "outcome");
CREATE INDEX IF NOT EXISTS "idx_jl_migration_record_target" ON "#__joomleague_migration_record" ("target_entity", "target_identity");

CREATE TABLE IF NOT EXISTS "#__joomleague_migration_issue" (
  "id" BIGSERIAL PRIMARY KEY,
  "batch_id" BIGINT NOT NULL,
  "record_id" BIGINT NULL,
  "severity" VARCHAR(20) NOT NULL,
  "issue_code" VARCHAR(191) NOT NULL,
  "message" TEXT NOT NULL,
  "context_json" TEXT NULL,
  "state" VARCHAR(30) NOT NULL DEFAULT 'open',
  "resolution_json" TEXT NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "resolved" TIMESTAMP WITHOUT TIME ZONE NULL,
  "resolved_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "fk_jl_migration_issue_batch"
    FOREIGN KEY ("batch_id") REFERENCES "#__joomleague_migration_batch" ("id")
    ON DELETE CASCADE,
  CONSTRAINT "fk_jl_migration_issue_record"
    FOREIGN KEY ("record_id") REFERENCES "#__joomleague_migration_record" ("id")
    ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS "idx_jl_migration_issue_batch" ON "#__joomleague_migration_issue" ("batch_id", "state", "severity");
CREATE INDEX IF NOT EXISTS "idx_jl_migration_issue_record" ON "#__joomleague_migration_issue" ("record_id");
