<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_results
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
    echo '<div class="jl-module jl-results-empty">' . Text::_('MOD_JOOMLEAGUE_RESULTS_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-results">
	<ul class="jl-results-list list-unstyled mb-0">
		<?php foreach ($list as $m) : ?>
			<li class="jl-result d-flex justify-content-between align-items-center py-1 border-bottom">
				<span class="jl-date text-muted small me-2"><?php echo HTMLHelper::_('date', $m->match_date, 'j.n.'); ?></span>
				<span class="jl-home flex-grow-1 text-end"><?php echo htmlspecialchars($m->home_name ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
				<a class="jl-score fw-bold mx-2" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $m->id); ?>">
					<?php echo (int) $m->team1_result . ':' . (int) $m->team2_result; ?>
				</a>
				<span class="jl-away flex-grow-1"><?php echo htmlspecialchars($m->away_name ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
