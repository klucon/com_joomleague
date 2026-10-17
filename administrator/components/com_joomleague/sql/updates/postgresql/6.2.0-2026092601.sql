ALTER TABLE "#__joomleague_sport_position" ADD COLUMN "role_domain" VARCHAR(100) NULL;
UPDATE "#__joomleague_sport_position" SET "role_domain" = CASE
  WHEN "person_type" IN ('player', 'participant') THEN 'participant'
  WHEN "person_type" = 'staff' THEN 'team_staff'
  WHEN "code" IN ('arbiter','chief_arbiter','chair_umpire','course_referee','finish_referee','first_referee','fourth_official','judge','lane_official','line_judge','line_umpire','linesman_1','linesman_2','referee','second_referee','start_referee','steward','touch_judge','var_referee','assistant_referee_1','assistant_referee_2') THEN 'adjudicator'
  WHEN "code" IN ('admin','caller','delegate','marker','observer','scorer','table_official','timekeeper','video_goal_judge') THEN 'competition_official'
  ELSE 'event_staff'
END;
ALTER TABLE "#__joomleague_sport_position" ALTER COLUMN "role_domain" SET NOT NULL;

ALTER TABLE "#__joomleague_project_actor_role" ADD COLUMN "role_domain" VARCHAR(100) NULL;
UPDATE "#__joomleague_project_actor_role" SET "role_domain" = CASE
  WHEN "role_code" IN ('arbiter','chief_arbiter','chair_umpire','course_referee','finish_referee','first_referee','fourth_official','judge','lane_official','line_judge','line_umpire','linesman_1','linesman_2','referee','second_referee','start_referee','steward','touch_judge','var_referee','assistant_referee_1','assistant_referee_2') THEN 'adjudicator'
  WHEN "role_code" IN ('admin','caller','delegate','marker','observer','scorer','table_official','timekeeper','video_goal_judge') THEN 'competition_official'
  ELSE 'event_staff'
END;
ALTER TABLE "#__joomleague_project_actor_role" ALTER COLUMN "role_domain" SET NOT NULL;

ALTER TABLE "#__joomleague_match_actor_role" ADD COLUMN "role_domain" VARCHAR(100) NULL;
ALTER TABLE "#__joomleague_match_actor_role" ADD COLUMN "delegation_status" VARCHAR(30) NOT NULL DEFAULT 'delegated';
UPDATE "#__joomleague_match_actor_role" SET "role_domain" = CASE
  WHEN "role_code" IN ('arbiter','chief_arbiter','chair_umpire','course_referee','finish_referee','first_referee','fourth_official','judge','lane_official','line_judge','line_umpire','linesman_1','linesman_2','referee','second_referee','start_referee','steward','touch_judge','var_referee','assistant_referee_1','assistant_referee_2') THEN 'adjudicator'
  WHEN "role_code" IN ('admin','caller','delegate','marker','observer','scorer','table_official','timekeeper','video_goal_judge') THEN 'competition_official'
  ELSE 'event_staff'
END;
ALTER TABLE "#__joomleague_match_actor_role" ALTER COLUMN "role_domain" SET NOT NULL;
ALTER TABLE "#__joomleague_match_actor_role" ADD CONSTRAINT "uq_jl_match_actor_role_scope" UNIQUE ("id", "match_id");

CREATE TABLE IF NOT EXISTS "#__joomleague_match_actor_role_change" (
  "id" BIGSERIAL PRIMARY KEY, "uuid" CHAR(36) NOT NULL, "match_id" BIGINT NOT NULL, "project_id" BIGINT NOT NULL,
  "outgoing_match_actor_role_id" BIGINT NULL, "incoming_match_actor_role_id" BIGINT NOT NULL,
  "role_code" VARCHAR(100) NOT NULL, "role_domain" VARCHAR(100) NOT NULL, "sequence_number" INTEGER NOT NULL,
  "phase_code" VARCHAR(100) NULL, "phase_sequence" INTEGER NULL, "clock_value" NUMERIC(30,9) NULL, "clock_unit" VARCHAR(50) NULL,
  "occurred_at" TIMESTAMP WITHOUT TIME ZONE NULL, "reason_code" VARCHAR(100) NOT NULL DEFAULT 'other', "notes" TEXT NULL,
  "created" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, "created_by" BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT "uq_jl_match_actor_change_uuid" UNIQUE ("uuid"), CONSTRAINT "uq_jl_match_actor_change_sequence" UNIQUE ("match_id", "sequence_number"),
  CONSTRAINT "fk_jl_match_actor_change_match" FOREIGN KEY ("match_id", "project_id") REFERENCES "#__joomleague_project_match" ("id", "project_id") ON DELETE CASCADE,
  CONSTRAINT "fk_jl_match_actor_change_outgoing" FOREIGN KEY ("outgoing_match_actor_role_id", "match_id") REFERENCES "#__joomleague_match_actor_role" ("id", "match_id") ON DELETE RESTRICT,
  CONSTRAINT "fk_jl_match_actor_change_incoming" FOREIGN KEY ("incoming_match_actor_role_id", "match_id") REFERENCES "#__joomleague_match_actor_role" ("id", "match_id") ON DELETE RESTRICT,
  CONSTRAINT "chk_jl_match_actor_change_people" CHECK ("outgoing_match_actor_role_id" IS NULL OR "outgoing_match_actor_role_id" <> "incoming_match_actor_role_id"),
  CONSTRAINT "chk_jl_match_actor_change_sequence" CHECK ("sequence_number" > 0), CONSTRAINT "chk_jl_match_actor_change_phase" CHECK ("phase_sequence" IS NULL OR "phase_sequence" > 0),
  CONSTRAINT "chk_jl_match_actor_change_clock" CHECK ("clock_value" IS NULL OR "clock_value" >= 0), CONSTRAINT "chk_jl_match_actor_change_clock_unit" CHECK (("clock_value" IS NULL AND "clock_unit" IS NULL) OR ("clock_value" IS NOT NULL AND "clock_unit" IS NOT NULL))
);
CREATE INDEX "idx_jl_match_actor_change_role" ON "#__joomleague_match_actor_role_change" ("match_id", "role_domain", "role_code") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_actor_change_outgoing" ON "#__joomleague_match_actor_role_change" ("outgoing_match_actor_role_id", "match_id") /** CAN FAIL **/;
CREATE INDEX "idx_jl_match_actor_change_incoming" ON "#__joomleague_match_actor_role_change" ("incoming_match_actor_role_id", "match_id") /** CAN FAIL **/;
