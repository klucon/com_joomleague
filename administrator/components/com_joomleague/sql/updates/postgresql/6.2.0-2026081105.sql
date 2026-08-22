CREATE TABLE IF NOT EXISTS "#__joomleague_project_actor_role" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "project_id" BIGINT NOT NULL, "actor_kind" VARCHAR(20) NOT NULL,
  "person_id" BIGINT NULL, "team_id" BIGINT NULL, "role_code" VARCHAR(100) NOT NULL, "person_type" VARCHAR(100) NOT NULL,
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
CREATE INDEX "idx_jl_project_actor_role_project" ON "#__joomleague_project_actor_role" ("project_id", "person_type", "published", "ordering") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_actor_role_person" ON "#__joomleague_project_actor_role" ("person_id", "valid_from", "valid_until") /** CAN FAIL **/;
CREATE INDEX "idx_jl_project_actor_role_team" ON "#__joomleague_project_actor_role" ("team_id", "valid_from", "valid_until") /** CAN FAIL **/;
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
CREATE INDEX "idx_jl_match_actor_role_match" ON "#__joomleague_match_actor_role" ("match_id", "person_type", "ordering") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_actor_role_project" ON "#__joomleague_match_actor_role" ("project_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_actor_role_person" ON "#__joomleague_match_actor_role" ("person_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_actor_role_team" ON "#__joomleague_match_actor_role" ("team_id") /** CAN FAIL **/;
