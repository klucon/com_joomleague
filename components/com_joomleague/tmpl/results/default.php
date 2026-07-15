<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Site\View\Results\HtmlView $this */

$params = $this->templateParams;
$show = static fn (string $name, bool $default = true): bool => array_key_exists($name, $params) ? (bool) $params[$name] : $default;
$projectId = $this->project ? (int) $this->project->id : 0;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';

// Oblíbený tým (nastavení projektu) – stejná logika jako v ranking.
$favTeamIds = array_map(
	'intval',
	array_filter(
		array_map('trim', explode(',', (string) ($this->project->fav_team ?? ''))),
		static fn (string $v): bool => $v !== '' && ctype_digit($v)
	)
);
$highlightFav = $show('highlight_fav');
$favBackgroundColor = trim((string) ($this->project->fav_team_color ?? '')) ?: null;
$favTextColor = trim((string) ($this->project->fav_team_text_color ?? '')) ?: null;
$favTextBold = (string) ($this->project->fav_team_text_bold ?? '0') === '1';

// Formát zobrazovaného názvu týmu.
$teamNameFormat = (string) ($params['team_name_format'] ?? '2');
$formatTeamName = static function (string $short, string $middle, string $full) use ($teamNameFormat): string {
	return match ($teamNameFormat) {
		'0' => $short !== '' ? $short : $full,
		'1' => $middle !== '' ? $middle : $full,
		default => $full,
	};
};

// Logo/vlajka u týmu.
$showLogoMode = (string) ($params['show_logo_small'] ?? '1');
$resolveImageUrl = static function (string $path): string {
	if ($path === '') {
		return '';
	}

	return preg_match('#^https?://#i', $path) ? $path : Uri::root(true) . '/' . ltrim($path, '/');
};

// Odkaz ze jména týmu na info o týmu.
$infoLinkMode = (string) ($params['show_info_link'] ?? '1');
$isTeamNameLinked = static function (int $projectTeamId) use ($infoLinkMode, $favTeamIds): bool {
	return match ($infoLinkMode) {
		'0' => false,
		'2' => in_array($projectTeamId, $favTeamIds, true),
		default => true,
	};
};

// Ikony-odkazy u jména týmu – stejná sada jako v ranking, navíc křivka vývoje pořadí
// jde zde smysluplně předvyplnit oběma týmy zápasu (na rozdíl od jednoho řádku tabulky).
$teamLinkSpecs = [];

if ($show('show_club_link')) {
	$teamLinkSpecs[] = ['view' => 'club', 'param' => 'id', 'field' => 'club_id', 'icon' => 'icon-home', 'label' => 'COM_JOOMLEAGUE_SITE_CLUB'];
}

if ($show('show_team_link')) {
	$teamLinkSpecs[] = ['view' => 'roster', 'param' => 'id', 'field' => 'projectteam_id', 'icon' => 'icon-users', 'label' => 'COM_JOOMLEAGUE_SITE_ROSTER'];
}

if ($show('show_teaminfo_link')) {
	$teamLinkSpecs[] = ['view' => 'team', 'param' => 'id', 'field' => 'projectteam_id', 'icon' => 'icon-info-circle', 'label' => 'COM_JOOMLEAGUE_SITE_TEAM'];
}

if ($show('show_teamstats_link')) {
	$teamLinkSpecs[] = ['view' => 'teamstats', 'param' => 'id', 'field' => 'projectteam_id', 'icon' => 'icon-list', 'label' => 'COM_JOOMLEAGUE_SITE_TEAM_STATS'];
}

if ($show('show_plan_link')) {
	$teamLinkSpecs[] = ['view' => 'schedule', 'param' => 'projectteam_id', 'field' => 'projectteam_id', 'icon' => 'icon-calendar', 'label' => 'COM_JOOMLEAGUE_SITE_SCHEDULE'];
}

if ($show('show_clubplan_link')) {
	$teamLinkSpecs[] = ['view' => 'schedule', 'param' => 'club_id', 'field' => 'club_id', 'icon' => 'icon-calendar-alt', 'label' => 'COM_JOOMLEAGUE_SITE_CLUB'];
}

