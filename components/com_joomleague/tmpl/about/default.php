<?php

declare(strict_types=1);

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\About\HtmlView $this */
?>
<div class="com-joomleague-about">
	<header class="border-bottom pb-3 mb-4">
		<h1 class="mb-1"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_VIEW_TITLE'); ?></h1>
		<p class="text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_VIEW_DESC'); ?></p>
	</header>
	<div class="row g-3 mb-4">
		<div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_COMPONENT_VERSION'); ?></div><strong><?php echo htmlspecialchars($this->installation['component_version'], ENT_QUOTES, 'UTF-8'); ?></strong></div></div></div>
		<div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_JOOMLA_VERSION'); ?></div><strong><?php echo htmlspecialchars($this->installation['joomla_version'], ENT_QUOTES, 'UTF-8'); ?></strong></div></div></div>
		<div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_PROFILES'); ?></div><strong><?php echo (int) $this->installation['profile_count']; ?></strong></div></div></div>
	</div>
	<section>
		<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_LICENSE_TITLE'); ?></h2>
		<p><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_LICENSE_DESC'); ?></p>
	</section>
</div>
