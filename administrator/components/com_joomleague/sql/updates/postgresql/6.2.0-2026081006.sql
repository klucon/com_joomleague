ALTER TABLE "#__joomleague_match_participant" DROP CONSTRAINT "fk_jl_match_participant_entry";
ALTER TABLE "#__joomleague_match_participant" ADD CONSTRAINT "fk_jl_match_participant_entry" FOREIGN KEY ("project_entry_id", "project_id") REFERENCES "#__joomleague_project_entry" ("id", "project_id") ON DELETE CASCADE;
