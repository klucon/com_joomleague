INSERT INTO `#__joomleague_template_config` (`project_id`, `template`, `func`, `title`, `params`, `published`)
SELECT `p`.`id`, `d`.`template`, '', `d`.`title`, `d`.`params`, 1
FROM `#__joomleague_project` AS `p`
CROSS JOIN (
	SELECT 'projectheading' AS `template`, 'COM_JOOMLEAGUE_TEMPLATE_PROJECTHEADING' AS `title`, 'layout=default
show_title=1
show_project_logo=1
show_project_name=1' AS `params`
	UNION ALL SELECT 'ranking', 'COM_JOOMLEAGUE_TEMPLATE_RANKING', 'layout=default
show_title=1
show_logo=1
show_form=1
show_points=1'
	UNION ALL SELECT 'results', 'COM_JOOMLEAGUE_TEMPLATE_RESULTS', 'layout=default
show_title=1
show_match_number=1
show_venue=1'
	UNION ALL SELECT 'matches', 'COM_JOOMLEAGUE_TEMPLATE_MATCHES', 'layout=default
show_title=1
show_date=1
show_time=1
show_referees=1'
	UNION ALL SELECT 'matchreport', 'COM_JOOMLEAGUE_TEMPLATE_MATCHREPORT', 'layout=default
show_title=1
show_lineups=1
show_events=1
show_statistics=1'
	UNION ALL SELECT 'teaminfo', 'COM_JOOMLEAGUE_TEMPLATE_TEAMINFO', 'layout=default
show_title=1
show_logo=1
show_club=1
show_stadium=1'
	UNION ALL SELECT 'teamplan', 'COM_JOOMLEAGUE_TEMPLATE_TEAMPLAN', 'layout=default
show_title=1
show_home_away=1
show_results=1'
	UNION ALL SELECT 'teamstats', 'COM_JOOMLEAGUE_TEMPLATE_TEAMSTATS', 'layout=default
show_title=1
show_events=1
show_statistics=1'
	UNION ALL SELECT 'roster', 'COM_JOOMLEAGUE_TEMPLATE_ROSTER', 'layout=default
show_title=1
show_positions=1
show_staff=1'
	UNION ALL SELECT 'players', 'COM_JOOMLEAGUE_TEMPLATE_PLAYERS', 'layout=default
show_title=1
show_positions=1
show_birthdays=1'
	UNION ALL SELECT 'staff', 'COM_JOOMLEAGUE_TEMPLATE_STAFF', 'layout=default
show_title=1
show_positions=1'
	UNION ALL SELECT 'playground', 'COM_JOOMLEAGUE_TEMPLATE_PLAYGROUND', 'layout=default
show_title=1
show_address=1
show_map=0'
	UNION ALL SELECT 'stats', 'COM_JOOMLEAGUE_TEMPLATE_STATS', 'layout=default
show_title=1
show_player_stats=1
show_team_stats=1'
	UNION ALL SELECT 'matrix', 'COM_JOOMLEAGUE_TEMPLATE_MATRIX', 'layout=default
show_title=1
show_results=1'
	UNION ALL SELECT 'nextmatch', 'COM_JOOMLEAGUE_TEMPLATE_NEXTMATCH', 'layout=default
show_title=1
show_countdown=0
show_venue=1'
	UNION ALL SELECT 'overall', 'COM_JOOMLEAGUE_TEMPLATE_OVERALL', 'layout=default
show_title=1
show_ranking=1
show_results=1
show_nextmatch=1'
) AS `d`
WHERE NOT EXISTS (
	SELECT 1
	FROM `#__joomleague_template_config` AS `tc`
	WHERE `tc`.`project_id` = `p`.`id`
		AND `tc`.`template` = `d`.`template`
		AND `tc`.`func` = ''
);
