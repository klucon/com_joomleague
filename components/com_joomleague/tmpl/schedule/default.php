<?php

/**
 * Rozpis zápasů (schedule) — tým i klub, řazení dle kola / dle data,
 * filtr doma/venku, export do kalendáře. Parita s teamplan + clubplan.
 *
 * @author   Ondřej Klučka
 * @package  Klucon.Joomleague
 * @license  GNU General Public License version 2 or later
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \Joomleague\Component\Joomleague\Site\View\Schedule\HtmlView $this */

$title   = Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE');
$eyebrow = $this->project ? $this->project->name : '';

if ($this->scheduleTeam) {
	$title   = $this->scheduleTeam->team_name;
	$eyebrow = Text::_('COM_JOOMLEAGUE_SITE_TEAM') . ' · ' . Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE');
} elseif ($this->scheduleClub) {
	$title   = $this->scheduleClub->name;
	$eyebrow = Text::_('COM_JOOMLEAGUE_SITE_CLUB') . ' · ' . Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE');
}

// export do kalendáře
$icalQuery = 'index.php?option=com_joomleague&view=ical';
if ($this->project) {
	$icalQuery .= '&project_id=' . (int) $this->project->id;
}
if ($this->scheduleTeam) {
	$icalQuery .= '&projectteam_id=' . (int) $this->scheduleTeam->id;
}
if ($this->scheduleClub) {
	$icalQuery .= '&club_id=' . (int) $this->scheduleClub->id;
}
$icalPath     = Route::_($icalQuery, false);
$icalUrl      = Uri::root() . ltrim($icalPath, '/');
$webcalUrl    = preg_replace('#^https?://#', 'webcal://', $icalUrl);
$calendarName = $title !== '' ? $title : Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE');

// režim zobrazení + filtr
$input    = Factory::getApplication()->getInput();
$planMode = $input->getWord('plan', 'round') === 'date' ? 'date' : 'round';
$filter   = \in_array($input->getWord('filter', 'all'), ['home', 'away'], true) ? $input->getWord('filter', 'all') : 'all';

$scheduleMatches = $this->matches;
$teamPtId        = $this->scheduleTeam ? (int) $this->scheduleTeam->id : 0;

if ($teamPtId && $filter !== 'all') {
	$scheduleMatches = array_values(array_filter($scheduleMatches, static function ($m) use ($teamPtId, $filter) {
		return $filter === 'home'
			? (int) $m->home_projectteam_id === $teamPtId
			: (int) $m->away_projectteam_id === $teamPtId;
	}));
}

// URL pro přepínače (zachová kontext projektu/týmu/klubu)
$baseQuery = 'index.php?option=com_joomleague&view=schedule';
if ($this->project) {
	$baseQuery .= '&project_id=' . (int) $this->project->id;
}
if ($this->scheduleTeam) {
	$baseQuery .= '&projectteam_id=' . (int) $this->scheduleTeam->id;
}
if ($this->scheduleClub) {
	$baseQuery .= '&club_id=' . (int) $this->scheduleClub->id;
}
$modeUrl = static fn (string $mode, string $flt): string =>
	Route::_($baseQuery . '&plan=' . $mode . ($flt !== 'all' ? '&filter=' . $flt : ''));

