<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Section\HtmlView $this */

$dashboardStyle = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.5.4';

?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($dashboardStyle, ENT_QUOTES, 'UTF-8'); ?>">
<div class="com-joomleague-section">
	<a class="jl-section-back" href="<?php echo Route::_('index.php?option=com_joomleague&view=dashboard'); ?>">
		<span class="icon-arrow-left" aria-hidden="true"></span>
		<?php echo Text::_('COM_JOOMLEAGUE_SECTION_BACK_TO_DASHBOARD'); ?>
	</a>

	<section class="jl-section-panel mt-3">
		<div class="jl-section-panel__icon <?php echo htmlspecialchars($this->section['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></div>
		<div class="jl-section-panel__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_SECTION_OVERVIEW'); ?></p>
			<h1><?php echo Text::_($this->section['title']); ?></h1>
			<p class="lead mb-0"><?php echo Text::sprintf('COM_JOOMLEAGUE_SECTION_DESCRIPTION', Text::_($this->section['title'])); ?></p>
		</div>
		<div class="jl-section-panel__count">
			<strong><?php echo number_format($this->section['count'], 0, ',', ' '); ?></strong>
			<span><?php echo Text::_('COM_JOOMLEAGUE_SECTION_RECORDS'); ?></span>
		</div>
	</section>
</div>
