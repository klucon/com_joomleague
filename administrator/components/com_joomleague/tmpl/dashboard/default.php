<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * JoomLeague dashboard: a calm, neutral overview of what is in the system,
 * an explanation of what each area is called, and (if a home club is
 * configured) a plain shortcut back to that club's own schedule. Nothing
 * here is red or urgent - if the admin wants a work queue of open issues,
 * that is a deliberately separate concern, not something this page also
 * tries to be. The schedule shortcut deliberately avoids sport-specific
 * words like "match": a JoomLeague installation might just as well be
 * tracking a cycling race or a chess tournament.
 */
$overviewTiles = [
	['competitions', 'COM_JOOMLEAGUE_DASHBOARD_OVERVIEW_COMPETITIONS', 'flag'],
	['projects', 'COM_JOOMLEAGUE_DASHBOARD_OVERVIEW_PROJECTS', 'folder-open'],
	['clubs', 'COM_JOOMLEAGUE_DASHBOARD_OVERVIEW_CLUBS', 'shield'],
	['teams', 'COM_JOOMLEAGUE_DASHBOARD_OVERVIEW_TEAMS', 'users'],
	['persons', 'COM_JOOMLEAGUE_DASHBOARD_OVERVIEW_PERSONS', 'user'],
	['matches', 'COM_JOOMLEAGUE_DASHBOARD_OVERVIEW_MATCHES', 'play'],
];
$explore = [
	'COM_JOOMLEAGUE_DASHBOARD_GROUP_COMPETITIONS' => [
		['sportprofiles', 'COM_JOOMLEAGUE_SPORTPROFILES_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_SPORTPROFILES', 'puzzle-piece'],
		['sporttypes', 'COM_JOOMLEAGUE_SPORTTYPES_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_SPORTTYPES', 'options'],
		['competitions', 'COM_JOOMLEAGUE_COMPETITIONS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_COMPETITIONS', 'flag'],
		['seasons', 'COM_JOOMLEAGUE_SEASONS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_SEASONS', 'calendar'],
		['projects', 'COM_JOOMLEAGUE_PROJECTS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_PROJECTS', 'folder-open'],
	],
	'COM_JOOMLEAGUE_DASHBOARD_GROUP_ORGANISATIONS' => [
		['clubs', 'COM_JOOMLEAGUE_CLUBS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_CLUBS', 'shield'],
		['teams', 'COM_JOOMLEAGUE_TEAMS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_TEAMS', 'users'],
		['persons', 'COM_JOOMLEAGUE_PERSONS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_PERSONS', 'user'],
		['venues', 'COM_JOOMLEAGUE_VENUES_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_VENUES', 'location'],
	],
	'COM_JOOMLEAGUE_DASHBOARD_GROUP_SYSTEM' => [
		['positions', 'COM_JOOMLEAGUE_POSITIONS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_POSITIONS', 'address'],
		['events', 'COM_JOOMLEAGUE_EVENTS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_EVENTS', 'bolt'],
		['statistics', 'COM_JOOMLEAGUE_STATISTICS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_STATISTICS', 'chart'],
		['templates', 'COM_JOOMLEAGUE_TEMPLATES_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_TEMPLATES', 'palette'],
		['tools', 'COM_JOOMLEAGUE_TOOLS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_DESC_TOOLS', 'wrench'],
	],
];
$formatMatchDate = static function (?string $utc): string {
	if (!$utc) {
		return '';
	}

	$timezone = (string) Factory::getApplication()->get('offset', 'UTC');

	return Factory::getDate($utc, 'UTC')->setTimezone(new \DateTimeZone($timezone))->format(Text::_('COM_JOOMLEAGUE_DATETIME_FORMAT'));
};
$clubMatchHref = static function (array $match, string $target): string {
	return match ($target) {
		'round' => Route::_('index.php?option=com_joomleague&view=matches&round_id=' . $match['round_id']),
		'project' => Route::_('index.php?option=com_joomleague&view=projectpanel&project_id=' . $match['project_id']),
		default => Route::_('index.php?option=com_joomleague&task=match.edit&id=' . $match['id'] . '&round_id=' . $match['round_id']),
	};
};
$clubMatchTile = function (array $match) use ($formatMatchDate, $clubMatchHref): string {
	$dateLine = $this->escape($formatMatchDate($match['scheduled_start']) ?: Text::_('JNONE'));
	if ($match['played_without_result']) {
		$dateLine .= ' &middot; <span class="badge text-bg-light border">' . Text::_('COM_JOOMLEAGUE_DASHBOARD_CLUB_MATCH_NO_RESULT') . '</span>';
	}
	$homeClass = $match['our_slot'] === 1 ? ' class="fw-semibold"' : '';
	$awayClass = $match['our_slot'] === 2 ? ' class="fw-semibold"' : '';

	return '<a class="text-decoration-none text-body d-block h-100" href="' . $clubMatchHref($match, $this->clubMatchLinkTarget) . '">'
		. '<div class="border rounded p-2 h-100 text-center">'
		. '<div class="small text-body-secondary text-truncate mb-1">' . $dateLine . '</div>'
		. '<div class="text-truncate mb-1"><span' . $homeClass . '>' . $this->escape($match['home'] ?: Text::_('JNONE')) . '</span> &ndash; <span' . $awayClass . '>' . $this->escape($match['away'] ?: Text::_('JNONE')) . '</span></div>'
		. '<div class="small text-body-secondary text-truncate">' . $this->escape($match['project_name']) . '</div>'
		. '</div></a>';
};
?>
<div class="container-fluid">

	<?php // ---- Overview: a calm, neutral count of what exists, full width ------------- ?>
	<div class="card mb-4">
		<div class="card-header"><h2 class="h5 mb-0"><?php echo Text::_('COM_JOOMLEAGUE_DASHBOARD_OVERVIEW_TITLE'); ?></h2></div>
		<div class="card-body">
			<div class="row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-3">
				<?php foreach ($overviewTiles as [$key, $label, $icon]) : ?>
					<div class="col">
						<?php $count = (int) ($this->overview[$key] ?? 0); ?>
						<div class="border rounded p-3 h-100 d-flex align-items-center">
							<div class="w-50 text-center"><span class="icon-<?php echo $icon; ?> display-5 text-body-secondary" style="font-weight:900" aria-hidden="true"></span></div>
							<div class="w-50 text-center">
								<div class="h3 mb-0"><?php echo $count; ?></div>
								<div class="text-body-secondary"><?php echo $this->escape(Text::_($label)); ?></div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<?php // ---- Club schedule: a shortcut for the configured home club, nothing more ---- ?>
	<?php if ($this->siteClub !== null && $this->clubScheduleDisplay !== 'hide' && $this->clubMatches !== []) : ?>
		<div class="card mb-4">
			<div class="card-header"><h2 class="h5 mb-0"><?php echo Text::sprintf('COM_JOOMLEAGUE_DASHBOARD_CLUB_MATCHES_TITLE', $this->siteClub['name']); ?></h2></div>
			<div class="card-body">
				<?php if ($this->clubScheduleDisplay === 'collapse') : ?>
					<?php $firstRow = array_slice($this->clubMatches, 0, 5); $rest = array_slice($this->clubMatches, 5); ?>
					<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
						<?php foreach ($firstRow as $match) : ?>
							<div class="col"><?php echo $clubMatchTile($match); ?></div>
						<?php endforeach; ?>
					</div>
					<?php if ($rest !== []) : ?>
						<div class="collapse mt-3" id="club-schedule-more">
							<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
								<?php foreach ($rest as $match) : ?>
									<div class="col"><?php echo $clubMatchTile($match); ?></div>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="text-center mt-3">
							<a id="club-schedule-toggle" class="btn btn-sm btn-outline-secondary" href="#club-schedule-more" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="club-schedule-more">
								<span class="icon-chevron-down club-schedule-toggle-collapsed" aria-hidden="true"></span><span class="icon-chevron-down club-schedule-toggle-collapsed" aria-hidden="true"></span>
								<span class="icon-chevron-up club-schedule-toggle-expanded" aria-hidden="true"></span><span class="icon-chevron-up club-schedule-toggle-expanded" aria-hidden="true"></span>
								<span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_DASHBOARD_CLUB_MATCHES_SHOW_MORE'); ?></span>
							</a>
						</div>
						<style>
							#club-schedule-toggle .club-schedule-toggle-expanded { display: none; }
							#club-schedule-toggle[aria-expanded="true"] .club-schedule-toggle-collapsed { display: none; }
							#club-schedule-toggle[aria-expanded="true"] .club-schedule-toggle-expanded { display: inline-block; }
						</style>
					<?php endif; ?>
				<?php else : ?>
					<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
						<?php foreach ($this->clubMatches as $match) : ?>
							<div class="col"><?php echo $clubMatchTile($match); ?></div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php // ---- Explore: what each area is, in plain language -------------------------- ?>
	<div class="row row-cols-1 row-cols-lg-3 g-4 mb-4">
		<?php foreach ($explore as $groupLabel => $items) : ?>
			<div class="col">
				<div class="card h-100">
					<div class="card-header"><h3 class="h6 mb-0 text-uppercase text-body-secondary"><?php echo Text::_($groupLabel); ?></h3></div>
					<div class="list-group list-group-flush">
						<?php foreach ($items as [$view, $label, $desc, $icon]) : ?>
							<a class="list-group-item list-group-item-action d-flex align-items-center gap-3" style="min-height:5.5rem" href="<?php echo Route::_('index.php?option=com_joomleague&view=' . $view); ?>">
								<span class="icon-<?php echo $icon; ?> text-body-secondary d-inline-block text-center flex-shrink-0" style="width:1.25rem" aria-hidden="true"></span>
								<span><span class="fw-semibold d-block"><?php echo Text::_($label); ?></span><span class="small text-body-secondary" style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2;overflow:hidden"><?php echo Text::_($desc); ?></span></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
