<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


/**
 * Sdílený layout detailu zápasu – používá ho frontend view (matchreport) i
 * content plugin ({jlmatch id=X}). Přepisovatelný přes:
 *   templates/<sablona>/html/layouts/joomleague/match/detail.php
 *
 * @var array $displayData  ['match' => object, 'events' => array, 'referees' => array,
 *                           'roster' => array, 'staff' => array, 'statistics' => array,
 *                           'favTeamIds' => int[], 'options' => array]
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Site\Service\PersonNameHelper;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$match     = $displayData['match'] ?? null;
$events    = $displayData['events'] ?? [];
$referees  = $displayData['referees'] ?? [];
$roster    = $displayData['roster'] ?? [];
$staffList = $displayData['staff'] ?? [];
$statistics = $displayData['statistics'] ?? [];
$favTeamIds = $displayData['favTeamIds'] ?? [];
$options   = $displayData['options'] ?? [];

// Sekce dostupné z jednoduché i pokročilé sady polí (kryjí se) – obojí musí platit zároveň.
$showSummary  = $options['summary'] ?? true;
$showEvents   = $options['events'] ?? true;
$showMeta     = ($options['meta'] ?? true) && ($options['details'] ?? true);
$showSplit    = $options['split'] ?? true;
$showPreview  = $options['preview'] ?? true;
$showReferees = ($options['referees'] ?? true) && ($options['matchReferees'] ?? true);
$refereeNameFormat = (string) ($options['refereeNameFormat'] ?? '3');
$showLink     = $options['link'] ?? false;
$headingTag   = \in_array($options['heading'] ?? 'h2', ['h1', 'h2', 'h3'], true) ? $options['heading'] : 'h2';
$escape       = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$translateLegacyName = static function ($value): string {
	$value = trim((string) $value);

	return preg_match('/^(COM|JLM)_[A-Z0-9_-]+$/', $value) ? Text::_($value) : $value;
};

$showSectionHeader   = $options['sectionHeader'] ?? true;
$showResult          = $options['result'] ?? true;
$showExtended        = $options['extended'] ?? true;
$showTimeline        = $options['timeline'] ?? false;
$showOvertimeResult  = $options['overtimeResult'] ?? true;
$showShotoutResult   = $options['shotoutResult'] ?? true;

$showMatchDate       = $options['matchDate'] ?? true;
$showMatchTime       = $options['matchTime'] ?? true;
$showTimeSuffix      = $options['timeSuffix'] ?? true;
$showMatchNumber     = $options['matchNumber'] ?? false;
$showMatchPlayground = $options['matchPlayground'] ?? true;
$showMatchCrowd      = $options['matchCrowd'] ?? true;
$showRefereePosition = $options['refereePosition'] ?? false;

$showEventMinute    = $options['eventMinute'] ?? true;
$showEventSum       = $options['eventSum'] ?? false;
$showEventNotice    = $options['eventNotice'] ?? true;
$eventLinkPlayer    = $options['eventLinkPlayer'] ?? true;
$showEventTeamName  = $options['eventTeamName'] ?? true;
$sortEventsDesc     = $options['sortEventsDesc'] ?? false;

$teamNameField  = $options['teamNameField'] ?? 'name';
$showTeamLogo   = $options['showTeamLogo'] ?? true;
$picture        = $options['picture'] ?? 'logo_big';
$pictureWidth   = (int) ($options['pictureWidth'] ?? 150);
$pictureHeight  = (int) ($options['pictureHeight'] ?? 0);

$showRoster         = $options['roster'] ?? true;
$showSubstitutions  = $options['substitutions'] ?? true;
$showStats          = $options['stats'] ?? true;
$playerNameFormat   = (string) ($options['playerNameFormat'] ?? '3');
$playerLinkMode     = (string) ($options['playerProfileLink'] ?? '1');

$styleClass1 = trim((string) ($options['styleClass1'] ?? ''));
$styleClass2 = trim((string) ($options['styleClass2'] ?? ''));
$rowClass = static function (int $index) use ($styleClass1, $styleClass2): string {
	$class = $index % 2 === 0 ? $styleClass1 : $styleClass2;

	return $class !== '' ? ' ' . $class : '';
};

if (!$match) {
	echo '<div class="alert alert-warning">' . Text::_('COM_JOOMLEAGUE_SITE_MATCH_NOT_FOUND') . '</div>';

	return;
}

$isPlayerLinked = static function (int $projectTeamId) use ($playerLinkMode, $favTeamIds): bool {
	return match ($playerLinkMode) {
		'0' => false,
		'2' => in_array($projectTeamId, $favTeamIds, true),
		default => true,
	};
};

$resolveImageUrl = static function (string $path): string {
	if ($path === '') {
		return '';
	}

	return preg_match('#^https?://#i', $path) ? $path : Uri::root(true) . '/' . ltrim($path, '/');
};
$resolveSchemaImageUrl = static fn (string $path): ?string => StructuredDataHelper::imageUrl($path);

$teamName = static function (string $side) use ($match, $teamNameField): string {
	$field = match ($teamNameField) {
		'short_name' => $side . '_team_short_name',
		'middle_name' => $side . '_team_middle_name',
		default => $side . '_name',
	};
	$value = trim((string) ($match->{$field} ?? ''));

	return $value !== '' ? $value : trim((string) ($match->{$side . '_name'} ?? ''));
};

// Když vybraná velikost/typ obrázku chybí, zkusí se ostatní velikosti loga klubu, pak
// obrázek týmu, a nakonec centrální placeholder (stejný fallback jako u club/team view).
$clubLogoPlaceholder = trim((string) ComponentHelper::getParams('com_joomleague')->get('placeholder_club_logo', ''));

$teamLogo = static function (string $side) use ($match, $picture, $clubLogoPlaceholder): string {
	$primary = trim((string) match ($picture) {
		'team_picture' => $match->{$side . '_team_picture'} ?? '',
		'projectteam_picture' => $match->{$side . '_projectteam_picture'} ?? '',
		'logo_small' => $match->{$side . '_club_logo_small'} ?? '',
		'logo_middle' => $match->{$side . '_club_logo_middle'} ?? '',
		default => $match->{$side . '_club_logo_big'} ?? '',
	});

	if ($primary !== '') {
		return $primary;
	}

	foreach ([$side . '_club_logo_big', $side . '_club_logo_middle', $side . '_club_logo_small', $side . '_team_picture', $side . '_projectteam_picture'] as $field) {
		$value = trim((string) ($match->{$field} ?? ''));

		if ($value !== '') {
			return $value;
		}
	}

	return $clubLogoPlaceholder;
};

$sportName = $translateLegacyName($match->sport_name ?? '');
$formatScoreValue = static function ($value): string {
	if ($value === null || $value === '') {
		return '';
	}

	$number = (float) $value;

	return fmod($number, 1.0) === 0.0 ? (string) (int) $number : rtrim(rtrim((string) $number, '0'), '.');
};
$normalizeUrl = static function ($value): ?string {
	$value = trim((string) $value);

	if ($value === '') {
		return null;
	}

	return preg_match('#^https?://#i', $value) ? $value : 'https://' . ltrim($value, '/');
};
$normalizeCountryCode = static function ($value): string {
	$value = strtoupper(trim((string) $value));

	return preg_match('/^[A-Z]{2}$/', $value) ? $value : '';
};
$countryName = static function (string $countryCode): string {
	$key = 'COM_JOOMLEAGUE_COUNTRY_' . $countryCode;
	$name = Text::_($key);

	return $name !== $key ? $name : $countryCode;
};

// Strukturovaná data pro vyhledávače (jednou za zápas na stránce).
if (class_exists(StructuredDataHelper::class)) {
	$languageTag = Factory::getApplication()->getLanguage()->getTag();
	$matchUrl = StructuredDataHelper::absoluteUrl(Route::_(
		'index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id,
		false
	));
	$homeTeamUrl = (int) ($match->home_projectteam_id ?? 0) > 0
		? StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $match->home_projectteam_id, false))
		: null;
	$awayTeamUrl = (int) ($match->away_projectteam_id ?? 0) > 0
		? StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $match->away_projectteam_id, false))
		: null;
	$projectUrl = (int) ($match->project_id ?? 0) > 0
		? StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $match->project_id, false))
		: null;
	$currentItemId = Factory::getApplication()->getInput()->getInt('Itemid', 0);
	$playgroundRoute = 'index.php?option=com_joomleague&view=playground&id=' . (int) ($match->playground_id ?? 0);
	$playgroundRoute .= $currentItemId > 0 ? '&Itemid=' . $currentItemId : '';
	$playgroundUrl = (int) ($match->playground_id ?? 0) > 0
		? StructuredDataHelper::absoluteUrl(Route::_($playgroundRoute, false))
		: null;
	$homeClubWebsite = $normalizeUrl($match->home_club_website ?? '');
	$awayClubWebsite = $normalizeUrl($match->away_club_website ?? '');
	$schemaTeamLogo = static function (string $side) use ($match, $picture, $resolveSchemaImageUrl): ?string {
		$primary = trim((string) match ($picture) {
			'team_picture' => $match->{$side . '_team_picture'} ?? '',
			'projectteam_picture' => $match->{$side . '_projectteam_picture'} ?? '',
			'logo_small' => $match->{$side . '_club_logo_small'} ?? '',
			'logo_middle' => $match->{$side . '_club_logo_middle'} ?? '',
			default => $match->{$side . '_club_logo_big'} ?? '',
		});

		if ($primary !== '') {
			return $resolveSchemaImageUrl($primary);
		}

		foreach ([$side . '_club_logo_big', $side . '_club_logo_middle', $side . '_club_logo_small', $side . '_team_picture', $side . '_projectteam_picture'] as $field) {
			$value = trim((string) ($match->{$field} ?? ''));

			if ($value !== '') {
				return $resolveSchemaImageUrl($value);
			}
		}

		return null;
	};
	$homeLogo = $schemaTeamLogo('home');
	$awayLogo = $schemaTeamLogo('away');
	$homeScore = $formatScoreValue($match->team1_result ?? null);
	$awayScore = $formatScoreValue($match->team2_result ?? null);
	$resultText = ($homeScore !== '' && $awayScore !== '')
		? trim((string) ($match->home_name ?? '') . ' ' . $homeScore . ':' . $awayScore . ' ' . (string) ($match->away_name ?? ''))
		: '';
	$competitionName = trim((string) ($match->project_name ?? '') . (!empty($match->season_name) ? ' · ' . (string) $match->season_name : ''));
	$descriptionParts = array_filter([
		$resultText,
		$competitionName,
		!empty($match->round_name) ? (string) $match->round_name : null,
	], static fn ($value): bool => trim((string) $value) !== '');
	$personSchema = static function (object $person, ?string $jobTitle = null) use ($match, $resolveSchemaImageUrl, $normalizeCountryCode, $countryName): ?array {
		$personId = (int) ($person->person_id ?? 0);
		$name = trim((string) ($person->person_name ?? ''));

		if ($name === '') {
			$name = trim((string) trim((string) ($person->firstname ?? '') . ' ' . (string) ($person->lastname ?? '')));
		}

		if ($name === '') {
			return null;
		}

		$personUrl = $personId > 0
			? StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=person&id=' . $personId . '&project_id=' . (int) $match->project_id, false))
			: null;
		$image = trim((string) (($person->person_teampicture ?? '') ?: ($person->person_picture ?? '')));
		$countryCode = $normalizeCountryCode($person->person_country ?? '');

		return [
			'@type'       => 'Person',
			'@id'         => $personUrl ? $personUrl . '#person' : null,
			'identifier'  => $personId > 0 ? [
				'@type'      => 'PropertyValue',
				'propertyID' => 'JoomLeague person ID',
				'value'      => (string) $personId,
			] : null,
			'name'        => $name,
			'url'         => $personUrl,
			'image'       => $image !== '' ? $resolveSchemaImageUrl($image) : null,
			'nationality' => $countryCode !== '' ? [
				'@type'      => 'Country',
				'identifier' => $countryCode,
				'name'       => $countryName($countryCode),
			] : null,
			'jobTitle'    => $jobTitle,
		];
	};
	$athletesBySide = ['home' => [], 'away' => []];
	$coachesBySide = ['home' => [], 'away' => []];
	$seenAthletes = ['home' => [], 'away' => []];
	$seenCoaches = ['home' => [], 'away' => []];

	foreach ($roster as $entry) {
		$side = (int) ($entry->projectteam_id ?? 0) === (int) ($match->home_projectteam_id ?? 0) ? 'home' : 'away';
		$personKey = (int) ($entry->person_id ?? 0) > 0 ? 'id:' . (int) $entry->person_id : 'name:' . md5((string) ($entry->person_name ?? ''));

		if (isset($seenAthletes[$side][$personKey])) {
			continue;
		}

		$seenAthletes[$side][$personKey] = true;
		$position = $translateLegacyName($entry->position_name ?? '');
		$schema = $personSchema($entry, $position !== '' ? $position : null);

		if ($schema !== null) {
			$athletesBySide[$side][] = $schema;
		}
	}

	foreach ($staffList as $entry) {
		$side = (int) ($entry->projectteam_id ?? 0) === (int) ($match->home_projectteam_id ?? 0) ? 'home' : 'away';
		$positionRaw = (string) ($entry->position_name ?? '');
		$position = $translateLegacyName($positionRaw);
		$isCoach = $positionRaw === 'COM_JOOMLEAGUE_F_HEAD_COACH'
			|| stripos($positionRaw, 'COACH') !== false
			|| stripos($position, 'tren') !== false
			|| stripos($position, 'coach') !== false;

		if (!$isCoach) {
			continue;
		}

		$personKey = (int) ($entry->person_id ?? 0) > 0 ? 'id:' . (int) $entry->person_id : 'name:' . md5((string) ($entry->person_name ?? ''));

		if (isset($seenCoaches[$side][$personKey])) {
			continue;
		}

		$seenCoaches[$side][$personKey] = true;
		$schema = $personSchema($entry, $position !== '' ? $position : null);

		if ($schema !== null) {
			$coachesBySide[$side][] = $schema;
		}
	}
	$homeTeamSchema = !empty($match->home_name) ? [
		'@type' => 'SportsTeam',
		'@id'   => $homeTeamUrl ? $homeTeamUrl . '#sportsteam' : null,
		'name'  => (string) $match->home_name,
		'url'   => $homeTeamUrl,
		'logo'  => $homeLogo,
		'sameAs' => $homeClubWebsite,
		'sport' => $sportName !== '' ? $sportName : null,
		'athlete' => $athletesBySide['home'],
		'coach'   => $coachesBySide['home'],
	] : null;
	$awayTeamSchema = !empty($match->away_name) ? [
		'@type' => 'SportsTeam',
		'@id'   => $awayTeamUrl ? $awayTeamUrl . '#sportsteam' : null,
		'name'  => (string) $match->away_name,
		'url'   => $awayTeamUrl,
		'logo'  => $awayLogo,
		'sameAs' => $awayClubWebsite,
		'sport' => $sportName !== '' ? $sportName : null,
		'athlete' => $athletesBySide['away'],
		'coach'   => $coachesBySide['away'],
	] : null;
	$refereeSchemas = [];

	foreach ($referees as $referee) {
		$refereeName = trim((string) (($referee->person_name ?? '') ?: ($referee->external_referee_name ?? '')));

		if ($refereeName === '') {
			continue;
		}

		$refereeUrl = (int) ($referee->person_id ?? 0) > 0
			? StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $referee->person_id . '&project_id=' . (int) $match->project_id, false))
			: null;
		$refereePosition = $translateLegacyName($referee->position_name ?? '');
		$refereeSchemas[] = [
			'@type'    => 'Person',
			'@id'      => $refereeUrl ? $refereeUrl . '#person' : null,
			'name'     => $refereeName,
			'url'      => $refereeUrl,
			'jobTitle' => $refereePosition !== '' ? $refereePosition : null,
		];
	}
	$keywords = array_values(array_unique(array_filter([
		$sportName,
		$competitionName,
		(string) ($match->round_name ?? ''),
		(string) ($match->home_name ?? ''),
		(string) ($match->away_name ?? ''),
	], static fn ($value): bool => trim((string) $value) !== '')));

	StructuredDataHelper::add(Factory::getApplication()->getDocument(), [
		'@context'    => 'https://schema.org',
		'@type'       => 'SportsEvent',
		'@id'         => $matchUrl ? $matchUrl . '#sportsevent' : null,
		'identifier'  => [
			'@type'      => 'PropertyValue',
			'propertyID' => 'JoomLeague match ID',
			'value'      => (string) (int) $match->id,
		],
		'name'        => trim((string) ($match->home_name ?? '') . ' - ' . (string) ($match->away_name ?? '')),
		'description' => $descriptionParts !== [] ? implode(', ', $descriptionParts) : null,
		'keywords'    => $keywords,
		'inLanguage'  => $languageTag,
		'startDate'   => !empty($match->match_date) ? date('c', strtotime((string) $match->match_date)) : null,
		'eventStatus' => !empty($match->cancel)
			? 'https://schema.org/EventCancelled'
			: 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		'sport'       => $sportName !== '' ? $sportName : null,
		'image'       => array_values(array_unique(array_filter([$homeLogo, $awayLogo]))),
		'mainEntityOfPage' => $matchUrl ? [
			'@type' => 'WebPage',
			'@id'   => $matchUrl,
			'url'   => $matchUrl,
			'name'  => trim((string) ($match->home_name ?? '') . ' - ' . (string) ($match->away_name ?? '')),
		] : null,
		'location'    => !empty($match->playground_name) ? [
			'@type' => 'SportsActivityLocation',
			'name'  => (string) $match->playground_name,
			'url'   => $playgroundUrl,
			'maximumAttendeeCapacity' => (int) ($match->playground_max_visitors ?? 0) > 0 ? (int) $match->playground_max_visitors : null,
			'address' => trim((string) ($match->playground_address ?? '') . (string) ($match->playground_city ?? '') . (string) ($match->playground_zipcode ?? '') . (string) ($match->playground_country ?? '')) !== '' ? [
				'@type'           => 'PostalAddress',
				'streetAddress'   => trim((string) ($match->playground_address ?? '')),
				'postalCode'      => trim((string) ($match->playground_zipcode ?? '')),
				'addressLocality' => trim((string) ($match->playground_city ?? '')),
				'addressCountry'  => $normalizeCountryCode($match->playground_country ?? '') ?: null,
			] : null,
			'geo' => is_numeric($match->playground_latitude ?? null) && is_numeric($match->playground_longitude ?? null) ? [
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $match->playground_latitude,
				'longitude' => (float) $match->playground_longitude,
			] : null,
		] : null,
		'homeTeam'    => $homeTeamSchema,
		'awayTeam'    => $awayTeamSchema,
		'referee'     => $refereeSchemas,
		'superEvent'  => $competitionName !== '' ? [
			'@type' => ['SportsEvent', 'EventSeries'],
			'@id'   => $projectUrl ? $projectUrl . '#sportsevent' : null,
			'name'  => $competitionName,
			'url'   => $projectUrl,
			'sport' => $sportName !== '' ? $sportName : null,
		] : null,
		'url'         => $matchUrl,
	]);
}

$splitHome = ($match->team1_result_split ?? '') !== '' ? explode(';', (string) $match->team1_result_split) : [];
$splitAway = ($match->team2_result_split ?? '') !== '' ? explode(';', (string) $match->team2_result_split) : [];
$splitParts = [];

for ($i = 0, $n = max(\count($splitHome), \count($splitAway)); $i < $n; $i++) {
	$splitParts[] = ($splitHome[$i] ?? '-') . ':' . ($splitAway[$i] ?? '-');
}
$splitParts = array_filter($splitParts, static fn (string $p): bool => trim($p, " :-\t") !== '');

$sortedEvents = $events;

usort($sortedEvents, static function (object $a, object $b): int {
	$minuteA = is_numeric($a->event_time ?? null) ? (float) $a->event_time : PHP_FLOAT_MAX;
	$minuteB = is_numeric($b->event_time ?? null) ? (float) $b->event_time : PHP_FLOAT_MAX;
	$minuteCompare = $minuteA <=> $minuteB;

	return $minuteCompare !== 0 ? $minuteCompare : ((int) ($a->id ?? 0) <=> (int) ($b->id ?? 0));
});

if ($sortEventsDesc) {
	$sortedEvents = array_reverse($sortedEvents);
}

$goalEventNames = [
	'COM_JOOMLEAGUE_E_GOAL' => 'team',
	'COM_JOOMLEAGUE_E_PENALTY_GOAL' => 'team',
	'COM_JOOMLEAGUE_E_OWN_GOAL' => 'opponent',
];
$eventScores = [];
$homeGoals = 0;
$awayGoals = 0;
$eventsForScore = $sortedEvents;

usort($eventsForScore, static function (object $a, object $b): int {
	$minuteA = is_numeric($a->event_time ?? null) ? (float) $a->event_time : PHP_FLOAT_MAX;
	$minuteB = is_numeric($b->event_time ?? null) ? (float) $b->event_time : PHP_FLOAT_MAX;
	$minuteCompare = $minuteA <=> $minuteB;

	return $minuteCompare !== 0 ? $minuteCompare : ((int) ($a->id ?? 0) <=> (int) ($b->id ?? 0));
});

foreach ($eventsForScore as $event) {
	$goalMode = $goalEventNames[(string) ($event->event_name ?? '')] ?? null;

	if ($goalMode === null) {
		continue;
	}

	$goals = max(1, (int) round((float) ($event->event_sum ?? 1)));
	$teamId = (int) ($event->projectteam_id ?? 0);
	$isHomeTeam = $teamId > 0 && $teamId === (int) ($match->home_projectteam_id ?? 0);
	$isAwayTeam = $teamId > 0 && $teamId === (int) ($match->away_projectteam_id ?? 0);

	if (($goalMode === 'team' && $isHomeTeam) || ($goalMode === 'opponent' && $isAwayTeam)) {
		$homeGoals += $goals;
	} elseif (($goalMode === 'team' && $isAwayTeam) || ($goalMode === 'opponent' && $isHomeTeam)) {
		$awayGoals += $goals;
	} else {
		continue;
	}

	$eventScores[(int) ($event->id ?? 0)] = $homeGoals . ':' . $awayGoals;
}

$eventPersonName = static function (object $event): string {
	$name = trim((string) ($event->person_name ?? ''));

	return $name !== '' ? $name : trim((string) ($event->external_person_name ?? ''));
};

$footballPositionOrder = array_flip([
	'COM_JOOMLEAGUE_P_FOOTBALL_GOALKEEPER',
	'COM_JOOMLEAGUE_P_GOALKEEPER',
	'COM_JOOMLEAGUE_P_FOOTBALL_RIGHT_BACK',
	'COM_JOOMLEAGUE_P_FOOTBALL_CENTRE_BACK',
	'COM_JOOMLEAGUE_P_FOOTBALL_LEFT_BACK',
	'COM_JOOMLEAGUE_P_FOOTBALL_RIGHT_WING_BACK',
	'COM_JOOMLEAGUE_P_FOOTBALL_LEFT_WING_BACK',
	'COM_JOOMLEAGUE_P_DEFENDER',
	'COM_JOOMLEAGUE_P_FOOTBALL_DEFENSIVE_MIDFIELDER',
	'COM_JOOMLEAGUE_P_FOOTBALL_CENTRE_MIDFIELDER',
	'COM_JOOMLEAGUE_P_FOOTBALL_ATTACKING_MIDFIELDER',
	'COM_JOOMLEAGUE_P_FOOTBALL_RIGHT_MIDFIELDER',
	'COM_JOOMLEAGUE_P_FOOTBALL_LEFT_MIDFIELDER',
	'COM_JOOMLEAGUE_P_MIDFIELDER',
	'COM_JOOMLEAGUE_P_FOOTBALL_RIGHT_WINGER',
	'COM_JOOMLEAGUE_P_FOOTBALL_LEFT_WINGER',
	'COM_JOOMLEAGUE_P_FOOTBALL_SECOND_STRIKER',
	'COM_JOOMLEAGUE_P_FOOTBALL_STRIKER',
	'COM_JOOMLEAGUE_P_FORWARD',
]);
$rosterSort = static function (object $a, object $b) use ($footballPositionOrder): int {
	$positionA = $footballPositionOrder[(string) ($a->position_name ?? '')] ?? 999;
	$positionB = $footballPositionOrder[(string) ($b->position_name ?? '')] ?? 999;
	$compare = $positionA <=> $positionB;

	if ($compare !== 0) {
		return $compare;
	}

	$substituteA = (int) ($a->is_substitute ?? $a->came_in ?? 0);
	$substituteB = (int) ($b->is_substitute ?? $b->came_in ?? 0);
	$compare = $substituteA <=> $substituteB;

	if ($compare !== 0) {
		return $compare;
	}

	return ((int) ($a->ordering ?? 0) <=> (int) ($b->ordering ?? 0)) ?: ((int) ($a->id ?? 0) <=> (int) ($b->id ?? 0));
};

// Sestava/realizační tým – rozdělené podle strany (domácí/hosté).
$rosterBySide = ['home' => [], 'away' => []];

foreach ($roster as $entry) {
	$side = (int) $entry->projectteam_id === (int) $match->home_projectteam_id ? 'home' : 'away';
	$rosterBySide[$side][] = $entry;
}

usort($rosterBySide['home'], $rosterSort);
usort($rosterBySide['away'], $rosterSort);

$staffBySide = ['home' => [], 'away' => []];

foreach ($staffList as $entry) {
	$side = (int) $entry->projectteam_id === (int) $match->home_projectteam_id ? 'home' : 'away';
	$staffBySide[$side][] = $entry;
}

$footballLine = static function (string $position): int {
	return match ($position) {
		'COM_JOOMLEAGUE_P_FOOTBALL_GOALKEEPER', 'COM_JOOMLEAGUE_P_GOALKEEPER' => 0,
		'COM_JOOMLEAGUE_P_FOOTBALL_RIGHT_BACK',
		'COM_JOOMLEAGUE_P_FOOTBALL_CENTRE_BACK',
		'COM_JOOMLEAGUE_P_FOOTBALL_LEFT_BACK',
		'COM_JOOMLEAGUE_P_FOOTBALL_RIGHT_WING_BACK',
		'COM_JOOMLEAGUE_P_FOOTBALL_LEFT_WING_BACK',
		'COM_JOOMLEAGUE_P_DEFENDER' => 1,
		'COM_JOOMLEAGUE_P_FOOTBALL_DEFENSIVE_MIDFIELDER',
		'COM_JOOMLEAGUE_P_FOOTBALL_CENTRE_MIDFIELDER',
		'COM_JOOMLEAGUE_P_FOOTBALL_ATTACKING_MIDFIELDER',
		'COM_JOOMLEAGUE_P_FOOTBALL_RIGHT_MIDFIELDER',
		'COM_JOOMLEAGUE_P_FOOTBALL_LEFT_MIDFIELDER',
		'COM_JOOMLEAGUE_P_MIDFIELDER' => 2,
		'COM_JOOMLEAGUE_P_FOOTBALL_RIGHT_WINGER',
		'COM_JOOMLEAGUE_P_FOOTBALL_LEFT_WINGER',
		'COM_JOOMLEAGUE_P_FOOTBALL_SECOND_STRIKER',
		'COM_JOOMLEAGUE_P_FOOTBALL_STRIKER',
		'COM_JOOMLEAGUE_P_FORWARD' => 3,
		default => 4,
	};
};
$playerNameHtml = static function (object $player) use ($escape, $isPlayerLinked, $playerNameFormat): string {
	$name = PersonNameHelper::format((string) $player->firstname, (string) $player->lastname, (string) $player->nickname, $playerNameFormat);

	if ($isPlayerLinked((int) $player->projectteam_id) && !empty($player->person_id)) {
		return '<a href="' . $escape(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $player->person_id)) . '">' . $escape($name) . '</a>';
	}

	return $escape($name);
};
$staffName = static function (object $staffMember) use ($escape, $playerNameFormat): string {
	$name = PersonNameHelper::format((string) $staffMember->firstname, (string) $staffMember->lastname, (string) $staffMember->nickname, $playerNameFormat);

	if (!empty($staffMember->person_id)) {
		return '<a href="' . $escape(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $staffMember->person_id)) . '">' . $escape($name) . '</a>';
	}

	return $escape($name);
};
$renderRosterSentence = static function (string $side) use ($rosterBySide, $staffBySide, $footballLine, $playerNameHtml, $staffName, $escape, $showSubstitutions): string {
	$incomingByMinute = [];
	$starters = [];

	foreach ($rosterBySide[$side] ?? [] as $player) {
		$isIncoming = (int) ($player->came_in ?? 0) === 1;
		$isBench = (int) ($player->is_substitute ?? 0) === 1 && !$isIncoming;

		if ($isIncoming) {
			$minute = trim((string) ($player->in_out_time ?? ''));
			$incomingByMinute[$minute][] = $player;
			continue;
		}

		if (!$isBench) {
			$starters[] = $player;
		}
	}

	$groups = [[], [], [], [], []];

	foreach ($starters as $player) {
		$line = $footballLine((string) ($player->position_name ?? ''));
		$text = $playerNameHtml($player);
		$minute = trim((string) ($player->in_out_time ?? ''));

		if ($showSubstitutions && (int) ($player->out ?? 0) === 1 && $minute !== '' && !empty($incomingByMinute[$minute])) {
			$incoming = array_shift($incomingByMinute[$minute]);
			$text .= ' <span class="jl-site-muted">(' . $escape($minute) . '. ' . $playerNameHtml($incoming) . ')</span>';
		}

		$groups[$line][] = $text;
	}

	if ($showSubstitutions) {
		foreach ($incomingByMinute as $minute => $players) {
			foreach ($players as $player) {
				$groups[4][] = '<span class="jl-site-muted">(' . ($minute !== '' ? $escape($minute) . '. ' : '') . $playerNameHtml($player) . ')</span>';
			}
		}
	}

	$parts = array_values(array_filter(array_map(
		static fn (array $group): string => implode(', ', $group),
		$groups
	), static fn (string $group): bool => $group !== ''));
	$sentence = implode(' - ', $parts);
	$coach = null;

	foreach ($staffBySide[$side] ?? [] as $staffMember) {
		if ((string) ($staffMember->position_name ?? '') === 'COM_JOOMLEAGUE_F_HEAD_COACH') {
			$coach = $staffMember;
			break;
		}
	}

	if (!$coach && !empty($staffBySide[$side])) {
		$coach = $staffBySide[$side][0];
	}

	if ($coach) {
		$coachText = '<span class="jl-site-muted">' . Text::_('COM_JOOMLEAGUE_F_HEAD_COACH') . ':</span> ' . $staffName($coach);
		$sentence = $sentence !== '' ? rtrim($sentence, '.') . '. ' . $coachText : $coachText;
	}

	return $sentence !== '' ? $sentence . '.' : '';
};

$statsBySide = ['home' => [], 'away' => []];

foreach ($statistics as $stat) {
	$side = (int) $stat->projectteam_id === (int) $match->home_projectteam_id ? 'home' : 'away';
	$playerKey = (int) ($stat->teamplayer_id ?? 0);
	$statsBySide[$side][$playerKey]['name'] = trim((string) ($stat->person_name ?? ''));
	$statsBySide[$side][$playerKey]['jerseynumber'] = $stat->jerseynumber ?? null;
	$statsBySide[$side][$playerKey]['entries'][] = [
		'label' => trim((string) ($stat->statistic_short ?: $stat->statistic_name)),
		'value' => $stat->value,
	];
}
?>
<div class="com-joomleague-site jl-match-detail">
	<?php if ($showSectionHeader) : ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $escape(trim(implode(' · ', array_filter([(string) ($match->project_name ?? ''), (string) ($match->season_name ?? ''), (string) ($match->round_name ?? '')], static fn (string $part): bool => trim($part) !== '')), ' ·')); ?></div>
		<<?php echo $headingTag; ?> class="jl-scoreboard">
			<span class="jl-scoreboard-side jl-scoreboard-home">
				<?php if ($showTeamLogo && $teamLogo('home') !== '') : ?>
					<img class="jl-scoreboard-logo" src="<?php echo $escape($resolveImageUrl($teamLogo('home'))); ?>" alt="" loading="lazy" <?php echo $pictureWidth > 0 ? 'width="' . $pictureWidth . '"' : ''; ?> <?php echo $pictureHeight > 0 ? 'height="' . $pictureHeight . '"' : ''; ?>>
				<?php endif; ?>
				<span class="jl-scoreboard-name"><?php echo $escape($teamName('home')); ?></span>
			</span>
			<?php if ($showResult) : ?>
				<span class="jl-scoreboard-center">
					<span class="jl-scoreboard-score"><?php echo $match->team1_result === null || $match->team2_result === null
						? Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED')
						: $escape((string) (float) $match->team1_result . ' : ' . (string) (float) $match->team2_result); ?></span>
					<?php if ($showOvertimeResult && $match->team1_result_ot !== null && $match->team2_result_ot !== null) : ?>
						<span class="jl-scoreboard-extra"><?php echo Text::_('COM_JOOMLEAGUE_SITE_OVERTIME'); ?> <?php echo $escape((string) (float) $match->team1_result_ot . ':' . (string) (float) $match->team2_result_ot); ?></span>
					<?php endif; ?>
					<?php if ($showShotoutResult && $match->team1_result_so !== null && $match->team2_result_so !== null) : ?>
						<span class="jl-scoreboard-extra"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SHOOTOUT'); ?> <?php echo $escape((string) (float) $match->team1_result_so . ':' . (string) (float) $match->team2_result_so); ?></span>
					<?php endif; ?>
				</span>
			<?php else : ?>
				<span class="jl-scoreboard-center"><span class="jl-scoreboard-vs">–</span></span>
			<?php endif; ?>
			<span class="jl-scoreboard-side jl-scoreboard-away">
				<?php if ($showTeamLogo && $teamLogo('away') !== '') : ?>
					<img class="jl-scoreboard-logo" src="<?php echo $escape($resolveImageUrl($teamLogo('away'))); ?>" alt="" loading="lazy" <?php echo $pictureWidth > 0 ? 'width="' . $pictureWidth . '"' : ''; ?> <?php echo $pictureHeight > 0 ? 'height="' . $pictureHeight . '"' : ''; ?>>
				<?php endif; ?>
				<span class="jl-scoreboard-name"><?php echo $escape($teamName('away')); ?></span>
			</span>
		</<?php echo $headingTag; ?>>
		<?php if ($showSplit && $splitParts !== []) : ?>
			<p class="jl-site-muted mb-0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SPLIT'); ?>: <?php echo $escape(implode(' · ', $splitParts)); ?></p>
		<?php endif; ?>
	</section>
	<?php endif; ?>

	<?php if ($showMeta) : ?><div class="jl-site-grid mb-4">
		<?php if ($showMatchDate) : ?>
			<div class="jl-site-card"><strong><?php echo $escape($match->match_date ? date($showMatchTime ? 'd.m.Y H:i' : 'd.m.Y', strtotime((string) $match->match_date)) . ($showMatchTime && $showTimeSuffix ? ' ' . Text::_('COM_JOOMLEAGUE_GLOBAL_CLOCK') : '') : Text::_('COM_JOOMLEAGUE_SITE_DATE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></span></div>
		<?php endif; ?>
		<?php if ($showMatchPlayground) : ?>
			<div class="jl-site-card"><strong><?php echo $escape($match->playground_name ?? Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></span></div>
		<?php endif; ?>
		<?php if ($showMatchCrowd) : ?>
			<div class="jl-site-card"><strong><?php echo (int) ($match->crowd ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ATTENDANCE'); ?></span></div>
		<?php endif; ?>
		<?php if ($showMatchNumber && trim((string) ($match->match_number ?? '')) !== '') : ?>
			<div class="jl-site-card"><strong><?php echo $escape((string) $match->match_number); ?></strong><span class="jl-site-muted">#</span></div>
		<?php endif; ?>
	</div><?php endif; ?>

	<?php
	// extended je JSON pole vlastních polí (Joomla Custom Fields) – prázdný stav se
	// ukládá jako doslovné "{}" nebo "[]", ne jako NULL/prázdný řetězec.
	$extendedContent = trim((string) ($match->extended ?? ''));
	$extendedEmpty = $extendedContent === '' || in_array($extendedContent, ['{}', '[]', 'null'], true);
	?>
	<?php if ($showExtended && !$extendedEmpty) : ?>
		<div class="jl-site-panel mb-4"><?php echo HTMLHelper::_('content.prepare', (string) $match->extended); ?></div>
	<?php endif; ?>

	<?php if ($showRoster && ($rosterBySide['home'] !== [] || $rosterBySide['away'] !== [] || $staffBySide['home'] !== [] || $staffBySide['away'] !== [])) : ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROSTER'); ?></h3>
			<div class="jl-match-roster-inline">
				<?php foreach (['home', 'away'] as $side) : ?>
					<section>
						<p><strong><?php echo $escape($teamName($side)); ?>:</strong> <?php echo $renderRosterSentence($side); ?></p>
					</section>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php
	$hasPreview = $showPreview && trim((string) ($match->preview ?? '')) !== '';
	$hasSummary = $showSummary && trim((string) ($match->summary ?? '')) !== '';
	$hasReportSide = ($showEvents && $sortedEvents !== []) || ($showReferees && $referees !== []);
	?>
	<?php if ($hasPreview || $hasSummary || $hasReportSide) : ?>
		<div class="jl-match-report-layout mb-4">
			<div class="jl-match-report-main">
				<?php if ($hasPreview) : ?>
					<article class="jl-site-panel jl-match-article">
						<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREVIEW'); ?></h3>
						<?php echo HTMLHelper::_('content.prepare', (string) $match->preview); ?>
					</article>
				<?php endif; ?>

				<?php if ($hasSummary) : ?>
					<article class="jl-site-panel jl-match-article">
						<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_SUMMARY'); ?></h3>
						<?php echo HTMLHelper::_('content.prepare', (string) $match->summary); ?>
					</article>
				<?php endif; ?>
			</div>

			<?php if ($hasReportSide) : ?>
				<aside class="jl-match-report-side">
					<?php if ($showReferees && $referees !== []) : ?>
						<section class="jl-site-panel jl-match-brief">
							<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_REFEREES'); ?></h3>
							<ul class="jl-match-referees-list">
								<?php foreach ($referees as $referee) : ?>
									<?php
									$refereeName = isset($referee->firstname, $referee->lastname) && ((string) $referee->firstname !== '' || (string) $referee->lastname !== '')
										? PersonNameHelper::format((string) $referee->firstname, (string) $referee->lastname, (string) ($referee->nickname ?? ''), $refereeNameFormat)
										: (string) (($referee->person_name ?? '') ?: ($referee->external_referee_name ?? ''));
									$refereePosition = $translateLegacyName($referee->position_name ?? '');
									?>
									<li>
										<?php if (!empty($referee->person_id)) : ?>
											<a href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $referee->person_id)); ?>"><?php echo $escape($refereeName); ?></a>
										<?php else : ?>
											<?php echo $escape($refereeName); ?>
										<?php endif; ?>
										<?php if ($showRefereePosition && $refereePosition !== '') : ?><span class="jl-site-muted">(<?php echo $escape($refereePosition); ?>)</span><?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<?php if ($showEvents) : ?>
						<section class="jl-site-panel jl-match-brief">
							<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_EVENTS'); ?></h3>
							<?php if ($sortedEvents) : ?>
								<?php if ($showTimeline) : ?>
									<ul class="jl-match-timeline">
										<?php foreach ($sortedEvents as $event) : ?>
											<li>
												<?php if ($showEventMinute) : ?><span class="jl-match-timeline__minute"><?php echo $escape((string) $event->event_time); ?>'</span><?php endif; ?>
												<span class="jl-match-timeline__dot"></span>
												<span>
													<strong><?php echo $escape($translateLegacyName($event->event_name ?? '')); ?><?php if (isset($eventScores[(int) ($event->id ?? 0)])) : ?> <span class="jl-site-muted">(<?php echo $escape($eventScores[(int) ($event->id ?? 0)]); ?>)</span><?php endif; ?></strong>
													<?php $eventPerson = $eventPersonName($event); ?>
													<?php if ($eventPerson !== '') : ?>
														–
														<?php if ($eventLinkPlayer && !empty($event->person_id)) : ?>
															<a href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $event->person_id)); ?>"><?php echo $escape($eventPerson); ?></a>
														<?php else : ?>
															<?php echo $escape($eventPerson); ?>
														<?php endif; ?>
													<?php endif; ?>
													<?php if ($showEventTeamName && trim((string) ($event->team_name ?? '')) !== '') : ?><span class="jl-site-muted"> · <?php echo $escape((string) $event->team_name); ?></span><?php endif; ?>
													<?php if ($showEventSum && (float) ($event->event_sum ?? 0) !== 0.0) : ?><span class="jl-site-muted"> <?php echo $escape((string) $event->event_sum); ?></span><?php endif; ?>
													<?php if ($showEventNotice && trim((string) ($event->notice ?? '')) !== '') : ?><br><span class="jl-site-muted"><?php echo $escape((string) $event->notice); ?></span><?php endif; ?>
												</span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<ul class="jl-match-event-list">
										<?php foreach ($sortedEvents as $event) : ?>
											<li>
												<?php if ($showEventMinute) : ?><span class="jl-match-event-list__minute"><?php echo $escape((string) $event->event_time); ?>'</span><?php endif; ?>
												<span class="jl-match-event-list__body">
													<strong>
														<?php echo $escape($translateLegacyName($event->event_name ?? '')); ?>
														<?php if (isset($eventScores[(int) ($event->id ?? 0)])) : ?><span class="jl-site-muted">(<?php echo $escape($eventScores[(int) ($event->id ?? 0)]); ?>)</span><?php endif; ?>
													</strong>
													<span>
														<?php if ($eventLinkPlayer && !empty($event->person_id)) : ?>
															<a href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $event->person_id)); ?>"><?php echo $escape($eventPersonName($event)); ?></a>
														<?php else : ?>
															<?php echo $escape($eventPersonName($event)); ?>
														<?php endif; ?>
														<?php if ($showEventTeamName && trim((string) ($event->team_name ?? '')) !== '') : ?><span class="jl-site-muted"> · <?php echo $escape((string) $event->team_name); ?></span><?php endif; ?>
													</span>
													<?php if ($showEventSum && (float) ($event->event_sum ?? 0) !== 0.0) : ?><span class="jl-site-muted"><?php echo $escape((string) $event->event_sum); ?></span><?php endif; ?>
													<?php if ($showEventNotice && trim((string) ($event->notice ?? '')) !== '') : ?><span class="jl-site-muted"><?php echo $escape((string) $event->notice); ?></span><?php endif; ?>
												</span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							<?php else : ?>
								<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_EVENTS'); ?></div>
							<?php endif; ?>
						</section>
					<?php endif; ?>
				</aside>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ($showStats && ($statsBySide['home'] !== [] || $statsBySide['away'] !== [])) : ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_STATS'); ?></h3>
			<div class="jl-match-roster-grid">
				<?php foreach (['home', 'away'] as $side) : ?>
					<div>
						<h4><?php echo $escape($teamName($side)); ?></h4>
						<ul class="jl-site-events-list">
							<?php foreach (($statsBySide[$side] ?? []) as $playerStats) : ?>
								<li>
									<strong><?php echo $escape((string) ($playerStats['name'] ?? '')); ?></strong>:
									<?php echo $escape(implode(', ', array_map(
										static fn (array $e): string => $e['label'] . ' ' . (float) $e['value'],
										$playerStats['entries'] ?? []
									))); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($showLink) : ?>
		<p><a class="jl-site-more" href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id)); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCH_DETAIL'); ?> →</a></p>
	<?php endif; ?>
</div>
