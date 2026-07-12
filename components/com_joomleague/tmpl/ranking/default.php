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

$params = $this->templateParams;
$show = static fn (string $name, bool $default = true): bool => array_key_exists($name, $params) ? (bool) $params[$name] : $default;
?>
<div class="com-joomleague-site">
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->project ? $this->escape($this->project->name) : ''; ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></h1>
	</section>
	<div class="jl-site-panel table-responsive">
		<table class="table jl-site-table align-middle">
			<thead><tr><?php if ($show('show_rank')) : ?><th>#</th><?php endif; ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th><?php if ($show('show_played')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED_SHORT'); ?></th><?php endif; ?><?php if ($show('show_won')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_WON_SHORT'); ?></th><?php endif; ?><?php if ($show('show_drawn')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DRAWN_SHORT'); ?></th><?php endif; ?><?php if ($show('show_lost')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_LOST_SHORT'); ?></th><?php endif; ?><?php if ($show('show_goals')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOALS'); ?></th><?php endif; ?><?php if ($show('show_goal_difference')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOAL_DIFFERENCE_SHORT'); ?></th><?php endif; ?><?php if ($show('show_points')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POINTS'); ?></th><?php endif; ?></tr></thead>
			<tbody>
				<?php foreach ($this->standings as $i => $row) : ?>
					<tr>
						<?php if ($show('show_rank')) : ?><td><?php echo $i + 1; ?></td><?php endif; ?>
						<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $row->projectteam_id); ?>"><?php echo $this->escape($row->team_name); ?></a></td>
						<?php if ($show('show_played')) : ?><td><?php echo (int) $row->played; ?></td><?php endif; ?>
						<?php if ($show('show_won')) : ?><td><?php echo (int) $row->won; ?></td><?php endif; ?>
						<?php if ($show('show_drawn')) : ?><td><?php echo (int) $row->drawn; ?></td><?php endif; ?>
						<?php if ($show('show_lost')) : ?><td><?php echo (int) $row->lost; ?></td><?php endif; ?>
						<?php if ($show('show_goals')) : ?><td><?php echo $this->escape((string) (int) $row->goals_for . ':' . (string) (int) $row->goals_against); ?></td><?php endif; ?>
						<?php if ($show('show_goal_difference')) : ?><td><?php echo ((int) $row->goal_diff > 0 ? '+' : '') . (int) $row->goal_diff; ?></td><?php endif; ?>
						<?php if ($show('show_points')) : ?><td><strong><?php echo (int) $row->points; ?></strong></td><?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!$this->standings) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
	</div>
</div>
