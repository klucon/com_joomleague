<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$db = $container->get(DatabaseInterface::class);

const HOME_ITEM_ID = 123;
const FOOTBALL_ITEM_IDS = [123, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179];

$json = static fn(array $value): string => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

/** @return int */
$upsertArticle = static function (array $article) use ($db, $json): int {
	$note = 'jldemo-football-' . $article['key'];
	$query = $db->getQuery(true)->select('id')->from($db->quoteName('#__content'))->where($db->quoteName('note') . ' = :note')->bind(':note', $note);
	$id = (int) $db->setQuery($query)->loadResult();
	$now = Factory::getDate()->toSql();
	$row = (object) [
		'id' => $id,
		'asset_id' => 0,
		'title' => $article['title'],
		'alias' => $article['key'],
		'introtext' => $article['intro'],
		'fulltext' => $article['body'],
		'state' => 1,
		'catid' => 2,
		'created' => $id > 0 ? ($article['created'] ?? $now) : ($article['created'] ?? $now),
		'created_by' => 0,
		'created_by_alias' => 'JoomLeague Demo Desk',
		'modified' => $now,
		'modified_by' => 0,
		'checked_out' => null,
		'checked_out_time' => null,
		'publish_up' => $article['created'] ?? $now,
		'publish_down' => null,
		'images' => $json(['image_intro' => $article['image'], 'image_intro_alt' => $article['title'], 'image_fulltext' => $article['image'], 'image_fulltext_alt' => $article['title']]),
		'urls' => '{}',
		'attribs' => $json(['show_title' => 1, 'show_category' => 0, 'show_author' => 1, 'show_create_date' => 1, 'show_modify_date' => 0, 'show_hits' => 0, 'show_navigation' => 1]),
		'version' => 1,
		'ordering' => 0,
		'metakey' => 'JoomLeague, football, demo, ' . $article['key'],
		'metadesc' => $article['description'],
		'access' => 1,
		'hits' => 0,
		'metadata' => $json(['robots' => '', 'author' => 'JoomLeague Demo Desk']),
		'featured' => 0,
		'language' => '*',
		'note' => $note,
	];
	if ($id > 0) {
		$db->updateObject('#__content', $row, 'id');
	} else {
		unset($row->id);
		$db->insertObject('#__content', $row, 'id');
		$id = (int) $row->id;
	}
	return $id;
};

$articles = [
	[
		'key' => 'westmoor-statement-win', 'title' => 'Westmoor begin the run-in with a statement win',
		'image' => 'images/joomleague/demo/editorial/football-news-match.webp', 'created' => '2026-08-28 19:40:00',
		'description' => 'A fictional matchday report demonstrating editorial coverage connected to JoomLeague competition data.',
		'intro' => '<p>Westmoor Falcons turned a measured first half into a decisive 3-1 victory under the lights at Westmoor Arena.</p>',
		'body' => '<h2>A patient performance</h2><p>The Falcons controlled the central areas before opening the scoring shortly before half-time. Riverdale responded after the interval, but two late goals settled a lively fictional demonstration match.</p><h2>Competition context</h2><p>This article shows how normal Joomla editorial content can sit alongside live standings, results, events and player statistics supplied by JoomLeague.</p><blockquote><p>Every club story becomes more useful when readers can move directly from the report to the competition data.</p></blockquote><p>All clubs, people and events on this website are fictional demonstration data.</p>',
	],
	[
		'key' => 'academy-first-team-minutes', 'title' => 'Five academy players earn first-team minutes',
		'image' => 'images/joomleague/demo/editorial/football-news-academy.webp', 'created' => '2026-08-27 16:15:00',
		'description' => 'Fictional club development news used to demonstrate Joomla articles in the football showcase.',
		'intro' => '<p>The latest programme gave five fictional academy players an opportunity to step into senior competition.</p>',
		'body' => '<h2>A route from squad to programme</h2><p>The club used the fixture to rotate its squad and demonstrate how player assignments, line-ups and match events connect across JoomLeague.</p><p>Each appearance can feed player profiles, event rankings and season statistics without duplicating editorial content.</p>',
	],
	[
		'key' => 'inside-westmoor-arena', 'title' => 'Inside Westmoor Arena: preparing the pitch',
		'image' => 'images/joomleague/demo/editorial/football-news-venue.webp', 'created' => '2026-08-26 08:30:00',
		'description' => 'A fictional venue feature demonstrating venue media and programme modules.',
		'intro' => '<p>Ground staff explain the fictional matchday routine behind the league\'s busiest venue.</p>',
		'body' => '<h2>Ready for the programme</h2><p>Venue records provide one source for location, media and scheduled events. The venue programme module below turns that information into a useful public schedule.</p><p>The image and all details are fictional and exist solely to demonstrate JoomLeague.</p>',
	],
	[
		'key' => 'football-data-workflow', 'title' => 'How data shapes the 2035/2036 season',
		'image' => 'templates/tpl_jldemo_football/images/football-matchday.png', 'created' => '2026-08-25 12:00:00',
		'description' => 'A demonstration article describing how JoomLeague links structured sport data and Joomla publishing.',
		'intro' => '<p>From programme planning to the final table, one structured workflow keeps the fictional season connected.</p>',
		'body' => '<h2>One competition, many views</h2><p>Administrators maintain projects, participants, rounds, programme items, line-ups, events and results. JoomLeague then presents the same information through focused pages and modules.</p><h2>Built for more than football</h2><p>Football is the reference showcase, while the underlying sport profiles support many different contest and result structures.</p>',
	],
];

