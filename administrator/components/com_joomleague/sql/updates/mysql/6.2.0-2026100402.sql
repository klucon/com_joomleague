ALTER TABLE `#__joomleague_sport_variant_version` ADD COLUMN `compiled_profile_version_id` BIGINT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `#__joomleague_sport_variant_version` ADD CONSTRAINT `uq_jl_variant_compiled_binding` UNIQUE (`id`, `compiled_profile_version_id`);
ALTER TABLE `#__joomleague_sport_variant_version` ADD CONSTRAINT `fk_jl_variant_compiled_profile` FOREIGN KEY (`compiled_profile_version_id`) REFERENCES `#__joomleague_sport_profile_version` (`id`) ON DELETE RESTRICT;
ALTER TABLE `#__joomleague_project` ADD COLUMN `sport_variant_version_id` BIGINT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `#__joomleague_project` ADD CONSTRAINT `fk_jl_project_variant_binding` FOREIGN KEY (`sport_variant_version_id`, `profile_version_id`) REFERENCES `#__joomleague_sport_variant_version` (`id`, `compiled_profile_version_id`) ON DELETE RESTRICT;
