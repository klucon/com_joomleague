<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="com-joomleague-site">
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->project ? $this->escape($this->project->name) : ''; ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></h1>
	</section>
	<div class="jl-site-panel table-responsive">
		<table class="table jl-site-table align-middle">
			<thead><tr><th>#</th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED_SHORT'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_WON_SHORT'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DRAWN_SHORT'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_LOST_SHORT'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOALS'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POINTS'); ?></th></tr></thead>
			<tbody>
				<?php foreach ($this->standings as $i => $row) : ?>
					<tr>
						<td><?php echo $i + 1; ?></td>
						<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $row->projectteam_id); ?>"><?php echo $this->escape($row->team_name); ?></a></td>
						<td><?php echo (int) $row->played; ?></td>
						<td><?php echo (int) $row->won; ?></td>
						<td><?php echo (int) $row->drawn; ?></td>
						<td><?php echo (int) $row->lost; ?></td>
						<td><?php echo $this->escape((string) $row->goals_for . ':' . (string) $row->goals_against); ?></td>
						<td><strong><?php echo (int) $row->points; ?></strong></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!$this->standings) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
	</div>
</div>
