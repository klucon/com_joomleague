CREATE TABLE IF NOT EXISTS "#__joomleague_venue" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "owner_club_id" BIGINT NULL,
  "name" VARCHAR(255) NOT NULL, "alias" VARCHAR(255) NOT NULL DEFAULT '',
  "short_name" VARCHAR(100) NOT NULL DEFAULT '', "nickname" VARCHAR(100) NOT NULL DEFAULT '',
  "address" VARCHAR(255) NOT NULL DEFAULT '', "postal_code" VARCHAR(20) NOT NULL DEFAULT '',
  "city" VARCHAR(100) NOT NULL DEFAULT '', "region" VARCHAR(100) NOT NULL DEFAULT '',
  "country_code" VARCHAR(8) NULL, "latitude" NUMERIC(10,7) NULL, "longitude" NUMERIC(10,7) NULL,
  "timezone" VARCHAR(64) NULL, "capacity" BIGINT NULL, "website" VARCHAR(2048) NULL,
  "picture" VARCHAR(255) NULL, "description" TEXT NULL, "external_code" VARCHAR(191) NULL,
  "metadata_json" TEXT NULL, "published" SMALLINT NOT NULL DEFAULT 1, "ordering" INTEGER NOT NULL DEFAULT 0,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  "modified" TIMESTAMP WITHOUT TIME ZONE NULL, "modified_by" BIGINT NOT NULL DEFAULT 0,
  "checked_out" BIGINT NULL, "checked_out_time" TIMESTAMP WITHOUT TIME ZONE NULL, "asset_id" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_venue_uuid" UNIQUE ("uuid"),
  CONSTRAINT "fk_jl_venue_owner_club" FOREIGN KEY ("owner_club_id") REFERENCES "#__joomleague_club" ("id") ON DELETE SET NULL,
  CONSTRAINT "chk_jl_venue_latitude" CHECK ("latitude" IS NULL OR ("latitude" >= -90 AND "latitude" <= 90)),
  CONSTRAINT "chk_jl_venue_longitude" CHECK ("longitude" IS NULL OR ("longitude" >= -180 AND "longitude" <= 180)),
  CONSTRAINT "chk_jl_venue_capacity" CHECK ("capacity" IS NULL OR "capacity" >= 0)
);
CREATE INDEX "idx_jl_venue_owner_club" ON "#__joomleague_venue" ("owner_club_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_venue_state" ON "#__joomleague_venue" ("published", "ordering") /** CAN FAIL **/;
