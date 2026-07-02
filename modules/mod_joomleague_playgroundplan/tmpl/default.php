<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_playgroundplan
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Playground rows.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-playgroundplan-empty">' . Text::_('MOD_JOOMLEAGUE_PLAYGROUNDPLAN_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-playgroundplan">
	<ul class="list-unstyled mb-0">
		<?php foreach ($list as $pg) :
			$hasGeo = !empty($pg->latitude) && !empty($pg->longitude);
		?>
			<li class="jl-playground py-2 border-bottom">
				<div class="fw-bold"><?php echo htmlspecialchars($pg->name, ENT_QUOTES, 'UTF-8'); ?></div>
				<?php if (!empty($pg->address)) : ?>
					<div class="text-muted small"><?php echo nl2br(htmlspecialchars($pg->address, ENT_QUOTES, 'UTF-8')); ?></div>
				<?php endif; ?>
				<?php if ($hasGeo) : ?>
					<a class="small" target="_blank" rel="noopener"
						href="https://www.openstreetmap.org/?mlat=<?php echo (float) $pg->latitude; ?>&mlon=<?php echo (float) $pg->longitude; ?>#map=16/<?php echo (float) $pg->latitude; ?>/<?php echo (float) $pg->longitude; ?>">
						<?php echo Text::_('MOD_JOOMLEAGUE_PLAYGROUNDPLAN_SHOW_MAP'); ?>
					</a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
