<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$team = $this->item;
$stats = $this->teamStats;
$rowsByStatistic = [];

foreach ($this->teamPlayerStats as $row) {
	$rowsByStatistic[(int) $row->statistic_id][] = $row;
}
?>
<div class="com-joomleague-site">
	<?php if (!$team) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape($team->club_name ?? ''); ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_STATS'); ?></h1>
		<p class="jl-site-muted mb-3"><?php echo $this->escape($team->team_name); ?></p>
		<nav class="jl-site-nav">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=roster&id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROSTER'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $team->project_id . '&projectteam_id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
		</nav>
	</section>

	<div class="jl-site-grid mb-4">
		<div class="jl-site-card"><strong><?php echo (int) ($stats['played'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED_MATCHES'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) ($stats['wins'] ?? 0); ?> / <?php echo (int) ($stats['draws'] ?? 0); ?> / <?php echo (int) ($stats['losses'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_WIN_DRAW_LOSS'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $this->escape((string) ($stats['goals_for'] ?? 0) . ':' . (string) ($stats['goals_against'] ?? 0)); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOALS'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $this->escape((string) ($stats['goal_difference'] ?? 0)); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOAL_DIFFERENCE'); ?></span></div>
	</div>

	<div class="jl-site-panel table-responsive mb-4">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME_AWAY'); ?></h2>
		<table class="table jl-site-table align-middle">
			<thead>
				<tr>
					<th></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED_MATCHES'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOALS_FOR'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOALS_AGAINST'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME'); ?></th>
					<td><?php echo (int) ($stats['home'] ?? 0); ?></td>
					<td><?php echo (int) ($stats['home_played'] ?? 0); ?></td>
					<td><?php echo $this->escape((string) ($stats['home_goals_for'] ?? 0)); ?></td>
					<td><?php echo $this->escape((string) ($stats['home_goals_against'] ?? 0)); ?></td>
				</tr>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_AWAY'); ?></th>
					<td><?php echo (int) ($stats['away'] ?? 0); ?></td>
					<td><?php echo (int) ($stats['away_played'] ?? 0); ?></td>
					<td><?php echo $this->escape((string) ($stats['away_goals_for'] ?? 0)); ?></td>
					<td><?php echo $this->escape((string) ($stats['away_goals_against'] ?? 0)); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="jl-site-grid mb-4">
		<div class="jl-site-card"><strong><?php echo (int) ($stats['attendance_total'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ATTENDANCE_TOTAL'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) ($stats['attendance_average'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ATTENDANCE_AVERAGE'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) ($stats['attendance_best'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ATTENDANCE_BEST'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) ($stats['attendance_worst'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ATTENDANCE_WORST'); ?></span></div>
	</div>

	<?php foreach ($rowsByStatistic as $statRows) : ?>
		<?php $first = $statRows[0]; ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h2><?php echo $this->escape($first->statistic_name); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead><tr><th>#</th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_VALUE'); ?></th></tr></thead>
				<tbody>
					<?php foreach ($statRows as $i => $row) : ?>
						<tr>
							<td><?php echo $i + 1; ?></td>
							<td><?php if ($row->person_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $row->person_id . '&project_id=' . (int) $team->project_id); ?>"><?php echo $this->escape($row->person_name ?: $row->nickname); ?></a><?php else : ?><?php echo Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?><?php endif; ?></td>
							<td><strong><?php echo $this->escape((string) $row->value); ?></strong></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endforeach; ?>
</div>
