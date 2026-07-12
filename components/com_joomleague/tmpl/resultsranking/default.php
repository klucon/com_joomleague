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

$project = $this->project;
?>
<div class="com-joomleague-site">
	<?php if (!$project) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape($project->name); ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS_RANKING'); ?></h1>
	</section>

	<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS'); ?></h2>
	<?php require JPATH_COMPONENT . '/tmpl/results/matches_grouped.php'; ?>

	<div class="jl-site-panel table-responsive">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></h2>
		<table class="table jl-site-table align-middle">
			<thead>
				<tr>
					<th>#</th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED_SHORT'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_WON_SHORT'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DRAWN_SHORT'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_LOST_SHORT'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOALS'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOAL_DIFFERENCE_SHORT'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POINTS'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->standings as $i => $row) : ?>
					<tr>
						<td><?php echo $i + 1; ?></td>
						<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $row->projectteam_id); ?>"><?php echo $this->escape($row->team_name); ?></a></td>
						<td><?php echo (int) $row->played; ?></td>
						<td><?php echo (int) $row->won; ?></td>
						<td><?php echo (int) $row->drawn; ?></td>
						<td><?php echo (int) $row->lost; ?></td>
						<td><?php echo $this->escape((string) (int) $row->goals_for . ':' . (string) (int) $row->goals_against); ?></td>
						<td><?php echo ((int) $row->goal_diff > 0 ? '+' : '') . (int) $row->goal_diff; ?></td>
						<td><strong><?php echo (int) $row->points; ?></strong></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!$this->standings) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
	</div>
</div>
