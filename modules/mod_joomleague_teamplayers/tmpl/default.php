<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_teamplayers
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Roster rows.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-teamplayers-empty">' . Text::_('MOD_JOOMLEAGUE_TEAMPLAYERS_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-teamplayers">
	<ul class="list-unstyled mb-0">
		<?php foreach ($list as $p) : ?>
			<li class="d-flex py-1 border-bottom">
				<span class="jl-jersey text-muted me-2" style="min-width:1.8em;text-align:right;"><?php echo $p->jerseynumber !== null ? (int) $p->jerseynumber : ''; ?></span>
				<a class="flex-grow-1" href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $p->person_id); ?>">
					<?php echo htmlspecialchars($p->person_name, ENT_QUOTES, 'UTF-8'); ?>
				</a>
				<?php if (!empty($p->position_name)) : ?>
					<span class="text-muted small"><?php echo htmlspecialchars(Text::_((string) $p->position_name), ENT_QUOTES, 'UTF-8'); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
