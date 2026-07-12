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
<?php if (!$this->matches) : ?>
	<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_MATCHES'); ?></div>
<?php else : ?>
	<div class="table-responsive">
		<table class="table jl-site-table align-middle">
			<thead><tr><?php if ($show('show_date')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></th><?php endif; ?><?php if ($show('show_round')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROUND'); ?></th><?php endif; ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME'); ?></th><?php if ($show('show_score')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCORE'); ?></th><?php endif; ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_AWAY'); ?></th><?php if ($show('show_venue')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></th><?php endif; ?><?php if ($show('show_detail_link')) : ?><th></th><?php endif; ?></tr></thead>
			<tbody>
				<?php foreach ($this->matches as $match) : ?>
					<tr>
						<?php if ($show('show_date')) : ?><td><?php echo $this->escape($match->match_date ? date('d.m.Y H:i', strtotime((string) $match->match_date)) : ''); ?></td><?php endif; ?>
						<?php if ($show('show_round')) : ?><td><?php echo $this->escape($match->round_name ?? ''); ?></td><?php endif; ?>
						<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $match->home_projectteam_id); ?>"><?php echo $this->escape($match->home_name ?? ''); ?></a></td>
						<?php if ($show('show_score')) : ?><td><span class="jl-site-score"><?php echo $match->team1_result === null || $match->team2_result === null ? Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED') : $this->escape((string) $match->team1_result . ' : ' . (string) $match->team2_result); ?></span></td><?php endif; ?>
						<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $match->away_projectteam_id); ?>"><?php echo $this->escape($match->away_name ?? ''); ?></a></td>
						<?php if ($show('show_venue')) : ?><td><?php echo $this->escape($match->playground_name ?? ''); ?></td><?php endif; ?>
						<?php if ($show('show_detail_link')) : ?><td><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a></td><?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
