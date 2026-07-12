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

$team = $this->item;
?>
<div class="com-joomleague-site">
	<?php if (!$team) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape($team->club_name ?? ''); ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RIVALS'); ?></h1>
		<p class="jl-site-muted mb-3"><?php echo $this->escape($team->team_name); ?></p>
		<nav class="jl-site-nav">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=teamstats&projectteam_id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_STATS'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $team->project_id . '&projectteam_id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
		</nav>
	</section>

	<div class="jl-site-panel table-responsive">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_RIVALS'); ?></h2>
		<table class="table jl-site-table align-middle">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_WON_SHORT'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DRAWN_SHORT'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_LOST_SHORT'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOALS'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_LAST_MATCH'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->rivals as $rival) : ?>
					<tr>
						<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $rival->projectteam_id); ?>"><?php echo $this->escape($rival->team_name); ?></a></td>
						<td><?php echo (int) $rival->matches; ?></td>
						<td><?php echo (int) $rival->wins; ?></td>
						<td><?php echo (int) $rival->draws; ?></td>
						<td><?php echo (int) $rival->losses; ?></td>
						<td><?php echo $this->escape((string) $rival->goals_for . ':' . (string) $rival->goals_against); ?></td>
						<td><?php if ($rival->last_match_id) : ?><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $rival->last_match_id); ?>"><?php echo $this->escape($rival->last_match_date ? date('d.m.Y', strtotime((string) $rival->last_match_date)) : Text::_('COM_JOOMLEAGUE_SITE_DETAIL')); ?></a><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!$this->rivals) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
	</div>
</div>
