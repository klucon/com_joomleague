<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$match = $this->item;
?>
<div class="com-joomleague-site">
	<?php if ($match) : ?>
		<nav class="jl-site-nav mb-4">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $match->projectteam1_id); ?>"><?php echo $this->escape($match->home_name ?? Text::_('COM_JOOMLEAGUE_SITE_HOME')); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $match->projectteam2_id); ?>"><?php echo $this->escape($match->away_name ?? Text::_('COM_JOOMLEAGUE_SITE_AWAY')); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $match->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=results&project_id=' . (int) $match->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS'); ?></a>
		</nav>
	<?php endif; ?>

	<?php
	echo LayoutHelper::render(
		'joomleague.match.detail',
		[
			'match'   => $match,
			'events'  => $this->items,
			'options' => ['link' => false, 'heading' => 'h1'],
		],
		JPATH_SITE . '/components/com_joomleague/layouts'
	);
	?>

	<?php if ($match && $this->headToHeadMatches) : ?>
		<div class="jl-site-panel table-responsive mt-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_HEAD_TO_HEAD'); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></th>
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
							<td><?php echo $this->escape($previousMatch->project_name ?? ''); ?></td>
							<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $previousMatch->home_projectteam_id); ?>"><?php echo $this->escape($previousMatch->home_name ?? ''); ?></a></td>
							<td><span class="jl-site-score"><?php echo $this->escape((string) $previousMatch->team1_result . ' : ' . (string) $previousMatch->team2_result); ?></span></td>
							<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $previousMatch->away_projectteam_id); ?>"><?php echo $this->escape($previousMatch->away_name ?? ''); ?></a></td>
							<td><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $previousMatch->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
