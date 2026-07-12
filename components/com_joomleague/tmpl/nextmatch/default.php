<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$match = $this->item;
$comparison = $this->matchTeamComparison;
?>
<div class="com-joomleague-site">
	<?php if (!$match) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NEXT_MATCH_NOT_FOUND'); ?></div>
		<?php return; ?>
	<?php endif; ?>

	<nav class="jl-site-nav mb-4">
		<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $match->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></a>
		<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $match->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
		<a href="<?php echo Route::_('index.php?option=com_joomleague&view=ranking&project_id=' . (int) $match->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></a>
	</nav>

	<?php
	echo LayoutHelper::render(
		'joomleague.match.detail',
		[
			'match'    => $match,
			'events'   => $this->items,
			'referees' => $this->matchReferees,
			'options'  => ['events' => false, 'heading' => 'h1'],
		],
		JPATH_SITE . '/components/com_joomleague/layouts'
	);
	?>

	<?php if (!empty($match->preview)) : ?>
		<div class="jl-site-panel mt-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREVIEW'); ?></h2>
			<div class="jl-site-richtext"><?php echo HTMLHelper::_('content.prepare', $match->preview); ?></div>
		</div>
	<?php endif; ?>

	<?php if ($this->homeForm !== [] || $this->awayForm !== []) : ?>
		<div class="jl-site-panel mt-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_RECENT_FORM'); ?></h2>
			<div class="jl-nm-form-grid">
				<?php foreach ([[$match->home_name ?? Text::_('COM_JOOMLEAGUE_SITE_HOME'), $this->homeForm], [$match->away_name ?? Text::_('COM_JOOMLEAGUE_SITE_AWAY'), $this->awayForm]] as $block) : ?>
					<div class="jl-nm-form-col">
						<h3><?php echo $this->escape((string) $block[0]); ?></h3>
						<?php if ($block[1] === []) : ?>
							<p class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></p>
						<?php else : ?>
							<ul class="jl-nm-form-list">
								<?php foreach ($block[1] as $game) : ?>
									<li>
										<span class="jl-nm-badge jl-nm-<?php echo $this->escape($game->form_result); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_FORM_' . strtoupper($game->form_result)); ?></span>
										<a class="jl-nm-opp" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $game->id); ?>"><?php echo $this->escape($game->form_opponent); ?></a>
										<span class="jl-site-score"><?php echo $this->escape($game->form_score); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($comparison !== []) : ?>
		<div class="jl-site-panel table-responsive mt-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_COMPARISON'); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th><?php echo $this->escape($match->home_name ?? Text::_('COM_JOOMLEAGUE_SITE_HOME')); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_STATISTIC'); ?></th>
						<th><?php echo $this->escape($match->away_name ?? Text::_('COM_JOOMLEAGUE_SITE_AWAY')); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo $comparison['home']['rank'] ?? Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></td>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></th>
						<td><?php echo $comparison['away']['rank'] ?? Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></td>
					</tr>
					<tr>
						<td><?php echo (int) ($comparison['home']['stats']['played'] ?? 0); ?></td>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED_MATCHES'); ?></th>
						<td><?php echo (int) ($comparison['away']['stats']['played'] ?? 0); ?></td>
					</tr>
					<tr>
						<td><?php echo (int) ($comparison['home']['stats']['wins'] ?? 0); ?> / <?php echo (int) ($comparison['home']['stats']['draws'] ?? 0); ?> / <?php echo (int) ($comparison['home']['stats']['losses'] ?? 0); ?></td>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_WIN_DRAW_LOSS'); ?></th>
						<td><?php echo (int) ($comparison['away']['stats']['wins'] ?? 0); ?> / <?php echo (int) ($comparison['away']['stats']['draws'] ?? 0); ?> / <?php echo (int) ($comparison['away']['stats']['losses'] ?? 0); ?></td>
					</tr>
					<tr>
						<td><?php echo $this->escape((string) ($comparison['home']['stats']['goals_for'] ?? 0) . ':' . (string) ($comparison['home']['stats']['goals_against'] ?? 0)); ?></td>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOALS'); ?></th>
						<td><?php echo $this->escape((string) ($comparison['away']['stats']['goals_for'] ?? 0) . ':' . (string) ($comparison['away']['stats']['goals_against'] ?? 0)); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if ($this->headToHeadMatches) : ?>
		<div class="jl-site-panel table-responsive mt-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_HEAD_TO_HEAD'); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCORE'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_AWAY'); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->headToHeadMatches as $previousMatch) : ?>
						<tr>
							<td><?php echo $this->escape($previousMatch->match_date ? date('d.m.Y H:i', strtotime((string) $previousMatch->match_date)) : ''); ?></td>
							<td><?php echo $this->escape($previousMatch->home_name ?? ''); ?></td>
							<td><span class="jl-site-score"><?php echo $this->escape((string) $previousMatch->team1_result . ' : ' . (string) $previousMatch->team2_result); ?></span></td>
							<td><?php echo $this->escape($previousMatch->away_name ?? ''); ?></td>
							<td><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $previousMatch->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
