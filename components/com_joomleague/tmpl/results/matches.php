<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<?php if (!$this->matches) : ?>
	<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_MATCHES'); ?></div>
<?php else : ?>
	<div class="table-responsive">
		<table class="table jl-site-table align-middle">
			<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROUND'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCORE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_AWAY'); ?></th><th></th></tr></thead>
			<tbody>
				<?php foreach ($this->matches as $match) : ?>
					<tr>
						<td><?php echo $this->escape($match->match_date ? date('d.m.Y H:i', strtotime((string) $match->match_date)) : ''); ?></td>
						<td><?php echo $this->escape($match->round_name ?? ''); ?></td>
						<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $match->home_projectteam_id); ?>"><?php echo $this->escape($match->home_name ?? ''); ?></a></td>
						<td><span class="jl-site-score"><?php echo $match->team1_result === null || $match->team2_result === null ? Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED') : $this->escape((string) $match->team1_result . ' : ' . (string) $match->team2_result); ?></span></td>
						<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $match->away_projectteam_id); ?>"><?php echo $this->escape($match->away_name ?? ''); ?></a></td>
						<td><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
