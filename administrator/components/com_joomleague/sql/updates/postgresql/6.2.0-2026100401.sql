CREATE TABLE IF NOT EXISTS "#__joomleague_sport_variant" (
 "id" BIGSERIAL PRIMARY KEY,
 "uuid" CHAR(36) NOT NULL,
 "profile_id" BIGINT NOT NULL,
 "code" VARCHAR(100) NOT NULL,
 "name" VARCHAR(255) NOT NULL,
 "published" SMALLINT NOT NULL DEFAULT 0,
 "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
 "created_by" BIGINT NOT NULL DEFAULT 0,
 CONSTRAINT "uq_jl_variant_uuid" UNIQUE ("uuid"),
 CONSTRAINT "uq_jl_variant_code" UNIQUE ("profile_id", "code"),
 CONSTRAINT "fk_jl_variant_profile" FOREIGN KEY ("profile_id") REFERENCES "#__joomleague_sport_profile" ("id") ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS "#__joomleague_sport_variant_version" (
 "id" BIGSERIAL PRIMARY KEY,
 "variant_id" BIGINT NOT NULL,
 "base_profile_version_id" BIGINT NOT NULL,
 "revision" VARCHAR(50) NOT NULL,
 "overrides_json" TEXT NOT NULL,
 "overrides_checksum" CHAR(64) NOT NULL,
 "base_checksum" CHAR(64) NOT NULL,
 "payload_json" TEXT NOT NULL,
 "payload_checksum" CHAR(64) NOT NULL,
 "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
 "created_by" BIGINT NOT NULL DEFAULT 0,
 CONSTRAINT "uq_jl_variant_revision" UNIQUE ("variant_id", "revision"),
 CONSTRAINT "fk_jl_variant_revision_variant" FOREIGN KEY ("variant_id") REFERENCES "#__joomleague_sport_variant" ("id") ON DELETE RESTRICT,
 CONSTRAINT "fk_jl_variant_revision_base" FOREIGN KEY ("base_profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE RESTRICT
);
CREATE INDEX "idx_jl_variant_base" ON "#__joomleague_sport_variant_version" ("base_profile_version_id");
