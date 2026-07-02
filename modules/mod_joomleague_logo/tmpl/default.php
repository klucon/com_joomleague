<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_logo
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Single-row logo list.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-logo-empty">' . Text::_('MOD_JOOMLEAGUE_LOGO_NO_DATA') . '</div>';

    return;
}

$row  = $list[0];
$logo = $row->logo_big ?: ($row->logo_middle ?: ($row->logo_small ?: $row->team_picture));
$url  = Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $row->projectteam_id);
?>
<div class="jl-module jl-logo text-center">
	<a href="<?php echo $url; ?>" title="<?php echo htmlspecialchars($row->team_name, ENT_QUOTES, 'UTF-8'); ?>">
		<?php if (!empty($logo)) : ?>
			<img class="jl-logo-img img-fluid" src="<?php echo htmlspecialchars($logo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($row->team_name, ENT_QUOTES, 'UTF-8'); ?>">
		<?php else : ?>
			<span class="jl-logo-name fw-bold"><?php echo htmlspecialchars($row->team_name, ENT_QUOTES, 'UTF-8'); ?></span>
		<?php endif; ?>
	</a>
</div>
