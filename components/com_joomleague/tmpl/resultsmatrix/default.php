<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$project = $this->project;
?>
<div class="com-joomleague-site">
	<?php if (!$project) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape(trim(($project->league_name ?? '') . ' · ' . ($project->season_name ?? ''), ' ·')); ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULT_MATRIX'); ?></h1>
		<p class="jl-site-muted mb-3"><?php echo $this->escape($project->name); ?></p>
		<nav class="jl-site-nav">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=ranking&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=results&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
		</nav>
	</section>
	<div class="jl-site-panel table-responsive">
		<table class="table jl-site-table align-middle">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
					<?php foreach ($this->teams as $team) : ?>
						<th><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id); ?>"><?php echo $this->escape($team->team_short_name ?: $team->team_name); ?></a></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->teams as $homeTeam) : ?>
					<tr>
						<th><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $homeTeam->id); ?>"><?php echo $this->escape($homeTeam->team_name); ?></a></th>
						<?php foreach ($this->teams as $awayTeam) : ?>
							<?php $cell = $this->matrix[(int) $homeTeam->id][(int) $awayTeam->id] ?? null; ?>
							<td>
								<?php if ((int) $homeTeam->id === (int) $awayTeam->id) : ?>
									<span class="jl-site-muted">-</span>
								<?php elseif ($cell) : ?>
									<a class="jl-site-score" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $cell->id); ?>"><?php echo $this->escape((string) $cell->home_result . ' : ' . (string) $cell->away_result); ?></a>
								<?php else : ?>
									<span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED'); ?></span>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!$this->teams) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
	</div>
</div>