$articleIds = [];
foreach ($articles as $article) {
	$articleIds[$article['key']] = $upsertArticle($article);
}

/** @return int */
$upsertModule = static function (string $key, array $data, array $menuIds = [HOME_ITEM_ID]) use ($db, $json): int {
	$note = 'jldemo-football-' . $key;
	$query = $db->getQuery(true)->select('id')->from($db->quoteName('#__modules'))->where($db->quoteName('note') . ' = :note')->bind(':note', $note);
	$id = (int) $db->setQuery($query)->loadResult();
	$row = (object) [
		'id' => $id,
		'asset_id' => 0,
		'title' => $data['title'],
		'note' => $note,
		'content' => $data['content'] ?? '',
		'ordering' => $data['ordering'] ?? 0,
		'position' => $data['position'],
		'checked_out' => null,
		'checked_out_time' => null,
		'publish_up' => null,
		'publish_down' => null,
		'published' => 1,
		'module' => $data['module'] ?? 'mod_custom',
		'access' => 1,
		'showtitle' => $data['showtitle'] ?? 0,
		'params' => $json($data['params'] ?? ['prepare_content' => 1, 'backgroundimage' => '', 'layout' => '_:default', 'moduleclass_sfx' => '', 'cache' => 0, 'cache_time' => 900, 'cachemode' => 'static']),
		'client_id' => 0,
		'language' => '*',
	];
	if ($id > 0) {
		$db->updateObject('#__modules', $row, 'id');
	} else {
		unset($row->id);
		$db->insertObject('#__modules', $row, 'id');
		$id = (int) $row->id;
	}
	$query = $db->getQuery(true)->delete($db->quoteName('#__modules_menu'))->where($db->quoteName('moduleid') . ' = :id')->bind(':id', $id);
	$db->setQuery($query)->execute();
	foreach (array_values(array_unique($menuIds)) as $menuId) {
		$assignment = (object) ['moduleid' => $id, 'menuid' => $menuId];
		$db->insertObject('#__modules_menu', $assignment);
	}
	return $id;
};

$articleUrl = static fn(string $key): string => 'index.php?option=com_content&amp;view=article&amp;id=' . $articleIds[$key] . '&amp;Itemid=' . HOME_ITEM_ID;
$news = [
	['key' => 'westmoor-statement-win', 'tag' => 'Match report', 'class' => 'jl-news-card jl-news-card--lead', 'image' => '/images/joomleague/demo/editorial/football-news-match.webp'],
	['key' => 'academy-first-team-minutes', 'tag' => 'Academy', 'class' => 'jl-news-card', 'image' => '/images/joomleague/demo/editorial/football-news-academy.webp'],
	['key' => 'inside-westmoor-arena', 'tag' => 'Behind the scenes', 'class' => 'jl-news-card', 'image' => '/images/joomleague/demo/editorial/football-news-venue.webp'],
];
foreach ($news as $index => $card) {
	$article = $articles[array_search($card['key'], array_column($articles, 'key'), true)];
	$content = '<article class="' . $card['class'] . '" style="--news-image:url(\'' . $card['image'] . '\')"><div><span class="jl-story-tag">' . $card['tag'] . '</span><h3><a href="' . $articleUrl($card['key']) . '">' . $article['title'] . '</a></h3><p>' . strip_tags($article['intro']) . '</p></div></article>';
	$upsertModule('news-' . ($index + 1), ['title' => $article['title'], 'position' => 'news-lead', 'ordering' => $index + 1, 'content' => $content]);
}

