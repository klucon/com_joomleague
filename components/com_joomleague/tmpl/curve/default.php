<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$project = $this->project;
$input = Factory::getApplication()->getInput();
$divisionId = (int) ($input->getInt('division_id') ?: $input->getInt('division'));
$team1Id = (int) ($input->getInt('projectteam1_id') ?: $input->getInt('tid1'));
$team2Id = (int) ($input->getInt('projectteam2_id') ?: $input->getInt('tid2'));
$rounds = $this->curve['rounds'] ?? [];
$curveTeams = $this->curve['teams'] ?? [];
$maxRank = max(1, (int) ($this->curve['max_rank'] ?? 1));
$roundCount = max(1, count($rounds));
$step = $roundCount > 1 ? 100 / ($roundCount - 1) : 0;
$palette = ['#2563eb', '#dc2626', '#16a34a', '#9333ea', '#ea580c', '#0891b2', '#be123c', '#4f46e5'];
?>
<div class="com-joomleague-site">
	<?php if (!$project) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT_NOT_FOUND'); ?></div><?php return; endif; ?>

	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape(trim(($project->league_name ?? '') . ' · ' . ($project->season_name ?? ''), ' ·')); ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CURVE'); ?></h1>
		<p class="jl-site-muted mb-3"><?php echo $this->escape($project->name); ?></p>
		<nav class="jl-site-nav">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=ranking&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=resultsranking&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS_RANKING'); ?></a>
		</nav>
	</section>

	<form class="jl-site-panel jl-site-filter mb-4" method="get" action="<?php echo Route::_('index.php'); ?>">
		<input type="hidden" name="option" value="com_joomleague">
		<input type="hidden" name="view" value="curve">
		<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
		<label>
			<span><?php echo Text::_('COM_JOOMLEAGUE_SITE_DIVISION'); ?></span>
			<select name="division_id">
				<option value="0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ALL_DIVISIONS'); ?></option>
				<?php foreach ($this->divisions as $division) : ?>
					<option value="<?php echo (int) $division->id; ?>"<?php echo (int) $division->id === $divisionId ? ' selected' : ''; ?>><?php echo $this->escape($division->name); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_1'); ?></span>
			<select name="projectteam1_id">
				<option value="0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ALL_TEAMS'); ?></option>
				<?php foreach ($this->teams as $team) : ?>
					<option value="<?php echo (int) $team->id; ?>"<?php echo (int) $team->id === $team1Id ? ' selected' : ''; ?>><?php echo $this->escape($team->team_name); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_2'); ?></span>
			<select name="projectteam2_id">
				<option value="0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ALL_TEAMS'); ?></option>
				<?php foreach ($this->teams as $team) : ?>
					<option value="<?php echo (int) $team->id; ?>"<?php echo (int) $team->id === $team2Id ? ' selected' : ''; ?>><?php echo $this->escape($team->team_name); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<button class="jl-site-button" type="submit"><?php echo Text::_('COM_JOOMLEAGUE_SITE_APPLY'); ?></button>
	</form>

	<?php if ($rounds === [] || $curveTeams === []) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div>
		<?php return; ?>
	<?php endif; ?>

	<div class="jl-site-panel mb-4">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_CURVE'); ?></h2>
		<div class="jl-site-curve" style="--jl-curve-rounds: <?php echo $roundCount; ?>; --jl-curve-ranks: <?php echo $maxRank; ?>;">
			<div class="jl-site-curve-ranks" aria-hidden="true">
				<?php for ($rank = 1; $rank <= $maxRank; $rank++) : ?><span><?php echo $rank; ?></span><?php endfor; ?>
			</div>
			<div class="jl-site-curve-lines">
				<?php foreach ($curveTeams as $teamIndex => $team) : ?>
					<?php $color = $palette[$teamIndex % count($palette)]; ?>
					<svg class="jl-site-curve-line" viewBox="0 0 100 100" preserveAspectRatio="none" style="--jl-curve-color: <?php echo $this->escape($color); ?>;" role="img" aria-label="<?php echo $this->escape($team->team_name); ?>">
						<?php $points = []; ?>
						<?php foreach ($rounds as $roundIndex => $round) : ?>
							<?php
							$rank = (int) ($team->positions[(int) $round->id] ?? 0);
							if ($rank < 1) {
								continue;
							}
							$x = $roundCount > 1 ? $roundIndex * $step : 50;
							$y = $maxRank > 1 ? (($rank - 1) / ($maxRank - 1)) * 100 : 0;
							$points[] = $x . ',' . $y;
							?>
						<?php endforeach; ?>
						<?php if ($points !== []) : ?>
							<polyline points="<?php echo $this->escape(implode(' ', $points)); ?>" />
							<?php foreach ($points as $point) : ?>
								<?php [$pointX, $pointY] = explode(',', $point); ?>
								<circle cx="<?php echo $this->escape($pointX); ?>" cy="<?php echo $this->escape($pointY); ?>" r="1.8" vector-effect="non-scaling-stroke" />
							<?php endforeach; ?>
						<?php endif; ?>
					</svg>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="jl-site-curve-legend">
			<?php foreach ($curveTeams as $teamIndex => $team) : ?>
				<span style="--jl-curve-color: <?php echo $this->escape($palette[$teamIndex % count($palette)]); ?>;"><?php echo $this->escape($team->team_name); ?></span>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="jl-site-panel table-responsive">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_CURVE_TABLE'); ?></h2>
		<table class="table jl-site-table align-middle">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
					<?php foreach ($rounds as $round) : ?><th><?php echo $this->escape($round->name); ?></th><?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($curveTeams as $team) : ?>
					<tr>
						<th><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->projectteam_id); ?>"><?php echo $this->escape($team->team_name); ?></a></th>
						<?php foreach ($rounds as $round) : ?>
							<td><?php echo isset($team->positions[(int) $round->id]) ? (int) $team->positions[(int) $round->id] : Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED'); ?></td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
