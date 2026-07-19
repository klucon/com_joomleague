<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_randomplayer
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  ?object                    $item    The random player.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

Factory::getApplication()->getLanguage()->load('com_joomleague', JPATH_SITE);

if (empty($item)) {
    echo '<div class="jl-module jl-randomplayer-empty">' . Text::_('MOD_JOOMLEAGUE_RANDOMPLAYER_NO_DATA') . '</div>';

    return;
}

$personPicture = trim((string) ($item->person_picture ?? ''));
if ($personPicture === '') {
    $personPicture = trim((string) ComponentHelper::getParams('com_joomleague')->get('placeholder_person_picture', ''));
}
?>
<div class="jl-module jl-randomplayer text-center">
	<?php if ($personPicture !== '') : ?>
		<img class="jl-player-photo img-fluid mb-2" src="<?php echo htmlspecialchars($personPicture, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item->person_name, ENT_QUOTES, 'UTF-8'); ?>">
	<?php endif; ?>
	<div class="jl-player-name fw-bold">
		<a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $item->person_id); ?>">
			<?php echo htmlspecialchars($item->person_name, ENT_QUOTES, 'UTF-8'); ?>
		</a>
	</div>
	<?php if (!empty($item->position_name)) : ?>
		<div class="jl-player-position text-muted small"><?php echo htmlspecialchars(Text::_((string) $item->position_name), ENT_QUOTES, 'UTF-8'); ?></div>
	<?php endif; ?>
</div>
<style>
	.jl-randomplayer .jl-player-photo {
		display: inline-block;
		width: auto;
		max-width: min(100%, 120px);
		max-height: 140px;
		object-fit: contain;
	}
</style>