$upsertModule('utility', ['title' => 'Demo utility links', 'position' => 'utility-links', 'content' => '<nav aria-label="Demo links"><a href="index.php?option=com_joomleague&amp;view=projects">All competitions</a><a href="https://joomleague.eu" target="_blank" rel="noopener">JoomLeague.eu</a></nav>'], FOOTBALL_ITEM_IDS);
$upsertModule('search', ['title' => 'Search', 'module' => 'mod_finder', 'position' => 'search', 'params' => ['searchfilter' => '', 'show_autosuggest' => 1, 'show_advanced' => 0, 'show_label' => 0, 'alt_label' => 'Search the demo', 'show_button' => 0, 'opensearch' => 0, 'layout' => '_:default', 'moduleclass_sfx' => '', 'cache' => 0]], FOOTBALL_ITEM_IDS);
$upsertModule('mail', ['title' => 'The Matchday Brief', 'position' => 'mail', 'content' => '<div class="jl-mail-cta"><div><span class="jl-section-kicker">From the demo newsroom</span><h2>The Matchday Brief</h2><p>See how a club can turn structured competition data into a polished weekly email. This demonstration form stores no personal data.</p></div><form class="jl-mail-form" data-demo-mail><label for="jl-demo-email">Email address</label><div><input id="jl-demo-email" type="email" placeholder="name@example.com" required><button type="submit">Preview subscription</button></div><p class="jl-mail-message" role="status" aria-live="polite"></p></form></div>']);
$upsertModule('discover-data', ['title' => 'Connected publishing', 'position' => 'discover', 'ordering' => 1, 'content' => '<article class="jl-discover-card"><span class="jl-story-tag">Editorial workflow</span><h3>From one result to every public view</h3><p>Programme, tables, rankings and reports stay connected while Joomla articles add the story around the data.</p><a href="' . $articleUrl('football-data-workflow') . '">Explore the workflow <span aria-hidden="true">→</span></a></article>']);
$upsertModule('discover-calendar', ['title' => 'Calendar feeds', 'position' => 'discover', 'ordering' => 3, 'content' => '<article class="jl-discover-card"><span class="jl-story-tag">Take it with you</span><h3>Subscribe to the programme</h3><p>Team and competition calendar feeds keep upcoming events available beyond the website.</p><a href="index.php?option=com_joomleague&amp;view=results&amp;project_id=1&amp;Itemid=171">Open the programme <span aria-hidden="true">→</span></a></article>']);
$upsertModule('footer-about', ['title' => 'About this demo', 'position' => 'footer-a', 'showtitle' => 1, 'content' => '<p>A fictional football publication powered by Joomla and JoomLeague 6.2. Every club, person and result exists only for demonstration.</p>'], FOOTBALL_ITEM_IDS);
$upsertModule('footer-competition', ['title' => 'Competition', 'position' => 'footer-b', 'showtitle' => 1, 'content' => '<ul><li><a href="index.php?option=com_joomleague&amp;view=standings&amp;project_id=1&amp;Itemid=170">Standings</a></li><li><a href="index.php?option=com_joomleague&amp;view=results&amp;project_id=1&amp;Itemid=171">Results</a></li><li><a href="index.php?option=com_joomleague&amp;view=participants&amp;project_id=1&amp;Itemid=172">Teams</a></li></ul>'], FOOTBALL_ITEM_IDS);
$upsertModule('footer-resources', ['title' => 'JoomLeague', 'position' => 'footer-c', 'showtitle' => 1, 'content' => '<ul><li><a href="https://joomleague.eu" target="_blank" rel="noopener">Project website</a></li><li><a href="https://downloads.joomleague.eu" target="_blank" rel="noopener">Downloads</a></li><li><a href="index.php?option=com_finder&amp;view=search&amp;Itemid=123">Search this demo</a></li></ul>'], FOOTBALL_ITEM_IDS);

