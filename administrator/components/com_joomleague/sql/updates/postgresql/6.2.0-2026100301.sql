ALTER TABLE "#__joomleague_project" DROP CONSTRAINT "fk_jl_project_sport_binding";
ALTER TABLE "#__joomleague_project" ADD CONSTRAINT "fk_jl_project_sport_type" FOREIGN KEY ("sport_type_id") REFERENCES "#__joomleague_sport_type" ("id") ON DELETE RESTRICT;
ALTER TABLE "#__joomleague_project" ADD CONSTRAINT "fk_jl_project_profile_version" FOREIGN KEY ("profile_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE RESTRICT;
CREATE TABLE IF NOT EXISTS "#__joomleague_project_profile_migration" (
 "id" BIGSERIAL PRIMARY KEY,
 "project_id" BIGINT NOT NULL,
 "from_version_id" BIGINT NOT NULL,
 "to_version_id" BIGINT NOT NULL,
 "review_checksum" CHAR(64) NOT NULL,
 "changes_json" TEXT NOT NULL,
 "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
 "created_by" BIGINT NOT NULL,
 CONSTRAINT "fk_jl_profile_migration_project" FOREIGN KEY ("project_id") REFERENCES "#__joomleague_project" ("id") ON DELETE CASCADE,
 CONSTRAINT "fk_jl_profile_migration_from" FOREIGN KEY ("from_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE RESTRICT,
 CONSTRAINT "fk_jl_profile_migration_to" FOREIGN KEY ("to_version_id") REFERENCES "#__joomleague_sport_profile_version" ("id") ON DELETE RESTRICT
);
CREATE INDEX "idx_jl_profile_migration_project" ON "#__joomleague_project_profile_migration" ("project_id", "id");
