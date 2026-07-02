INSERT INTO `#__joomleague_version` (`major`, `minor`, `build`, `count`, `revision`, `file`, `version`)
SELECT 0, 14, 0, 0, '', '0.14.0.sql', '0.14.0'
WHERE NOT EXISTS (
	SELECT 1
	FROM `#__joomleague_version`
	WHERE `version` = '0.14.0'
);
