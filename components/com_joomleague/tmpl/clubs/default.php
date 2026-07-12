<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';
?>
<div class="com-joomleague-site">
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DIRECTORY'); ?></div><h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUBS'); ?></h1></section>
	<div class="jl-site-grid"><?php foreach ($this->items as $club) : ?><a class="jl-site-card" href="<?php echo Route::_('index.php?option=com_joomleague&view=club&id=' . (int) $club->id); ?>"><span><strong><?php echo LayoutHelper::render('joomleague.flag', ['code' => $club->country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?> <?php echo $this->escape($club->name); ?></strong><br><span class="jl-site-muted"><?php echo $this->escape(trim(($club->location ?? '') . ' ' . ($club->zipcode ?? ''))); ?></span></span><span class="jl-site-badge"><?php echo (int) $club->teams . ' ' . Text::_('COM_JOOMLEAGUE_SITE_TEAMS'); ?></span></a><?php endforeach; ?></div>
	<?php if (!$this->items) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
</div>
