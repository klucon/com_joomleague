<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_teamstaffs
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Staff rows.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-teamstaffs-empty">' . Text::_('MOD_JOOMLEAGUE_TEAMSTAFFS_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-teamstaffs">
	<ul class="list-unstyled mb-0">
		<?php foreach ($list as $s) : ?>
			<li class="d-flex justify-content-between py-1 border-bottom">
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $s->person_id); ?>">
					<?php echo htmlspecialchars($s->person_name, ENT_QUOTES, 'UTF-8'); ?>
				</a>
				<?php if (!empty($s->position_name)) : ?>
					<span class="text-muted small"><?php echo htmlspecialchars($s->position_name, ENT_QUOTES, 'UTF-8'); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
