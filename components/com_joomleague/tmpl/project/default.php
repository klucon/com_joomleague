<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$project = $this->project;
$isRunningRace = $project && ($project->project_type ?? '') === 'RUNNING_RACE';
$translateLegacyName = static function ($value): string {
	$value = trim((string) $value);

	return preg_match('/^(COM|JLM)_[A-Z0-9_-]+$/', $value) ? Text::_($value) : $value;
};
$sportName = $project ? $translateLegacyName($project->sport_name ?? '') : '';

// 'projectheading' – konfigurace záhlaví projektové domovské stránky.
$headingParams = $this->templateParams;
$showTitle = (bool) ($headingParams['show_title'] ?? true);
$showProjectLogo = (bool) ($headingParams['show_project_logo'] ?? true);

$projectLogo = '';
$schemaProjectLogo = null;
if ($project && $showProjectLogo) {
	$pic = trim((string) ($project->picture ?? ''));
	$schemaProjectLogo = $pic !== '' ? (preg_match('#^https?://#i', $pic) ? $pic : Uri::root(true) . '/' . ltrim($pic, '/')) : null;

	if ($pic === '') {
		$pic = trim((string) ComponentHelper::getParams('com_joomleague')->get('placeholder_project_picture', ''));
	}
	if ($pic !== '') {
		$projectLogo = preg_match('#^https?://#i', $pic) ? $pic : Uri::root(true) . '/' . ltrim($pic, '/');
	}
}

if ($project) {
	$projectUrl = StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $project->id, false));
	$projectId = (int) $project->id;
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => ['SportsOrganization', 'SportsEvent'],
		'@id' => $projectUrl ? $projectUrl . '#competition' : null,
		'name' => (string) $project->name,
		'alternateName' => trim((string) ($project->league_name ?? '') . ' ' . (string) ($project->season_name ?? '')),
		'url' => $projectUrl,
		'image' => $schemaProjectLogo,
		'mainEntityOfPage' => StructuredDataHelper::webPage((string) $project->name, trim($sportName . ' · ' . (string) ($project->season_name ?? ''), ' ·'), $projectUrl),
		'sport' => $sportName !== '' ? $sportName : null,
		'startDate' => !empty($project->start_date) ? (string) $project->start_date : null,
		'endDate' => !empty($project->end_date) ? (string) $project->end_date : null,
		'parentOrganization' => $project->league_name ? [
			'@type' => 'SportsOrganization',
			'name' => (string) $project->league_name,
		] : null,
		'member' => array_map(
			static fn (object $team): array => [
				'@type' => 'SportsTeam',
				'@id' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id, false)) . '#sportsteam',
				'name' => (string) $team->team_name,
				'url' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id, false)),
			],
			$this->teams
		),
		'subEvent' => array_map(
			static fn (object $match): array => [
				'@type' => 'SportsEvent',
				'@id' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=matchreport&project_id=' . $projectId . '&id=' . (int) $match->id, false)) . '#sportsevent',
				'name' => trim((string) ($match->home_name ?? '') . ' - ' . (string) ($match->away_name ?? '')),
				'url' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=matchreport&project_id=' . $projectId . '&id=' . (int) $match->id, false)),
				'startDate' => !empty($match->match_date) ? date('c', strtotime((string) $match->match_date)) : null,
				'sport' => $sportName !== '' ? $sportName : null,
			],
			$this->matches
		),
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$project) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="d-flex align-items-center gap-3">
		<?php if ($projectLogo !== '') : ?><img class="jl-team-logo" src="<?php echo $this->escape($projectLogo); ?>" alt="<?php echo $this->escape($project->name); ?>" loading="lazy" style="max-height:90px;width:auto;flex-shrink:0;"><?php endif; ?>
		<div style="min-width:0;">
			<?php if ($showTitle) : ?><h1 class="jl-site-title mb-1"><?php echo $this->escape($project->name); ?></h1><?php endif; ?>
			<p class="jl-site-muted mb-0"><?php echo $this->escape(trim($sportName . ' · ' . (string) ($project->season_name ?? ''), ' ·')); ?></p>
		</div>
		</div>
		<nav class="jl-site-nav">
			<?php if ($isRunningRace) : ?>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=raceresults&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_RESULTS'); ?></a>
			<?php else : ?>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=ranking&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=results&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=resultsranking&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS_RANKING'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=resultsmatrix&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULT_MATRIX'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=nextmatch&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NEXT_MATCH'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=teams&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAMS'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=referees&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_REFEREES'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=stats&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_STATS'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=statsranking&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_STATS_RANKING'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=eventsranking&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_EVENTS_RANKING'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=curve&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CURVE'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=treetonode&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TREE'); ?></a>
			<?php endif; ?>
		</nav>
	</section>
	<?php if ($isRunningRace) : ?>
		<div class="jl-site-grid mb-4">
			<div class="jl-site-card"><strong><?php echo count($this->rounds); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROUNDS'); ?></span></div>
			<div class="jl-site-card"><strong><?php echo count($this->raceCategories); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_CATEGORIES'); ?></span></div>
			<div class="jl-site-card"><strong><?php echo count($this->raceResults); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_RESULTS'); ?></span></div>
		</div>
		<div class="jl-site-panel">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_RESULTS'); ?></h2>
			<p class="jl-site-muted mb-3"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_PROJECT_HINT'); ?></p>
			<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=raceresults&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_RESULTS'); ?></a>
		</div>
	<?php else : ?>
		<div class="jl-site-grid mb-4">
			<div class="jl-site-card"><strong><?php echo count($this->teams); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAMS'); ?></span></div>
			<div class="jl-site-card"><strong><?php echo count($this->rounds); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROUNDS'); ?></span></div>
			<div class="jl-site-card"><strong><?php echo count($this->matches); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_UPCOMING_MATCHES'); ?></span></div>
		</div>
		<div class="jl-site-panel">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_UPCOMING_MATCHES'); ?></h2>
			<?php require __DIR__ . '/../results/matches.php'; ?>
		</div>
	<?php endif; ?>
</div>