if ($show('show_rivals_link')) {
	$teamLinkSpecs[] = ['view' => 'rivals', 'param' => 'id', 'field' => 'projectteam_id', 'icon' => 'icon-users', 'label' => 'COM_JOOMLEAGUE_SITE_RIVALS'];
}

$showCurveLink = $show('show_curve_link');

// Formát skóre a pořadí týmů.
$resultStyle = (string) ($params['result_style'] ?? '0');
$switchHomeGuest = $show('switch_home_guest', false);
$linkMatchreportMode = (string) ($params['show_link_matchreport'] ?? '1');
$isScoreLinked = static function (bool $homeFav, bool $awayFav) use ($linkMatchreportMode): bool {
	return match ($linkMatchreportMode) {
		'0' => false,
		'2' => $homeFav || $awayFav,
		default => true,
	};
};

$divisionNameField = (string) ($params['show_division_name'] ?? 'name') === 'shortname' ? 'division_short_name' : 'division_name';
$styleClass1 = trim((string) ($params['style_class1'] ?? ''));
$styleClass2 = trim((string) ($params['style_class2'] ?? ''));
$rowExtraClass = static function (int $index) use ($styleClass1, $styleClass2): string {
	$class = $index % 2 === 0 ? $styleClass1 : $styleClass2;

	return $class !== '' ? ' ' . $class : '';
};

// "Živý" zápas – bez přesné znalosti délky utkání jde jen o zjednodušený odhad
// (odehráno už mělo být, ale výsledek zatím není zapsán, a od výkopu neuplynulo
// nesmyslně mnoho času). Přesný aktuální čas/poločas proto nahrazujeme "1. částí".
$markNowPlaying = $show('mark_now_playing', false);
$markNowPlayingBlink = $show('mark_now_playing_blink', false);
$markNowPlayingText = (string) ($params['mark_now_playing_text'] ?? '!NOW!');
$markNowPlayingAltText = (string) ($params['mark_now_playing_alt_text'] ?? 'Start: %STARTTIME% - Current: %ACTUALTIME%');
$markNowPlayingAltActualTime = (string) ($params['mark_now_playing_alt_actual_time'] ?? 'ca. %PART%. part - %MINUTE%. min.');
$now = time();
$isLive = static function (object $match) use ($now): bool {
	if ($match->team1_result !== null && $match->team2_result !== null) {
		return false;
	}

	$date = (string) ($match->match_date ?? '');

	if ($date === '' || strpos($date, '0000-00-00') === 0) {
		return false;
	}

	$start = strtotime($date);

	return $start !== false && $start <= $now && $now - $start < 3 * 3600;
};
$liveTitle = static function (object $match) use ($markNowPlayingAltText, $markNowPlayingAltActualTime, $now): string {
	$start = strtotime((string) $match->match_date);
	$minutes = $start !== false ? max(0, (int) floor(($now - $start) / 60)) : 0;
	$actual = str_replace(['%PART%', '%MINUTE%'], ['1', (string) $minutes], $markNowPlayingAltActualTime);
	$startFormatted = $start !== false ? date('H:i', $start) : '';

	return str_replace(['%STARTTIME%', '%ACTUALTIME%'], [$startFormatted, $actual], $markNowPlayingAltText);
};

// Události zápasu.
$showEvents = $show('show_events', false);
$eventsWithIcons = $show('show_events_with_icons');
$showEventMinute = $show('show_event_minute');
$showEventSum = $show('show_event_sum', false);
$showEventNotice = $show('show_event_notice', false);

