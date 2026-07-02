<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_calendar
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $data    Map of day => matches.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

if (empty($data)) {
    echo '<div class="jl-module jl-calendar-empty">' . Text::_('MOD_JOOMLEAGUE_CALENDAR_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-calendar">
	<?php foreach ($data as $day => $matches) : ?>
		<div class="jl-cal-day mb-2">
			<div class="jl-cal-date fw-bold border-bottom"><?php echo HTMLHelper::_('date', $day, 'l j. F Y'); ?></div>
			<ul class="list-unstyled mb-0">
				<?php foreach ($matches as $m) :
					$played = $m->team1_result !== null && $m->team2_result !== null && (int) $m->count_result === 1;
				?>
					<li class="d-flex justify-content-between py-1">
						<span class="text-end flex-grow-1"><?php echo htmlspecialchars($m->home_name ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
						<a class="mx-2 fw-bold" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $m->id); ?>">
							<?php echo $played ? ((int) $m->team1_result . ':' . (int) $m->team2_result) : HTMLHelper::_('date', $m->match_date, 'H:i'); ?>
						</a>
						<span class="flex-grow-1"><?php echo htmlspecialchars($m->away_name ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</div>
