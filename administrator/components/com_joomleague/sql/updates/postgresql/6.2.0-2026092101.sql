ALTER TABLE "#__joomleague_person"
  ADD COLUMN "height_cm" NUMERIC(6,2) NULL,
  ADD COLUMN "weight_kg" NUMERIC(6,2) NULL,
  ADD CONSTRAINT "chk_jl_person_height" CHECK ("height_cm" IS NULL OR ("height_cm" >= 20 AND "height_cm" <= 300)),
  ADD CONSTRAINT "chk_jl_person_weight" CHECK ("weight_kg" IS NULL OR ("weight_kg" >= 1 AND "weight_kg" <= 500));

UPDATE "#__joomleague_person"
SET
  "height_cm" = CASE
    WHEN substring("metadata_json" FROM '"legacy"\s*:\s*\{[^}]*"height"\s*:\s*"?([0-9]+(?:\.[0-9]+)?)')::numeric BETWEEN 20 AND 300
      THEN substring("metadata_json" FROM '"legacy"\s*:\s*\{[^}]*"height"\s*:\s*"?([0-9]+(?:\.[0-9]+)?)')::numeric
    ELSE NULL
  END,
  "weight_kg" = CASE
    WHEN substring("metadata_json" FROM '"legacy"\s*:\s*\{[^}]*"weight"\s*:\s*"?([0-9]+(?:\.[0-9]+)?)')::numeric BETWEEN 1 AND 500
      THEN substring("metadata_json" FROM '"legacy"\s*:\s*\{[^}]*"weight"\s*:\s*"?([0-9]+(?:\.[0-9]+)?)')::numeric
    ELSE NULL
  END
WHERE "metadata_json" IS NOT NULL;
