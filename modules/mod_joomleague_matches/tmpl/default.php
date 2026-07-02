<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_matches
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Match rows.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-matches-empty">' . Text::_('MOD_JOOMLEAGUE_MATCHES_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-matches">
	<table class="table table-sm jl-matches-table mb-0">
		<tbody>
		<?php foreach ($list as $m) :
			$played = $m->team1_result !== null && $m->team2_result !== null && (int) $m->count_result === 1;
		?>
			<tr>
				<td class="text-muted small text-nowrap"><?php echo HTMLHelper::_('date', $m->match_date, 'j.n. H:i'); ?></td>
				<td class="text-end"><?php echo htmlspecialchars($m->home_name ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
				<td class="text-center fw-bold text-nowrap">
					<a href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $m->id); ?>">
						<?php echo $played ? ((int) $m->team1_result . ':' . (int) $m->team2_result) : '–'; ?>
					</a>
				</td>
				<td><?php echo htmlspecialchars($m->away_name ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
