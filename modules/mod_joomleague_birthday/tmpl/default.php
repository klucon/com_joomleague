<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_birthday
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Birthday rows.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-birthday-empty">' . Text::_('MOD_JOOMLEAGUE_BIRTHDAY_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-birthday">
	<ul class="list-unstyled mb-0">
		<?php foreach ($list as $row) : ?>
			<li class="d-flex justify-content-between py-1 border-bottom">
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $row->person_id); ?>">
					<?php echo htmlspecialchars($row->person_name, ENT_QUOTES, 'UTF-8'); ?>
				</a>
				<span class="text-muted small"><?php echo HTMLHelper::_('date', $row->next_date, 'j.n.'); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
