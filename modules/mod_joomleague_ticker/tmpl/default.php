<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_ticker
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
    echo '<div class="jl-module jl-ticker-empty">' . Text::_('MOD_JOOMLEAGUE_TICKER_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-ticker overflow-auto">
	<ul class="jl-ticker-list list-unstyled d-flex flex-nowrap gap-3 mb-0">
		<?php foreach ($list as $m) :
			$played = $m->team1_result !== null && $m->team2_result !== null && (int) $m->count_result === 1;
		?>
			<li class="jl-ticker-item text-nowrap">
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $m->id); ?>">
					<span class="text-muted small"><?php echo HTMLHelper::_('date', $m->match_date, 'j.n.'); ?></span>
					<?php echo htmlspecialchars($m->home_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
					<strong class="mx-1"><?php echo $played ? ((int) $m->team1_result . ':' . (int) $m->team2_result) : '–'; ?></strong>
					<?php echo htmlspecialchars($m->away_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
