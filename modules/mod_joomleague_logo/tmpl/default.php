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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-logo-empty">' . Text::_('MOD_JOOMLEAGUE_LOGO_NO_DATA') . '</div>';

    return;
}

$row  = $list[0];
$logo = $row->logo_big ?: ($row->logo_middle ?: ($row->logo_small ?: ($row->team_picture ?: ComponentHelper::getParams('com_joomleague')->get('placeholder_club_logo', ''))));
$url  = Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $row->projectteam_id);
$nameType = (string) $params->get('nametype', 'name');
$teamName = match ($nameType) {
    'short_name' => (string) ($row->team_short_name ?: $row->team_name),
    'middle_name' => (string) ($row->team_middle_name ?: $row->team_name),
    default => (string) $row->team_name,
};
?>
<div class="jl-module jl-logo text-center">
	<?php if ((int) $params->get('show_project_name', 0) === 1 && !empty($row->project_name)) : ?>
		<div class="jl-logo-project text-muted small mb-2"><?php echo htmlspecialchars((string) $row->project_name, ENT_QUOTES, 'UTF-8'); ?></div>
	<?php endif; ?>
	<a href="<?php echo $url; ?>" title="<?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?>" class="jl-logo-link text-decoration-none">
		<?php if (!empty($logo)) : ?>
			<img class="jl-logo-img img-fluid" src="<?php echo htmlspecialchars($logo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?>">
		<?php else : ?>
			<span class="jl-logo-placeholder" aria-hidden="true"></span>
		<?php endif; ?>
		<span class="jl-logo-name d-block fw-semibold mt-2"><?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?></span>
	</a>
</div>
<style>
	.jl-logo .jl-logo-link {
		color: inherit;
	}

	.jl-logo .jl-logo-img {
		display: inline-block;
		width: auto;
		max-width: min(100%, 120px);
		max-height: 120px;
		object-fit: contain;
	}

	.jl-logo .jl-logo-placeholder {
		display: inline-block;
		width: 120px;
		height: 120px;
		border: 1px solid color-mix(in srgb, currentColor 18%, transparent);
		border-radius: .5rem;
		background: color-mix(in srgb, currentColor 5%, transparent);
	}
</style>
