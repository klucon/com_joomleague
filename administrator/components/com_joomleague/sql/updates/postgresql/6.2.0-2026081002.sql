ALTER TABLE "#__joomleague_project_stage" ADD COLUMN "entry_selection_mode" VARCHAR(30) NOT NULL DEFAULT 'inherit_project';
ALTER TABLE "#__joomleague_project_stage" ADD CONSTRAINT "uq_jl_project_stage_owner" UNIQUE ("id", "project_id");
ALTER TABLE "#__joomleague_project_stage" ADD CONSTRAINT "chk_jl_project_stage_entry_mode" CHECK ("entry_selection_mode" IN ('inherit_project', 'explicit'));
ALTER TABLE "#__joomleague_project_entry" ADD CONSTRAINT "uq_jl_project_entry_owner" UNIQUE ("id", "project_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_stage_entry" (
  "stage_id" BIGINT NOT NULL, "entry_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "ordering" INTEGER NOT NULL DEFAULT 0, "seed_number" INTEGER NULL, "metadata_json" TEXT NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "pk_jl_stage_entry" PRIMARY KEY ("stage_id", "entry_id"),
  CONSTRAINT "fk_jl_stage_entry_stage" FOREIGN KEY ("stage_id", "project_id") REFERENCES "#__joomleague_project_stage" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_stage_entry_entry" FOREIGN KEY ("entry_id", "project_id") REFERENCES "#__joomleague_project_entry" ("id", "project_id") ON DELETE CASCADE
);
CREATE INDEX "idx_jl_stage_entry_entry" ON "#__joomleague_stage_entry" ("entry_id", "project_id") /** CAN FAIL **/;
