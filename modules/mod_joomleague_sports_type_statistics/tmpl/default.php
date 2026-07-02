<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_sports_type_statistics
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Statistic totals.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-sportstats-empty">' . Text::_('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-sportstats">
	<table class="table table-sm mb-0">
		<tbody>
		<?php foreach ($list as $row) : ?>
			<tr>
				<td><?php echo htmlspecialchars($row->statistic_name, ENT_QUOTES, 'UTF-8'); ?></td>
				<td class="text-end fw-bold"><?php echo (int) $row->value; ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
