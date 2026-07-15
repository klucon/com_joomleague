-- Schema baseline marker.
-- The full, current schema lives entirely in ../../install.mysql.utf8.sql.
-- All historical incremental update files (0.2.0 through 6.1.0-alpha-151)
-- have been removed: this project has no production installs on those
-- older versions to upgrade from (still alpha). New schema changes going
-- forward should get their own update file here, named after the version
-- that introduces them.
SELECT 1;
