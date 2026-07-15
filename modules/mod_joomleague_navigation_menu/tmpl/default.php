<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_navigation_menu
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $data    Navigation data.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

if (empty($data['projects'])) {
    echo '<div class="jl-module jl-nav-empty">' . Text::_('MOD_JOOMLEAGUE_NAVIGATION_MENU_NO_DATA') . '</div>';

    return;
}
?>
<div class="jl-module jl-navigation">
	<nav class="jl-nav-projects">
		<label class="fw-bold d-block"><?php echo Text::_('MOD_JOOMLEAGUE_NAVIGATION_MENU_COMPETITION'); ?></label>
		<ul class="list-unstyled mb-2">
			<?php foreach ($data['projects'] as $project) : ?>
				<li<?php echo (int) $project->id === (int) $data['active'] ? ' class="fw-bold"' : ''; ?>>
					<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $project->id); ?>">
						<?php echo htmlspecialchars($project->name, ENT_QUOTES, 'UTF-8'); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php if (!empty($data['teams'])) : ?>
		<nav class="jl-nav-teams">
			<label class="fw-bold d-block"><?php echo Text::_('MOD_JOOMLEAGUE_NAVIGATION_MENU_TEAMS'); ?></label>
			<ul class="list-unstyled mb-0">
				<?php foreach ($data['teams'] as $team) : ?>
					<li>
						<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id); ?>">
							<?php echo htmlspecialchars($team->team_name, ENT_QUOTES, 'UTF-8'); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>
</div>
