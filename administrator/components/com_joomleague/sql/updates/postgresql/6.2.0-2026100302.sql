CREATE TABLE IF NOT EXISTS "#__joomleague_sport_category" (
 "id" BIGSERIAL PRIMARY KEY,
 "uuid" CHAR(36) NOT NULL,
 "code" VARCHAR(100) NOT NULL,
 "parent_id" BIGINT NULL DEFAULT NULL,
 "name" VARCHAR(255) NOT NULL,
 "description" TEXT NOT NULL,
 "published" SMALLINT NOT NULL DEFAULT 1,
 "ordering" INTEGER NOT NULL DEFAULT 0,
 "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
 "created_by" BIGINT NOT NULL DEFAULT 0,
 "modified" TIMESTAMP WITHOUT TIME ZONE NULL DEFAULT NULL,
 "modified_by" BIGINT NOT NULL DEFAULT 0,
 CONSTRAINT "uq_jl_sport_category_uuid" UNIQUE ("uuid"),
 CONSTRAINT "uq_jl_sport_category_code" UNIQUE ("code"),
 CONSTRAINT "fk_jl_sport_category_parent" FOREIGN KEY ("parent_id") REFERENCES "#__joomleague_sport_category" ("id") ON DELETE RESTRICT
);
CREATE INDEX "idx_jl_sport_category_parent" ON "#__joomleague_sport_category" ("parent_id");
CREATE TABLE IF NOT EXISTS "#__joomleague_sport_category_lock" (
 "id" INTEGER PRIMARY KEY
);
INSERT INTO "#__joomleague_sport_category_lock" ("id") VALUES (1);
CREATE TABLE IF NOT EXISTS "#__joomleague_sport_profile_category" (
 "profile_id" BIGINT NOT NULL,
 "category_id" BIGINT NOT NULL,
 PRIMARY KEY ("profile_id", "category_id"),
 CONSTRAINT "fk_jl_profile_category_profile" FOREIGN KEY ("profile_id") REFERENCES "#__joomleague_sport_profile" ("id") ON DELETE CASCADE,
 CONSTRAINT "fk_jl_profile_category_category" FOREIGN KEY ("category_id") REFERENCES "#__joomleague_sport_category" ("id") ON DELETE RESTRICT
);
CREATE INDEX "idx_jl_profile_category_category" ON "#__joomleague_sport_profile_category" ("category_id");
