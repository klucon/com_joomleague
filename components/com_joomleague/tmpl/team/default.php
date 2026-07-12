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
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$team = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';
$teamText = $team ? trim((string) ($team->team_info ?: $team->team_notes)) : '';
$translateLegacyName = static function ($value): string {
	$value = trim((string) $value);

	return preg_match('/^(COM|JLM)_[A-Z0-9_-]+$/', $value) ? Text::_($value) : $value;
};
$sportName = $this->project ? $translateLegacyName($this->project->sport_name ?? '') : '';

// URL loga týmu (team_picture je cesta relativní ke kořeni Joomly)
$teamLogo = null;
if ($team && trim((string) ($team->team_picture ?? '')) !== '') {
	$pic = trim((string) $team->team_picture);
	$teamLogo = preg_match('#^https?://#i', $pic) ? $pic : Uri::root(true) . '/' . ltrim($pic, '/');
}

if ($team) {
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'SportsOrganization',
		'@id' => StructuredDataHelper::currentUrl() . '#team',
		'name' => (string) $team->team_name,
		'alternateName' => $team->team_short_name ?? null,
		'url' => StructuredDataHelper::absoluteUrl($team->team_website ?? null),
		'sport' => $sportName !== '' ? $sportName : null,
		'parentOrganization' => $team->club_name ? [
			'@type' => 'SportsOrganization',
			'name' => (string) $team->club_name,
		] : null,
		'location' => $team->playground_name ? [
			'@type' => 'SportsActivityLocation',
			'name' => (string) $team->playground_name,
		] : null,
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$team) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="d-flex align-items-center gap-3 flex-wrap">
			<?php if ($teamLogo) : ?>
				<img class="jl-team-logo" src="<?php echo $this->escape($teamLogo); ?>" alt="<?php echo $this->escape($team->team_name); ?>" loading="lazy" style="max-height:90px;width:auto;">
			<?php endif; ?>
			<div>
				<div class="jl-site-eyebrow"><?php echo LayoutHelper::render('joomleague.flag', ['code' => $team->club_country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?> <?php echo $this->escape($team->club_name ?? ''); ?></div>
				<h1 class="jl-site-title mb-0"><?php echo $this->escape($team->team_name); ?></h1>
				<?php if (!empty($team->team_website)) : ?>
					<p class="mb-0 mt-1"><a href="<?php echo $this->escape((string) $team->team_website); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_SITE_WEBSITE'); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
		<nav class="jl-site-nav mt-3">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=roster&id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROSTER'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $team->project_id . '&projectteam_id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=teamstats&projectteam_id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_STATS'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=rivals&projectteam_id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RIVALS'); ?></a>
			<?php if ($team->club_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=club&id=' . (int) $team->club_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB'); ?></a><?php endif; ?>
			<?php if ($team->playground_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=playground&id=' . (int) $team->playground_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></a><?php endif; ?>
		</nav>
	</section>
	<div class="jl-site-grid mb-4">
		<div class="jl-site-card"><strong><?php echo $this->escape($this->project->name ?? Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $this->escape($team->club_name ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $this->escape($team->playground_name ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo count($this->items); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYERS'); ?></span></div>
	</div>
	<?php if ($teamText !== '') : ?>
		<div class="jl-site-panel mb-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_INFO'); ?></h2>
			<p class="jl-site-muted mb-0"><?php echo nl2br($this->escape($teamText)); ?></p>
		</div>
	<?php endif; ?>
	<?php if ($this->teamSeasons) : ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_SEASONS'); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_LEAGUE'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_SEASON'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DIVISION'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYERS'); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->teamSeasons as $season) : ?>
						<tr>
							<td><?php echo $this->escape($season->project_name ?? ''); ?></td>
							<td><?php echo $this->escape($season->league_name ?? ''); ?></td>
							<td><?php echo $this->escape($season->season_name ?? ''); ?></td>
							<td><?php echo $this->escape($season->division_name ?: Text::_('COM_JOOMLEAGUE_SITE_NO_DIVISION')); ?></td>
							<td><?php echo (int) $season->player_count; ?></td>
							<td><?php if (!(int) $season->is_current) : ?><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $season->projectteam_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<div class="jl-site-panel"><h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></h2><?php require JPATH_COMPONENT . '/tmpl/results/matches_grouped.php'; ?></div>
</div>
