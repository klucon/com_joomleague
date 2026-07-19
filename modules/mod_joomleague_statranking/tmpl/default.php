<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_statranking
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Map of statistic => rows.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-statranking-empty">' . Text::_('MOD_JOOMLEAGUE_STATRANKING_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-statranking">
	<?php foreach ($list as $statName => $rows) : ?>
		<div class="jl-stat-block mb-2">
			<div class="jl-stat-name fw-bold border-bottom"><?php echo htmlspecialchars(Text::_($statName), ENT_QUOTES, 'UTF-8'); ?></div>
			<table class="table table-sm mb-0">
				<tbody>
				<?php foreach ($rows as $i => $row) : ?>
					<tr>
						<td class="text-end text-muted"><?php echo $i + 1; ?>.</td>
						<td><?php echo htmlspecialchars($row->person_name, ENT_QUOTES, 'UTF-8'); ?></td>
						<td class="text-end fw-bold"><?php echo (int) $row->value; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endforeach; ?>
</div>
