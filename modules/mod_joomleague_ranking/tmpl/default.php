<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_ranking
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                          $list    Standings rows.
 * @var  \Joomla\Registry\Registry      $params  Module parameters.
 * @var  object                         $module  Module record.
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-ranking-empty">' . Text::_('MOD_JOOMLEAGUE_RANKING_NO_DATA') . '</div>';

    return;
}

$highlight = (int) $params->get('highlight_team', 0);
?>
<div class="jl-module jl-ranking table-responsive">
	<table class="table table-sm jl-ranking-table">
		<thead>
			<tr>
				<th class="text-end">#</th>
				<th><?php echo Text::_('MOD_JOOMLEAGUE_RANKING_TEAM'); ?></th>
				<th class="text-center" title="<?php echo Text::_('MOD_JOOMLEAGUE_RANKING_PLAYED'); ?>"><?php echo Text::_('MOD_JOOMLEAGUE_RANKING_PLAYED_SHORT'); ?></th>
				<th class="text-center">V</th>
				<th class="text-center">R</th>
				<th class="text-center">P</th>
				<th class="text-center">±</th>
				<th class="text-center"><?php echo Text::_('MOD_JOOMLEAGUE_RANKING_POINTS_SHORT'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($list as $i => $row) : ?>
				<tr<?php echo ($highlight && (int) $row->projectteam_id === $highlight) ? ' class="table-active fw-bold"' : ''; ?>>
					<td class="text-end"><?php echo $i + 1; ?></td>
					<td>
						<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $row->projectteam_id); ?>">
							<?php echo htmlspecialchars($row->team_name, ENT_QUOTES, 'UTF-8'); ?>
						</a>
					</td>
					<td class="text-center"><?php echo (int) $row->played; ?></td>
					<td class="text-center"><?php echo (int) $row->won; ?></td>
					<td class="text-center"><?php echo (int) $row->drawn; ?></td>
					<td class="text-center"><?php echo (int) $row->lost; ?></td>
					<td class="text-center"><?php echo (int) $row->goals_for . ':' . (int) $row->goals_against; ?></td>
					<td class="text-center fw-bold"><?php echo (int) $row->points; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
