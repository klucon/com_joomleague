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
$sortEventsDesc     = $options['sortEventsDesc'] ?? true;

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
$showPlayerPicture  = $options['playerPicture'] ?? false;
$playerPictureWidth  = (int) ($options['playerPictureWidth'] ?? 0);
$playerPictureHeight = (int) ($options['playerPictureHeight'] ?? 40);

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

// Strukturovaná data pro vyhledávače (jednou za zápas na stránce).
if (class_exists(StructuredDataHelper::class)) {
	StructuredDataHelper::add(Factory::getApplication()->getDocument(), [
		'@context'    => 'https://schema.org',
		'@type'       => 'SportsEvent',
		'name'        => trim((string) ($match->home_name ?? '') . ' - ' . (string) ($match->away_name ?? '')),
		'startDate'   => !empty($match->match_date) ? date('c', strtotime((string) $match->match_date)) : null,
		'eventStatus' => !empty($match->cancel)
			? 'https://schema.org/EventCancelled'
			: (($match->team1_result !== null && $match->team2_result !== null) ? 'https://schema.org/EventCompleted' : 'https://schema.org/EventScheduled'),
		'sport'       => $sportName !== '' ? $sportName : null,
		'location'    => !empty($match->playground_name) ? ['@type' => 'SportsActivityLocation', 'name' => (string) $match->playground_name] : null,
		'homeTeam'    => !empty($match->home_name) ? ['@type' => 'SportsOrganization', 'name' => (string) $match->home_name] : null,
		'awayTeam'    => !empty($match->away_name) ? ['@type' => 'SportsOrganization', 'name' => (string) $match->away_name] : null,
		'organizer'   => !empty($match->project_name) ? ['@type' => 'SportsOrganization', 'name' => (string) $match->project_name] : null,
		'url'         => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id, false)),
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

if ($sortEventsDesc) {
	$sortedEvents = array_reverse($sortedEvents);
}

// Sestava/realizační tým – rozdělené podle strany (domácí/hosté).
$rosterBySide = ['home' => [], 'away' => []];

foreach ($roster as $entry) {
	$side = (int) $entry->projectteam_id === (int) $match->home_projectteam_id ? 'home' : 'away';
	$rosterBySide[$side][] = $entry;
}

$staffBySide = ['home' => [], 'away' => []];

foreach ($staffList as $entry) {
	$side = (int) $entry->projectteam_id === (int) $match->home_projectteam_id ? 'home' : 'away';
	$staffBySide[$side][] = $entry;
}

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
		<div class="jl-site-eyebrow"><?php echo $escape(trim(($match->project_name ?? '') . ' · ' . ($match->round_name ?? ''), ' ·')); ?></div>
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

	<?php if ($showPreview && trim((string) ($match->preview ?? '')) !== '') : ?>
		<div class="jl-site-panel mb-4"><h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREVIEW'); ?></h3><?php echo HTMLHelper::_('content.prepare', (string) $match->preview); ?></div>
	<?php endif; ?>

	<?php if ($showSummary && trim((string) ($match->summary ?? '')) !== '') : ?>
		<div class="jl-site-panel mb-4"><h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_SUMMARY'); ?></h3><?php echo $match->summary; ?></div>
	<?php endif; ?>

	<?php if ($showReferees && $referees !== []) : ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_REFEREES'); ?></h3>
			<table class="table jl-site-table">
				<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th><?php if ($showRefereePosition) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POSITION'); ?></th><?php endif; ?></tr></thead>
				<tbody>
					<?php foreach ($referees as $referee) : ?>
						<tr>
							<td>
								<?php
								$refereeName = isset($referee->firstname, $referee->lastname)
									? PersonNameHelper::format((string) $referee->firstname, (string) $referee->lastname, (string) ($referee->nickname ?? ''), $refereeNameFormat)
									: (string) ($referee->person_name ?: $referee->nickname);
								?>
								<?php if (!empty($referee->person_id)) : ?>
									<a href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $referee->person_id)); ?>"><?php echo $escape($refereeName); ?></a>
								<?php else : ?>
									<?php echo $escape($refereeName); ?>
								<?php endif; ?>
							</td>
							<?php if ($showRefereePosition) : ?><td><?php echo $escape($translateLegacyName($referee->position_name ?? '')); ?></td><?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if ($showRoster && ($rosterBySide['home'] !== [] || $rosterBySide['away'] !== [] || $staffBySide['home'] !== [] || $staffBySide['away'] !== [])) : ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROSTER'); ?></h3>
			<div class="jl-match-roster-grid">
				<?php foreach (['home', 'away'] as $side) : ?>
					<div>
						<h4><?php echo $escape($teamName($side)); ?></h4>
						<table class="table jl-site-table">
							<tbody>
								<?php foreach (($rosterBySide[$side] ?? []) as $index => $player) : ?>
									<?php
									$formattedName = PersonNameHelper::format((string) $player->firstname, (string) $player->lastname, (string) $player->nickname, $playerNameFormat);
									$linked = $isPlayerLinked((int) $player->projectteam_id);
									?>
									<tr class="<?php echo trim($rowClass((int) $index)); ?>">
										<td class="text-end text-nowrap"><?php echo $player->jerseynumber !== null ? (int) $player->jerseynumber : ''; ?></td>
										<?php if ($showPlayerPicture) : ?>
											<td>
												<?php $playerPicPath = trim((string) ($player->person_teampicture ?: $player->person_picture)); ?>
												<?php if ($playerPicPath !== '') : ?>
													<img src="<?php echo $escape($resolveImageUrl($playerPicPath)); ?>" alt="" loading="lazy" <?php echo $playerPictureWidth > 0 ? 'width="' . $playerPictureWidth . '"' : ''; ?> <?php echo $playerPictureHeight > 0 ? 'height="' . $playerPictureHeight . '"' : ''; ?>>
												<?php endif; ?>
											</td>
										<?php endif; ?>
										<td>
											<?php if ($linked && !empty($player->person_id)) : ?>
												<a href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $player->person_id)); ?>"><?php echo $escape($formattedName); ?></a>
											<?php else : ?>
												<?php echo $escape($formattedName); ?>
											<?php endif; ?>
											<?php if ($showSubstitutions && ((int) $player->came_in === 1 || (int) $player->out === 1)) : ?>
												<span class="jl-site-muted">
													<?php echo (int) $player->came_in === 1 ? '↑' : '↓'; ?>
													<?php echo $player->in_out_time !== null ? $escape((string) $player->in_out_time) : ''; ?>
												</span>
											<?php endif; ?>
										</td>
										<td class="jl-site-muted"><?php echo $escape($translateLegacyName($player->position_name ?? '')); ?></td>
									</tr>
								<?php endforeach; ?>
								<?php foreach (($staffBySide[$side] ?? []) as $staffMember) : ?>
									<tr class="jl-site-muted">
										<td></td>
										<?php if ($showPlayerPicture) : ?><td></td><?php endif; ?>
										<td><?php echo $escape(PersonNameHelper::format((string) $staffMember->firstname, (string) $staffMember->lastname, (string) $staffMember->nickname, $playerNameFormat)); ?></td>
										<td><?php echo $escape($staffMember->position_name !== null && trim((string) $staffMember->position_name) !== '' ? $translateLegacyName($staffMember->position_name) : Text::_('COM_JOOMLEAGUE_SITE_STAFF')); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			</div>
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

	<?php if ($showEvents) : ?>
		<div class="jl-site-panel table-responsive mb-3">
			<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_EVENTS'); ?></h3>
			<?php if ($sortedEvents) : ?>
				<?php if ($showTimeline) : ?>
					<ul class="jl-match-timeline">
						<?php foreach ($sortedEvents as $event) : ?>
							<li>
								<?php if ($showEventMinute) : ?><span class="jl-match-timeline__minute"><?php echo $escape((string) $event->event_time); ?>'</span><?php endif; ?>
								<span class="jl-match-timeline__dot"></span>
								<span><?php echo $escape($translateLegacyName($event->event_name ?? '')); ?> – <?php echo $escape($event->person_name ?? ''); ?><?php if ($showEventTeamName) : ?> (<?php echo $escape($event->team_name ?? ''); ?>)<?php endif; ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<table class="table jl-site-table">
						<thead><tr>
							<?php if ($showEventMinute) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_MINUTE'); ?></th><?php endif; ?>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_EVENT'); ?></th>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th>
							<?php if ($showEventTeamName) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th><?php endif; ?>
						</tr></thead>
						<tbody>
							<?php foreach ($sortedEvents as $event) : ?>
								<tr>
									<?php if ($showEventMinute) : ?><td><?php echo $escape((string) $event->event_time); ?></td><?php endif; ?>
									<td>
										<?php echo $escape($translateLegacyName($event->event_name ?? '')); ?>
										<?php if ($showEventSum && (float) ($event->event_sum ?? 0) !== 0.0) : ?><span class="jl-site-muted">(<?php echo $escape((string) $event->event_sum); ?>)</span><?php endif; ?>
										<?php if ($showEventNotice && trim((string) ($event->notice ?? '')) !== '') : ?><br><span class="jl-site-muted"><?php echo $escape((string) $event->notice); ?></span><?php endif; ?>
									</td>
									<td>
										<?php if ($eventLinkPlayer && !empty($event->person_id)) : ?>
											<a href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $event->person_id)); ?>"><?php echo $escape($event->person_name ?? ''); ?></a>
										<?php else : ?>
											<?php echo $escape($event->person_name ?? ''); ?>
										<?php endif; ?>
									</td>
									<?php if ($showEventTeamName) : ?><td><?php echo $escape($event->team_name ?? ''); ?></td><?php endif; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			<?php else : ?>
				<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_EVENTS'); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ($showLink) : ?>
		<p><a class="jl-site-more" href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id)); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCH_DETAIL'); ?> →</a></p>
	<?php endif; ?>
</div>