$updateModule = static function (int $id, string $title, string $position, array $params, int $ordering = 0, array $menuIds = [HOME_ITEM_ID]) use ($db, $json): void {
	$row = (object) ['id' => $id, 'title' => $title, 'position' => $position, 'published' => 1, 'showtitle' => 1, 'ordering' => $ordering, 'params' => $json($params)];
	$db->updateObject('#__modules', $row, 'id');
	$query = $db->getQuery(true)->delete($db->quoteName('#__modules_menu'))->where($db->quoteName('moduleid') . ' = :id')->bind(':id', $id);
	$db->setQuery($query)->execute();
	foreach (array_values(array_unique($menuIds)) as $menuId) {
		$assignment = (object) ['moduleid' => $id, 'menuid' => $menuId];
		$db->insertObject('#__modules_menu', $assignment);
	}
};
$base = ['layout' => '_:default', 'moduleclass_sfx' => '', 'cache' => 0, 'cache_time' => 900, 'cachemode' => 'id'];
$updateModule(129, 'Next at Westmoor', 'hero-event', ['project_id' => 1, 'scope' => 'project', 'entry_id' => 0, 'club_id' => 0, 'show_project_name' => 0, 'show_round' => 1, 'show_venue' => 1, 'show_calendar' => 1] + $base);
$updateModule(130, 'Competition navigation', 'competition-nav', ['project_id' => 1, 'navigation_style' => 'pills', 'show_project_name' => 0, 'show_overview' => 1] + $base, 0, FOOTBALL_ITEM_IDS);
$updateModule(110, 'League table', 'league-main', ['project_id' => 1, 'stage_id' => 1, 'highlight_entry_id' => 1, 'highlight_style' => 'row', 'highlight_color_row' => '#d9f99d', 'highlight_color_text' => '#172016', 'highlight_bold' => 1, 'highlight_italic' => 0, 'highlight_underline' => 0, 'limit' => 12, 'show_project_name' => 0] + $base);
$updateModule(111, 'Upcoming programme', 'league-side', ['project_id' => 1, 'scope' => 'project', 'entry_id' => 0, 'club_id' => 0, 'mode' => 'upcoming', 'limit' => 5, 'show_project_name' => 0, 'show_round' => 1, 'show_date' => 1, 'show_venue' => 1, 'show_result' => 0, 'show_calendar' => 1] + $base);
$updateModule(112, 'Leading performers', 'rankings', ['project_id' => 1, 'statistic_code' => '', 'limit' => 5, 'show_project_name' => 0] + $base, 1);
$updateModule(113, 'Event leaders', 'rankings', ['project_id' => 1, 'event_code' => '', 'limit' => 5, 'show_project_name' => 0] + $base, 2);
$updateModule(131, 'Featured team', 'club-showcase', ['project_id' => 1, 'entry_id' => 1, 'show_image' => 1, 'show_project_name' => 0, 'show_club' => 1, 'show_member_count' => 1, 'show_actions' => 1] + $base, 1);
$updateModule(132, 'Westmoor Football Club', 'club-showcase', ['club_id' => 1, 'show_logo' => 1, 'show_teams' => 1, 'show_events' => 1, 'event_limit' => 3] + $base, 2);
$updateModule(133, 'Coaching and officials', 'club-showcase', ['project_id' => 1, 'group' => 'all', 'limit' => 8, 'show_project_name' => 0, 'show_images' => 1, 'show_counts' => 1] + $base, 3);
$updateModule(134, 'At Westmoor Arena', 'venue-programme', ['venue_id' => 1, 'mode' => 'all', 'limit' => 5, 'show_venue' => 1, 'show_image' => 1, 'show_address' => 1, 'show_context' => 1] + $base);
$updateModule(135, 'Explore competitions', 'discover', ['sport_type_id' => 0, 'season_id' => 0, 'organisation' => '', 'limit' => 6, 'show_sport' => 1, 'show_season' => 1, 'show_organisation' => 1, 'show_counts' => 1] + $base, 2);

// The legacy football menu module is replaced by the project-aware navigation module.
$legacyMenu = (object) ['id' => 114, 'published' => 0, 'position' => ''];
$db->updateObject('#__modules', $legacyMenu, 'id');

echo 'Football showcase configured: ' . count($articleIds) . " articles, all 11 JoomLeague modules, Smart Search and editorial sections.\n";
