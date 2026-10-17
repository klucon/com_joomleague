ALTER TABLE `#__joomleague_person`
  ADD COLUMN `height_cm` DECIMAL(6,2) NULL DEFAULT NULL AFTER `death_date`,
  ADD COLUMN `weight_kg` DECIMAL(6,2) NULL DEFAULT NULL AFTER `height_cm`,
  ADD CONSTRAINT `chk_jl_person_height` CHECK (`height_cm` IS NULL OR (`height_cm` >= 20 AND `height_cm` <= 300)),
  ADD CONSTRAINT `chk_jl_person_weight` CHECK (`weight_kg` IS NULL OR (`weight_kg` >= 1 AND `weight_kg` <= 500));

UPDATE `#__joomleague_person`
SET
  `height_cm` = CASE
    WHEN JSON_UNQUOTE(JSON_EXTRACT(`metadata_json`, '$.legacy.height')) REGEXP '^[0-9]+([.][0-9]+)?$'
      AND CAST(JSON_UNQUOTE(JSON_EXTRACT(`metadata_json`, '$.legacy.height')) AS DECIMAL(6,2)) BETWEEN 20 AND 300
      THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(`metadata_json`, '$.legacy.height')) AS DECIMAL(6,2))
    ELSE NULL
  END,
  `weight_kg` = CASE
    WHEN JSON_UNQUOTE(JSON_EXTRACT(`metadata_json`, '$.legacy.weight')) REGEXP '^[0-9]+([.][0-9]+)?$'
      AND CAST(JSON_UNQUOTE(JSON_EXTRACT(`metadata_json`, '$.legacy.weight')) AS DECIMAL(6,2)) BETWEEN 1 AND 500
      THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(`metadata_json`, '$.legacy.weight')) AS DECIMAL(6,2))
    ELSE NULL
  END
WHERE `metadata_json` IS NOT NULL
  AND JSON_VALID(`metadata_json`) = 1;
