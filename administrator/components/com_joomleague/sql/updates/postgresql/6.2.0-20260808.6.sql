CREATE TABLE IF NOT EXISTS "#__joomleague_sport_position" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "sport_type_id" BIGINT NOT NULL, "source_profile_version_id" BIGINT NULL,
  "code" VARCHAR(100) NOT NULL, "name" VARCHAR(255) NOT NULL DEFAULT '', "name_key" VARCHAR(255) NULL, "person_type" VARCHAR(100) NOT NULL, "lineup_group" VARCHAR(100) NULL,
  "parent_id" BIGINT NULL, "has_events" SMALLINT NULL, "has_statistics" SMALLINT NULL, "source" VARCHAR(30) NOT NULL DEFAULT 'local', "source_checksum" CHAR(64) NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0, "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0, "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_sport_position_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_sport_position_code" UNIQUE ("sport_type_id","code"),
  CONSTRAINT "fk_jl_sport_position_type" FOREIGN KEY ("sport_type_id") REFERENCES "#__joomleague_sport_type" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_sport_position_profile" FOREIGN KEY ("source_profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE SET NULL,
  CONSTRAINT "fk_jl_sport_position_parent" FOREIGN KEY ("parent_id") REFERENCES "#__joomleague_sport_position" ("id") ON DELETE SET NULL
);
CREATE INDEX "idx_jl_sport_position_parent" ON "#__joomleague_sport_position" ("parent_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_sport_position_state" ON "#__joomleague_sport_position" ("published","ordering") /** CAN FAIL **/;

CREATE TABLE IF NOT EXISTS "#__joomleague_event_type" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "sport_type_id" BIGINT NOT NULL, "source_profile_version_id" BIGINT NULL,
  "code" VARCHAR(100) NOT NULL, "name" VARCHAR(255) NOT NULL DEFAULT '', "name_key" VARCHAR(255) NULL, "person_type" VARCHAR(100) NULL, "timeline" SMALLINT NOT NULL DEFAULT 0,
  "direction" SMALLINT NOT NULL DEFAULT 0, "affects_score" SMALLINT NOT NULL DEFAULT 0, "score_delta" NUMERIC(12,4) NULL, "score_target" VARCHAR(30) NULL,
  "requires_second_person" SMALLINT NOT NULL DEFAULT 0, "leads_to_suspension" SMALLINT NOT NULL DEFAULT 0, "system_event" SMALLINT NOT NULL DEFAULT 0,
  "source" VARCHAR(30) NOT NULL DEFAULT 'local', "source_checksum" CHAR(64) NULL, "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0, "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_event_type_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_event_type_code" UNIQUE ("sport_type_id","code"),
  CONSTRAINT "fk_jl_event_type_sport_type" FOREIGN KEY ("sport_type_id") REFERENCES "#__joomleague_sport_type" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_event_type_profile" FOREIGN KEY ("source_profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE SET NULL
);
CREATE INDEX "idx_jl_event_type_state" ON "#__joomleague_event_type" ("published","ordering") /** CAN FAIL **/;

CREATE TABLE IF NOT EXISTS "#__joomleague_statistic" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "sport_type_id" BIGINT NOT NULL, "source_profile_version_id" BIGINT NULL,
  "code" VARCHAR(100) NOT NULL, "name" VARCHAR(255) NOT NULL DEFAULT '', "name_key" VARCHAR(255) NULL, "abbreviation_key" VARCHAR(255) NULL,
  "statistic_type" VARCHAR(100) NOT NULL DEFAULT 'basic', "scope" VARCHAR(100) NOT NULL, "value_type" VARCHAR(100) NOT NULL DEFAULT 'integer', "calculation_source" VARCHAR(100) NOT NULL DEFAULT 'manual',
  "source" VARCHAR(30) NOT NULL DEFAULT 'local', "source_checksum" CHAR(64) NULL, "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0, "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_statistic_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_statistic_code" UNIQUE ("sport_type_id","code"),
  CONSTRAINT "fk_jl_statistic_sport_type" FOREIGN KEY ("sport_type_id") REFERENCES "#__joomleague_sport_type" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_statistic_profile" FOREIGN KEY ("source_profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE SET NULL
);
CREATE INDEX "idx_jl_statistic_state" ON "#__joomleague_statistic" ("published","ordering") /** CAN FAIL **/;
