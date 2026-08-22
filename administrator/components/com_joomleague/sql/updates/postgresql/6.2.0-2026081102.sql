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
CREATE INDEX "idx_jl_org_name_history_club" ON "#__joomleague_organization_name_history" ("club_id", "valid_from", "ordering");
CREATE INDEX "idx_jl_org_name_history_team" ON "#__joomleague_organization_name_history" ("team_id", "valid_from", "ordering");

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
CREATE INDEX "idx_jl_org_media_history_club" ON "#__joomleague_organization_media_history" ("club_id", "media_type", "valid_from", "ordering");
CREATE INDEX "idx_jl_org_media_history_team" ON "#__joomleague_organization_media_history" ("team_id", "media_type", "valid_from", "ordering");
