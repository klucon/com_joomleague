<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_eventsranking
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Ranking rows.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-eventsranking-empty">' . Text::_('MOD_JOOMLEAGUE_EVENTSRANKING_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-eventsranking">
	<table class="table table-sm mb-0">
		<tbody>
		<?php foreach ($list as $i => $row) : ?>
			<tr>
				<td class="text-end text-muted"><?php echo $i + 1; ?>.</td>
				<td>
					<a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $row->person_id); ?>">
						<?php echo htmlspecialchars($row->person_name, ENT_QUOTES, 'UTF-8'); ?>
					</a>
					<?php if (!empty($row->team_name)) : ?>
						<span class="text-muted small">(<?php echo htmlspecialchars($row->team_name, ENT_QUOTES, 'UTF-8'); ?>)</span>
					<?php endif; ?>
				</td>
				<td class="text-end fw-bold"><?php echo (int) $row->total; ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
