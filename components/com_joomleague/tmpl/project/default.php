<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$project = $this->project;
$isRunningRace = $project && ($project->project_type ?? '') === 'RUNNING_RACE';
$translateLegacyName = static function ($value): string {
	$value = trim((string) $value);

	return preg_match('/^(COM|JLM)_[A-Z0-9_-]+$/', $value) ? Text::_($value) : $value;
};
$sportName = $project ? $translateLegacyName($project->sport_name ?? '') : '';

if ($project) {
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'SportsOrganization',
		'@id' => StructuredDataHelper::currentUrl() . '#competition',
		'name' => (string) $project->name,
		'sport' => $sportName !== '' ? $sportName : null,
		'parentOrganization' => $project->league_name ? [
			'@type' => 'SportsOrganization',
			'name' => (string) $project->league_name,
		] : null,
		'event' => array_map(
			static fn (object $match): array => [
				'@type' => 'SportsEvent',
				'name' => trim((string) ($match->home_name ?? '') . ' - ' . (string) ($match->away_name ?? '')),
				'startDate' => !empty($match->match_date) ? date('c', strtotime((string) $match->match_date)) : null,
			],
			$this->matches
		),
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$project) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape(trim(($project->league_name ?? '') . ' · ' . ($project->season_name ?? ''), ' ·')); ?></div>
		<h1 class="jl-site-title"><?php echo $this->escape($project->name); ?></h1>
		<p class="jl-site-muted mb-3"><?php echo $this->escape($sportName); ?></p>
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
