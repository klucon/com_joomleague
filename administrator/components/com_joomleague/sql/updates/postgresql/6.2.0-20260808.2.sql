CREATE TABLE IF NOT EXISTS "#__joomleague_club" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL,
  "alias" VARCHAR(255) NOT NULL DEFAULT '', "short_name" VARCHAR(100) NOT NULL DEFAULT '',
  "country_code" VARCHAR(8) NULL, "website" VARCHAR(2048) NULL, "founded_date" DATE NULL,
  "dissolved_date" DATE NULL, "description" TEXT NULL, "external_code" VARCHAR(191) NULL,
  "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_club_uuid" UNIQUE ("uuid"),
  CONSTRAINT "chk_jl_club_dates" CHECK ("dissolved_date" IS NULL OR "founded_date" IS NULL OR "dissolved_date" >= "founded_date")
);
CREATE INDEX "idx_jl_club_state" ON "#__joomleague_club" ("published", "ordering") /** CAN FAIL **/;
CREATE TABLE IF NOT EXISTS "#__joomleague_team" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "club_id" BIGINT NULL, "name" VARCHAR(255) NOT NULL,
  "middle_name" VARCHAR(100) NOT NULL DEFAULT '', "short_name" VARCHAR(50) NOT NULL DEFAULT '',
  "alias" VARCHAR(255) NOT NULL DEFAULT '', "website" VARCHAR(2048) NULL, "picture" VARCHAR(255) NULL,
  "description" TEXT NULL, "external_code" VARCHAR(191) NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_team_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_team_club" FOREIGN KEY ("club_id") REFERENCES "#__joomleague_club" ("id") ON DELETE RESTRICT
);
CREATE INDEX "idx_jl_team_club" ON "#__joomleague_team" ("club_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_team_state" ON "#__joomleague_team" ("published", "ordering") /** CAN FAIL **/;
CREATE TABLE IF NOT EXISTS "#__joomleague_person" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "contact_id" BIGINT NULL,
  "first_name" VARCHAR(100) NOT NULL DEFAULT '', "last_name" VARCHAR(100) NOT NULL DEFAULT '',
  "nickname" VARCHAR(100) NOT NULL DEFAULT '', "alias" VARCHAR(255) NOT NULL DEFAULT '',
  "country_code" VARCHAR(8) NULL, "birth_date" DATE NULL, "death_date" DATE NULL,
  "picture" VARCHAR(255) NULL, "description" TEXT NULL, "external_code" VARCHAR(191) NULL, "metadata_json" TEXT NULL,
  "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_person_uuid" UNIQUE ("uuid"),
  CONSTRAINT "chk_jl_person_dates" CHECK ("death_date" IS NULL OR "birth_date" IS NULL OR "death_date" >= "birth_date")
);
CREATE INDEX "idx_jl_person_name" ON "#__joomleague_person" ("last_name", "first_name") /** CAN FAIL **/;
CREATE INDEX "idx_jl_person_state" ON "#__joomleague_person" ("published", "ordering") /** CAN FAIL **/;
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
  CONSTRAINT "fk_jl_project_entry_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_project_entry_team" FOREIGN KEY ("team_id") REFERENCES "#__joomleague_team" ("id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_project_entry_person" FOREIGN KEY ("person_id") REFERENCES "#__joomleague_person" ("id") ON DELETE RESTRICT,
  CONSTRAINT "chk_jl_project_entry_kind" CHECK ("entry_kind" IN ('team', 'person', 'group')),
  CONSTRAINT "chk_jl_project_entry_target" CHECK (("entry_kind" = 'team' AND "team_id" IS NOT NULL AND "person_id" IS NULL) OR ("entry_kind" = 'person' AND "person_id" IS NOT NULL AND "team_id" IS NULL) OR ("entry_kind" = 'group' AND "team_id" IS NULL AND "person_id" IS NULL AND "display_name" <> ''))
);
CREATE INDEX "idx_jl_project_entry_state" ON "#__joomleague_project_entry" ("project_id", "published", "ordering") /** CAN FAIL **/;
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
CREATE INDEX "idx_jl_entry_member_entry" ON "#__joomleague_project_entry_member" ("entry_id", "published", "ordering") /** CAN FAIL **/;
CREATE INDEX "idx_jl_entry_member_person" ON "#__joomleague_project_entry_member" ("person_id", "valid_from", "valid_until") /** CAN FAIL **/;