$formatScore = static function (object $match) use ($resultStyle, $switchHomeGuest, $formatTeamName): array {
	$homeScore = $match->team1_result;
	$awayScore = $match->team2_result;

	if ($switchHomeGuest) {
		[$homeScore, $awayScore] = [$awayScore, $homeScore];
	}

	if ($homeScore === null || $awayScore === null) {
		return ['played' => false, 'text' => Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED')];
	}

	$separator = $resultStyle === '1' ? ' - ' : ':';
	$suffix = $resultStyle === '2' ? ' (' . Text::_('COM_JOOMLEAGUE_SITE_SECOND_LEG') . ')' : '';

	return ['played' => true, 'text' => $homeScore . $separator . $awayScore . $suffix];
};

// Stavitel odkazů zachovávající kolo napříč navigací.
$resultsUrl = function (array $overrides = []) use ($projectId): string {
	$query = [
		'option' => 'com_joomleague',
		'view' => 'results',
		'project_id' => $projectId,
		'round_id' => array_key_exists('round_id', $overrides) ? $overrides['round_id'] : $this->resultsRoundId,
	];

	$parts = [];

	foreach ($query as $key => $value) {
		if ($key === 'round_id' && (int) $value === 0) {
			continue;
		}

		$parts[] = $key . '=' . rawurlencode((string) $value);
	}

	return Route::_('index.php?' . implode('&', $parts));
};

$roundShortLabel = static function (object $round): string {
	return preg_match('/(\d+)/', (string) $round->name, $matches) === 1 ? $matches[1] : (string) $round->name;
};

$typeSectionHeading = (string) ($params['type_section_heading'] ?? '1');
$roundHeading = static function (?string $name, int $roundcode) use ($typeSectionHeading): string {
	if ($typeSectionHeading === '0' || $name === null || $name === '') {
		return (string) $name;
	}

	return $roundcode . '. ' . Text::_('COM_JOOMLEAGUE_MATCHDAY');
};

$showRoundsDates = $show('show_rounds_dates');
$showMatchdayDateheader = $show('show_matchday_dateheader');

// Seskupení zápasů podle kola (i při zobrazení jednoho kola vznikne jedna skupina).
$byRound = [];

foreach ($this->matches as $match) {
	$roundId = (int) ($match->round_id ?? 0);

	if (!isset($byRound[$roundId])) {
		// Datum záhlaví kola bere admin's vlastní round_date_first/last (rozmezí, kdy se
		// kolo hraje), ne odvozené z jednotlivých zápasů – ty mohou mít odložené utkání
		// s datem daleko mimo běžný víkend kola, což by zkreslilo zobrazené datum.
		$dateFirst = (string) ($match->round_date_first ?? '');
		$dateLast = (string) ($match->round_date_last ?? '');
		$byRound[$roundId] = [
			'id' => $roundId,
			'name' => (string) ($match->round_name ?? ''),
			'roundcode' => (int) ($match->roundcode ?? 0),
			'date_first' => $dateFirst,
			'date_last' => $dateLast !== '' && $dateLast !== $dateFirst ? $dateLast : '',
			'matches' => [],
		];
	}

	$byRound[$roundId]['matches'][] = $match;
}

uasort($byRound, static fn (array $a, array $b): int => $a['roundcode'] <=> $b['roundcode']);

// Volné týmy (nehrají v aktuálně zobrazeném kole) – dává smysl jen při filtru na 1 kolo.
$dnpTeams = [];

if ($this->resultsRoundId > 0 && $show('show_dnp_teams') && $this->teams) {
	$playingIds = [];

	foreach ($this->matches as $match) {
		$playingIds[(int) $match->home_projectteam_id] = true;
		$playingIds[(int) $match->away_projectteam_id] = true;
	}

	foreach ($this->teams as $team) {
		if (!isset($playingIds[(int) $team->id])) {
			$dnpTeams[] = $team;
		}
	}
}

$showDnpTeamsIcons = $show('show_dnp_teams_icons', false);

// show_matchday_pagenav: 0=nezobrazovat, 1=pod výsledky, 2=nad výsledky, 3=nad i pod.
$pagenavMode = (string) ($params['show_matchday_pagenav'] ?? '1');
$renderPagenav = function () use ($resultsUrl, $roundShortLabel): string {
	if (count($this->rounds) <= 1) {
		return '';
	}

	ob_start();
	?>
	<nav class="jl-site-round-pager mt-3" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_SITE_BY_ROUND'); ?>">
		<?php foreach ($this->rounds as $round) : $roundId = (int) $round->id; $isActive = $roundId === $this->resultsRoundId; ?>
			<a
				class="jl-site-round-pager__item<?php echo $isActive ? ' is-active' : ''; ?>"
				href="<?php echo $resultsUrl(['round_id' => $roundId]); ?>"
				title="<?php echo $this->escape((string) $round->name); ?>"
				<?php echo $isActive ? 'aria-current="page"' : ''; ?>
			><?php echo $this->escape($roundShortLabel($round)); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
	return (string) ob_get_clean();
};
$topPagenav = in_array($pagenavMode, ['2', '3'], true) ? $renderPagenav() : '';
$bottomPagenav = in_array($pagenavMode, ['1', '3'], true) ? $renderPagenav() : '';
?>
<div class="com-joomleague-site">
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->project ? $this->escape($this->project->name) : ''; ?></div>
		<?php if ($show('show_sectionheader')) : ?><h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS'); ?></h1><?php endif; ?>
		<?php if ($show('show_matchday_dropdown') && count($this->rounds) > 1) : ?>
			<?php
			$roundIds = array_map(static fn (object $r): int => (int) $r->id, $this->rounds);
			$activeIndex = array_search($this->resultsRoundId, $roundIds, true);
			$prevRound = $activeIndex !== false && $activeIndex > 0 ? $this->rounds[$activeIndex - 1] : null;
			$nextRound = $activeIndex !== false && $activeIndex < count($roundIds) - 1 ? $this->rounds[$activeIndex + 1] : null;
			?>
			<div class="jl-site-round-nav mt-2">
				<a class="jl-site-round-nav__arrow<?php echo $prevRound === null ? ' is-disabled' : ''; ?>" href="<?php echo $prevRound !== null ? $resultsUrl(['round_id' => (int) $prevRound->id]) : '#'; ?>" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_SITE_PREVIOUS_ROUND'); ?>">‹</a>
				<label class="jl-site-round-nav__select">
					<span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_SITE_BY_ROUND'); ?></span>
					<select onchange="location.href=this.value">
						<option value="<?php echo $this->escape($resultsUrl(['round_id' => 0])); ?>" <?php echo $this->resultsRoundId === 0 ? 'selected' : ''; ?>><?php echo Text::_('COM_JOOMLEAGUE_SITE_ALL_ROUNDS'); ?></option>
						<?php foreach ($this->rounds as $round) : ?>
							<option value="<?php echo $this->escape($resultsUrl(['round_id' => (int) $round->id])); ?>" <?php echo (int) $round->id === $this->resultsRoundId ? 'selected' : ''; ?>>
								<?php echo $this->escape((string) $round->name); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<a class="jl-site-round-nav__arrow<?php echo $nextRound === null ? ' is-disabled' : ''; ?>" href="<?php echo $nextRound !== null ? $resultsUrl(['round_id' => (int) $nextRound->id]) : '#'; ?>" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_SITE_NEXT_ROUND'); ?>">›</a>
			</div>
		<?php endif; ?>
	</section>

	<?php if (!$this->matches) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_MATCHES'); ?></div>
	<?php endif; ?>

	<?php echo $topPagenav; ?>

	<?php foreach ($byRound as $round) : ?>
		<div class="jl-schedule-round">
			<?php if ($show('show_sectionheader')) : ?>
				<div class="jl-schedule-round__head">
					<?php echo $this->escape($roundHeading($round['name'], $round['roundcode'])); ?>
					<?php if ($showRoundsDates && $round['date_first'] !== '' && strpos($round['date_first'], '0000-00-00') !== 0) : ?>
						<span class="jl-site-muted">
							<?php echo htmlspecialchars(date('d.m.Y', strtotime($round['date_first'])), ENT_QUOTES, 'UTF-8'); ?>
							<?php if ($round['date_last'] !== '') : ?> – <?php echo htmlspecialchars(date('d.m.Y', strtotime($round['date_last'])), ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
							<?php if ($showMatchdayDateheader) : ?>(<?php echo $round['roundcode']; ?>)<?php endif; ?>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="jl-site-panel table-responsive">
				<table class="table jl-site-table align-middle">
					<thead>
						<tr>
							<?php if ($show('show_date')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></th><?php endif; ?>
							<?php if ($show('show_round')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROUND'); ?></th><?php endif; ?>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME'); ?></th>
							<?php if ($show('show_score')) : ?><th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCORE'); ?></th><?php endif; ?>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_AWAY'); ?></th>
							<?php if ($show('show_venue') && $show('show_playground')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></th><?php endif; ?>
							<?php if ($show('show_referee', false)) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_REFEREES'); ?></th><?php endif; ?>
							<?php if ($show('show_match_number', false)) : ?><th>#</th><?php endif; ?>
							<?php if ($show('show_attendance_column', false)) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_ATTENDANCE'); ?></th><?php endif; ?>
							<?php if ($show('show_division', false)) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DIVISION'); ?></th><?php endif; ?>
							<?php if ($show('show_detail_link')) : ?><th></th><?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($round['matches'] as $index => $match) : ?>
							<?php
							$homeId = (int) $match->home_projectteam_id;
							$awayId = (int) $match->away_projectteam_id;
							$homeFav = in_array($homeId, $favTeamIds, true);
							$awayFav = in_array($awayId, $favTeamIds, true);
							$score = $formatScore($match);
							$live = $markNowPlaying && $isLive($match);

							$renderTeam = static function (
								int $projectTeamId,
								int $clubId,
								string $shortName,
								string $middleName,
								string $fullName,
								string $clubCountry,
								string $logoSmall,
								bool $isFav
							) use (
								$formatTeamName,
								$showLogoMode,
								$resolveImageUrl,
								$jlFlagPath,
								$isTeamNameLinked,
								$teamLinkSpecs,
								$showCurveLink,
								$favTextColor,
								$favTextBold,
								$highlightFav,
								$projectId,
								$match
							): string {
								$nameStyle = '';

								if ($highlightFav && $isFav) {
									if ($favTextColor !== null) {
										$nameStyle .= 'color: ' . $favTextColor . ';';
									}

									if ($favTextBold) {
										$nameStyle .= 'font-weight: 700;';
									}
								}

								$html = '<span class="jl-site-team-cell">';

								if ($showLogoMode === '2') {
									$html .= LayoutHelper::render('joomleague.flag', ['code' => $clubCountry, 'showName' => false, 'size' => 20], $jlFlagPath);
								} elseif ($showLogoMode === '1' && trim($logoSmall) !== '') {
									$url = $resolveImageUrl(trim($logoSmall));
									$html .= '<img class="jl-site-team-logo" src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="" loading="lazy" height="21">';
								}

								$name = htmlspecialchars($formatTeamName($shortName, $middleName, $fullName), ENT_QUOTES);

								if ($isTeamNameLinked($projectTeamId)) {
									$html .= '<a style="' . htmlspecialchars($nameStyle, ENT_QUOTES) . '" href="' . Route::_('index.php?option=com_joomleague&view=team&id=' . $projectTeamId) . '">' . $name . '</a>';
								} else {
									$html .= '<span style="' . htmlspecialchars($nameStyle, ENT_QUOTES) . '">' . $name . '</span>';
								}

								if ($teamLinkSpecs !== [] || $showCurveLink) {
									$html .= '<span class="jl-site-team-links">';

									foreach ($teamLinkSpecs as $spec) {
										$linkId = $spec['field'] === 'club_id' ? $clubId : $projectTeamId;

										if ($linkId > 0) {
											$html .= '<a class="jl-site-team-link" title="' . Text::_($spec['label']) . '" href="' . Route::_('index.php?option=com_joomleague&view=' . $spec['view'] . '&' . $spec['param'] . '=' . $linkId . '&project_id=' . $projectId) . '"><span class="' . htmlspecialchars($spec['icon'], ENT_QUOTES) . '" aria-hidden="true"></span></a>';
										}
									}

									if ($showCurveLink) {
										$html .= '<a class="jl-site-team-link" title="' . Text::_('COM_JOOMLEAGUE_SITE_CURVE') . '" href="' . Route::_('index.php?option=com_joomleague&view=curve&project_id=' . $projectId . '&projectteam1_id=' . (int) $match->home_projectteam_id . '&projectteam2_id=' . (int) $match->away_projectteam_id) . '"><span class="icon-signal" aria-hidden="true"></span></a>';
									}

									$html .= '</span>';
								}

								$html .= '</span>';

								return $html;
							};

							$homeTeamHtml = $renderTeam(
								$homeId,
								(int) $match->home_club_id,
								(string) $match->home_team_short_name,
								(string) $match->home_team_middle_name,
								(string) $match->home_name,
								(string) $match->home_club_country,
								(string) $match->home_club_logo_small,
								$homeFav
							);
							$awayTeamHtml = $renderTeam(
								$awayId,
								(int) $match->away_club_id,
								(string) $match->away_team_short_name,
								(string) $match->away_team_middle_name,
								(string) $match->away_name,
								(string) $match->away_club_country,
								(string) $match->away_club_logo_small,
								$awayFav
							);

							if ($switchHomeGuest) {
								[$homeTeamHtml, $awayTeamHtml] = [$awayTeamHtml, $homeTeamHtml];
							}

							$rowStyle = '';

							if ($highlightFav && ($homeFav || $awayFav) && $favBackgroundColor !== null) {
								$rowStyle = 'background-color: ' . $favBackgroundColor . ';';
							}

							$standardPlayground = (int) ($match->home_standard_playground ?: $match->home_club_standard_playground);
							$playgroundAlert = $show('show_playground_alert', false)
								&& $standardPlayground > 0
								&& (int) $match->playground_id > 0
								&& (int) $match->playground_id !== $standardPlayground;
							?>
							<tr<?php echo $rowStyle !== '' ? ' style="' . $this->escape($rowStyle) . '"' : ''; ?> class="<?php echo trim('jl-site-results-row' . $rowExtraClass($index)); ?>">
								<?php if ($show('show_date')) : ?>
									<td class="text-nowrap">
										<?php
										$matchDate = (string) ($match->match_date ?? '');
										echo $matchDate !== '' && strpos($matchDate, '0000-00-00') !== 0
											? htmlspecialchars(date($show('show_time') ? 'd.m.Y H:i' : 'd.m.Y', strtotime($matchDate)), ENT_QUOTES, 'UTF-8') . ($show('show_time') && $show('show_time_suffix') ? ' ' . Text::_('COM_JOOMLEAGUE_GLOBAL_CLOCK') : '')
											: '';
										?>
									</td>
								<?php endif; ?>
								<?php if ($show('show_round')) : ?><td><?php echo $this->escape((string) $match->round_name); ?></td><?php endif; ?>
								<td><?php echo $homeTeamHtml; ?></td>
								<?php if ($show('show_score')) : ?>
									<td class="text-center">
										<?php if ($live) : ?><span class="jl-site-live<?php echo $markNowPlayingBlink ? ' jl-site-live--blink' : ''; ?>" title="<?php echo $this->escape($liveTitle($match)); ?>"><?php echo $this->escape($markNowPlayingText); ?></span><?php endif; ?>
										<?php if ($score['played'] && $isScoreLinked($homeFav, $awayFav)) : ?>
											<a class="jl-site-score" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id); ?>"><?php echo $this->escape($score['text']); ?></a>
										<?php elseif (!$score['played'] && $isScoreLinked($homeFav, $awayFav)) : ?>
											<a class="jl-site-muted" href="<?php echo Route::_('index.php?option=com_joomleague&view=nextmatch&id=' . (int) $match->id); ?>"><?php echo $this->escape($score['text']); ?></a>
										<?php else : ?>
											<span class="<?php echo $score['played'] ? 'jl-site-score' : 'jl-site-muted'; ?>"><?php echo $this->escape($score['text']); ?></span>
										<?php endif; ?>
										<?php
										// team1/2_result_split ukládá i "prázdný" stav jako pouhé oddělovače (";", ",")
										// – bez skutečné hodnoty periody, proto je nutné je od skutečného obsahu odlišit.
										$partHome = trim((string) $match->team1_result_split, " ;,\t\n\r\0\x0B");
										$partAway = trim((string) $match->team2_result_split, " ;,\t\n\r\0\x0B");
										?>
										<?php if ($show('show_part_results') && ($partHome !== '' || $partAway !== '')) : ?>
											<br><small class="jl-site-muted"><?php echo $this->escape($partHome . ' / ' . $partAway); ?></small>
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<td><?php echo $awayTeamHtml; ?></td>
								<?php if ($show('show_venue') && $show('show_playground')) : ?>
									<td>
										<?php echo $this->escape((string) $match->playground_name); ?>
										<?php if ($playgroundAlert) : ?><span class="jl-site-alert" title="<?php echo Text::_('COM_JOOMLEAGUE_SITE_NOT_HOME_VENUE'); ?>">⚠</span><?php endif; ?>
									</td>
								<?php endif; ?>
								<?php if ($show('show_referee', false)) : ?>
									<td>
										<?php foreach (($this->matchesReferees[(int) $match->id] ?? []) as $referee) : ?>
											<?php echo $this->escape((string) ($referee->person_name ?? '')); ?><?php if (($referee->position_name ?? '') !== '') : ?> <span class="jl-site-muted">(<?php echo $this->escape((string) $referee->position_name); ?>)</span><?php endif; ?><br>
										<?php endforeach; ?>
									</td>
								<?php endif; ?>
								<?php if ($show('show_match_number', false)) : ?><td><?php echo $this->escape((string) ($match->match_number ?? '')); ?></td><?php endif; ?>
								<?php if ($show('show_attendance_column', false)) : ?><td><?php echo (int) ($match->crowd ?? 0) > 0 ? (int) $match->crowd : ''; ?></td><?php endif; ?>
								<?php if ($show('show_division', false)) : ?>
									<td>
										<?php $divisionLabel = (string) ($match->{$divisionNameField} ?: $match->division_name); ?>
										<?php if ($show('show_division_link', false) && (int) $match->division_id > 0) : ?>
											<a href="<?php echo Route::_('index.php?option=com_joomleague&view=ranking&project_id=' . $projectId . '&division_id=' . (int) $match->division_id); ?>"><?php echo $this->escape($divisionLabel); ?></a>
										<?php else : ?>
											<?php echo $this->escape($divisionLabel); ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<?php if ($show('show_detail_link')) : ?>
									<td class="text-end"><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a></td>
								<?php endif; ?>
							</tr>
							<?php if ($showEvents && !empty($this->matchesEvents[(int) $match->id])) : ?>
								<tr class="jl-site-events-row">
									<td colspan="10">
										<ul class="jl-site-events-list">
											<?php foreach ($this->matchesEvents[(int) $match->id] as $event) : ?>
												<li>
													<?php if ($showEventMinute && trim((string) $event->event_time) !== '') : ?><span class="jl-site-events-minute"><?php echo $this->escape((string) $event->event_time); ?>'</span><?php endif; ?>
													<?php if ($eventsWithIcons && trim((string) $event->event_icon) !== '') : ?>
														<span class="<?php echo $this->escape((string) $event->event_icon); ?>" title="<?php echo $this->escape((string) $event->event_name); ?>" aria-hidden="true"></span>
													<?php else : ?>
														<?php echo $this->escape((string) $event->event_name); ?>
													<?php endif; ?>
													<?php echo $this->escape((string) ($event->team_name ?? '')); ?> – <?php echo $this->escape((string) ($event->person_name ?? '')); ?>
													<?php if ($showEventSum && (float) ($event->event_sum ?? 0) !== 0.0) : ?><span class="jl-site-muted">(<?php echo $this->escape((string) $event->event_sum); ?>)</span><?php endif; ?>
													<?php if ($showEventNotice && trim((string) $event->notice) !== '') : ?><span class="jl-site-muted"> – <?php echo $this->escape((string) $event->notice); ?></span><?php endif; ?>
												</li>
											<?php endforeach; ?>
										</ul>
									</td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ($dnpTeams && $round['id'] === $this->resultsRoundId) : ?>
				<p class="jl-site-muted mt-2">
					<?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAMS_NOT_PLAYING'); ?>:
					<?php foreach ($dnpTeams as $team) : ?>
						<?php if ($showDnpTeamsIcons && trim((string) $team->club_logo_small) !== '') : ?>
							<img class="jl-site-team-logo" src="<?php echo $this->escape($resolveImageUrl(trim((string) $team->club_logo_small))); ?>" alt="" loading="lazy" height="18">
						<?php endif; ?>
						<?php echo $this->escape($formatTeamName((string) $team->team_short_name, (string) $team->team_middle_name, (string) $team->team_name)); ?><?php echo $team !== end($dnpTeams) ? ', ' : ''; ?>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>

	<?php echo $bottomPagenav; ?>
</div>