// řádek jednoho zápasu
$matchRow = static function ($m): void {
	$score = $m->team1_result === null || $m->team2_result === null
		? '<span class="jl-site-muted">' . Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED') . '</span>'
		: '<span class="jl-site-score">' . htmlspecialchars((string) $m->team1_result . ' : ' . (string) $m->team2_result, ENT_QUOTES, 'UTF-8') . '</span>';
	?>
	<tr>
		<td class="text-nowrap"><?php echo $m->match_date && strpos((string) $m->match_date, '0000-00-00') !== 0 ? htmlspecialchars(date('d.m.Y H:i', strtotime((string) $m->match_date)), ENT_QUOTES, 'UTF-8') : ''; ?></td>
		<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $m->home_projectteam_id); ?>"><?php echo htmlspecialchars((string) ($m->home_name ?? ''), ENT_QUOTES, 'UTF-8'); ?></a></td>
		<td class="text-center"><?php echo $score; ?></td>
		<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $m->away_projectteam_id); ?>"><?php echo htmlspecialchars((string) ($m->away_name ?? ''), ENT_QUOTES, 'UTF-8'); ?></a></td>
		<td><?php echo htmlspecialchars((string) ($m->playground_name ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
		<td class="text-end"><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $m->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a></td>
	</tr>
	<?php
};

// seskupení dle kola (zachovává pořadí z modelu)
$byRound = [];
foreach ($scheduleMatches as $m) {
	$byRound[(string) ($m->round_name ?? '')][] = $m;
}

// řazení dle data
$byDate = $scheduleMatches;
usort($byDate, static fn ($a, $b) => strcmp((string) ($a->match_date ?? ''), (string) ($b->match_date ?? '')));

$tableHead = '<thead><tr>'
	. '<th>' . Text::_('COM_JOOMLEAGUE_SITE_DATE') . '</th>'
	. '<th>' . Text::_('COM_JOOMLEAGUE_SITE_HOME') . '</th>'
	. '<th class="text-center">' . Text::_('COM_JOOMLEAGUE_SITE_SCORE') . '</th>'
	. '<th>' . Text::_('COM_JOOMLEAGUE_SITE_AWAY') . '</th>'
	. '<th>' . Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND') . '</th><th></th></tr></thead>';

// pevné šířky sloupců (aby se kola zarovnala) — datum, domácí, výsledek, hosté, stadion, detail
$colgroup = '<colgroup>'
	. '<col style="width:15%">'
	. '<col style="width:22%">'
	. '<col style="width:8%">'
	. '<col style="width:22%">'
	. '<col style="width:23%">'
	. '<col style="width:10%">'
	. '</colgroup>';
?>
<div class="com-joomleague-site">
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape($eyebrow); ?></div>
		<h1 class="jl-site-title"><?php echo $this->escape($title); ?></h1>
	</section>

	<div class="jl-site-grid mb-4">
		<div class="jl-site-card"><strong><?php echo (int) ($this->matchSummary['total'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) ($this->matchSummary['played'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED_MATCHES'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) ($this->matchSummary['upcoming'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_UPCOMING_MATCHES'); ?></span></div>
		<?php if ($this->scheduleTeam) : ?>
			<div class="jl-site-card"><strong><?php echo (int) ($this->matchSummary['home'] ?? 0); ?> / <?php echo (int) ($this->matchSummary['away'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME_AWAY'); ?></span></div>
		<?php endif; ?>
	</div>

	<div class="jl-site-panel mb-4">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_ADD_TO_CALENDAR'); ?></h2>
		<p class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CALENDAR_SUBSCRIBE_HELP'); ?></p>
		<nav class="jl-site-nav">
			<a href="<?php echo $this->escape($icalUrl); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DOWNLOAD_ICS'); ?><br><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ONE_TIME_IMPORT'); ?></span></a>
			<a href="https://calendar.google.com/calendar/render?cid=<?php echo rawurlencode($icalUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOOGLE_CALENDAR'); ?></a>
			<a href="<?php echo $this->escape($webcalUrl); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_APPLE_CALENDAR'); ?></a>
			<a href="https://outlook.live.com/calendar/0/addfromweb?url=<?php echo rawurlencode($icalUrl); ?>&amp;name=<?php echo rawurlencode($calendarName); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_SITE_OUTLOOK_COM'); ?></a>
			<a href="https://outlook.office.com/calendar/0/addfromweb?url=<?php echo rawurlencode($icalUrl); ?>&amp;name=<?php echo rawurlencode($calendarName); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_SITE_OFFICE_365'); ?></a>
		</nav>
	</div>

	<div class="jl-site-panel">
		<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
			<h2 class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></h2>
			<nav class="jl-site-nav">
				<a class="<?php echo $planMode === 'round' ? 'active' : ''; ?>" href="<?php echo $modeUrl('round', $filter); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_BY_ROUND'); ?></a>
				<a class="<?php echo $planMode === 'date' ? 'active' : ''; ?>" href="<?php echo $modeUrl('date', $filter); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_BY_DATE'); ?></a>
			</nav>
		</div>

		<?php if ($this->scheduleTeam) : ?>
			<nav class="jl-site-nav mb-3">
				<a class="<?php echo $filter === 'all' ? 'active' : ''; ?>" href="<?php echo $modeUrl($planMode, 'all'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ALL'); ?></a>
				<a class="<?php echo $filter === 'home' ? 'active' : ''; ?>" href="<?php echo $modeUrl($planMode, 'home'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME'); ?></a>
				<a class="<?php echo $filter === 'away' ? 'active' : ''; ?>" href="<?php echo $modeUrl($planMode, 'away'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_AWAY'); ?></a>
			</nav>
		<?php endif; ?>

		<?php if (!$scheduleMatches) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_MATCHES'); ?></div>
		<?php elseif ($planMode === 'date') : ?>
			<div class="table-responsive">
				<table class="table jl-site-table jl-matches-table align-middle">
					<?php echo $colgroup; ?>
					<?php echo $tableHead; ?>
					<tbody><?php foreach ($byDate as $m) { $matchRow($m); } ?></tbody>
				</table>
			</div>
		<?php else : ?>
			<?php foreach ($byRound as $roundName => $roundMatches) : ?>
				<div class="jl-schedule-round">
					<?php if ($roundName !== '') : ?>
						<div class="jl-schedule-round__head"><?php echo $this->escape($roundName); ?></div>
					<?php endif; ?>
					<div class="table-responsive">
						<table class="table jl-site-table jl-matches-table align-middle mb-0">
							<?php echo $colgroup; ?>
							<?php echo $tableHead; ?>
							<tbody><?php foreach ($roundMatches as $m) { $matchRow($m); } ?></tbody>
						</table>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
