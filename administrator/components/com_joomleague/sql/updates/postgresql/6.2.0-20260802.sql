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
CREATE INDEX "idx_jl_sport_profile_published" ON "#__joomleague_sport_profile" ("published");

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
CREATE INDEX "idx_jl_profile_version_state" ON "#__joomleague_sport_profile_version" ("state");

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
  CONSTRAINT "fk_jl_sport_type_profile_version"
    FOREIGN KEY ("profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id")
    ON DELETE RESTRICT
);
CREATE INDEX "idx_jl_sport_type_profile" ON "#__joomleague_sport_type" ("profile_version_id");
CREATE INDEX "idx_jl_sport_type_state" ON "#__joomleague_sport_type" ("published", "ordering");

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
CREATE INDEX "idx_jl_migration_batch_state" ON "#__joomleague_migration_batch" ("state");

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
CREATE INDEX "idx_jl_migration_record_outcome" ON "#__joomleague_migration_record" ("batch_id", "outcome");
CREATE INDEX "idx_jl_migration_record_target" ON "#__joomleague_migration_record" ("target_entity", "target_identity");

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
CREATE INDEX "idx_jl_migration_issue_batch" ON "#__joomleague_migration_issue" ("batch_id", "state", "severity");
CREATE INDEX "idx_jl_migration_issue_record" ON "#__joomleague_migration_issue" ("record_id");
