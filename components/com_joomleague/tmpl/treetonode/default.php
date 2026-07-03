<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$tree = $this->tree;
$project = $this->project;
$nodesByLevel = [];
$maxLevel = 0;

foreach ($this->treeNodes as $node) {
	$level = (int) floor(log(max(1, (int) $node->node), 2)) + 1;
	$nodesByLevel[$level][] = $node;
	$maxLevel = max($maxLevel, $level);
}

ksort($nodesByLevel);
?>
<div class="com-joomleague-site">
	<?php if (!$tree) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TREE_NOT_FOUND'); ?></div>
		<?php return; ?>
	<?php endif; ?>

	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape($tree->project_name ?? ''); ?></div>
		<h1 class="jl-site-title"><?php echo $this->escape($tree->name ?: Text::_('COM_JOOMLEAGUE_SITE_TREE')); ?></h1>
		<p class="jl-site-muted mb-3">
			<?php echo $this->escape(trim(implode(' · ', array_filter([$tree->league_name ?? '', $tree->season_name ?? '', $tree->division_name ?? ''])))); ?>
		</p>
		<nav class="jl-site-nav">
			<?php if ($project) : ?>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=results&project_id=' . (int) $project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS'); ?></a>
			<?php endif; ?>
		</nav>
	</section>

	<div class="jl-site-grid mb-4">
		<div class="jl-site-card"><strong><?php echo (int) $tree->tree_i; ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TREE_DEPTH'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) $tree->node_count; ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TREE_NODES'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) $tree->global_bestof; ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_BEST_OF'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $tree->leafed ? Text::_('JYES') : Text::_('JNO'); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TREE_GENERATED'); ?></span></div>
	</div>

	<?php if ($this->treeNodes === []) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TREE_EMPTY'); ?></div>
		<?php return; ?>
	<?php endif; ?>

	<div class="jl-site-panel">
		<div class="jl-site-bracket" style="--jl-tree-levels: <?php echo max(1, $maxLevel); ?>;">
			<?php foreach ($nodesByLevel as $level => $nodes) : ?>
				<section class="jl-site-bracket-round">
					<h2>
						<?php
						$roundTitles = [
							1 => 'COM_JOOMLEAGUE_SITE_TREE_ROUND_FINAL',
							2 => 'COM_JOOMLEAGUE_SITE_TREE_ROUND_SEMIFINAL',
							3 => 'COM_JOOMLEAGUE_SITE_TREE_ROUND_QUARTERFINAL',
						];
						$roundTitle = $roundTitles[$level] ?? 'COM_JOOMLEAGUE_SITE_TREE_ROUND';
						echo Text::_($roundTitle);
						?>
						<span><?php echo (int) count($nodes); ?></span>
					</h2>

					<?php foreach ($nodes as $node) : ?>
						<article class="jl-site-bracket-node<?php echo $node->is_ready ? ' is-ready' : ''; ?><?php echo $node->is_lock ? ' is-locked' : ''; ?>">
							<div class="jl-site-bracket-node-head">
								<strong><?php echo $this->escape($node->title ?: Text::_('COM_JOOMLEAGUE_SITE_TREE_NODE') . ' ' . (int) $node->row); ?></strong>
								<span><?php echo Text::_('COM_JOOMLEAGUE_SITE_BEST_OF'); ?> <?php echo (int) $node->bestof; ?></span>
							</div>

							<div class="jl-site-bracket-team">
								<?php if ((int) $node->projectteam_id > 0) : ?>
									<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $node->projectteam_id); ?>"><?php echo $this->escape($node->team_name ?: $node->team_short_name ?: $node->team_middle_name); ?></a>
								<?php else : ?>
									<span><?php echo $this->escape($node->content ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></span>
								<?php endif; ?>
							</div>

							<?php if (!empty($node->matches_summary)) : ?>
								<ul class="jl-site-bracket-matches">
									<?php foreach (explode('|', (string) $node->matches_summary) as $summary) : ?>
										<?php [$matchId, $matchDate, $homeName, $awayName, $homeScore, $awayScore] = array_pad(explode('~', $summary), 6, ''); ?>
										<?php if ((int) $matchId <= 0) : continue; endif; ?>
										<li>
											<a href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $matchId); ?>">
												<span><?php echo $this->escape(trim($homeName . ' - ' . $awayName)); ?></span>
												<strong><?php echo $homeScore !== '' && $awayScore !== '' ? $this->escape($homeScore . ':' . $awayScore) : Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></strong>
											</a>
											<?php if ($matchDate !== '') : ?><small><?php echo $this->escape(date('d.m.Y', strtotime($matchDate))); ?></small><?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</section>
			<?php endforeach; ?>
		</div>
	</div>
</div>
