<?php

declare(strict_types=1);

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Aboutjoomleague\HtmlView $this */
?>
<div class="com-joomleague-about">
	<header class="border-bottom pb-3 mb-4">
		<div class="mb-3">
			<?php echo HTMLHelper::_('image', Uri::root(true) . '/media/com_joomleague/brand/joomleague-logo.png', 'JoomLeague', ['class' => 'img-fluid', 'width' => 480, 'height' => 150, 'decoding' => 'async'], false, -1); ?>
		</div>
		<p class="text-uppercase fw-semibold text-body-secondary small mb-2"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_EYEBROW'); ?></p>
		<h1 class="mb-2"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_VIEW_TITLE'); ?></h1>
		<p class="lead mb-2"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_INTRO'); ?></p>
		<p class="text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_VIEW_DESC'); ?></p>
	</header>

	<section class="mb-5" aria-labelledby="joomleague-about-capabilities">
		<h2 id="joomleague-about-capabilities" class="h3 mb-3"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_CAPABILITIES_TITLE'); ?></h2>
		<div class="row g-4">
			<div class="col-12 col-lg-4">
				<h3 class="h5"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_CAPABILITY_SPORT_NEUTRAL_TITLE'); ?></h3>
				<p class="text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_CAPABILITY_SPORT_NEUTRAL_DESC'); ?></p>
			</div>
			<div class="col-12 col-lg-4">
				<h3 class="h5"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_CAPABILITY_WORKFLOW_TITLE'); ?></h3>
				<p class="text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_CAPABILITY_WORKFLOW_DESC'); ?></p>
			</div>
			<div class="col-12 col-lg-4">
				<h3 class="h5"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_CAPABILITY_PUBLISHING_TITLE'); ?></h3>
				<p class="text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_CAPABILITY_PUBLISHING_DESC'); ?></p>
			</div>
		</div>
	</section>

	<section class="mb-5" aria-labelledby="joomleague-about-purpose">
		<div class="row g-4">
			<div class="col-12 col-lg-7">
				<h2 id="joomleague-about-purpose" class="h3 mb-3"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_PURPOSE_TITLE'); ?></h2>
				<p><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_PURPOSE_P1'); ?></p>
				<p><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_PURPOSE_P2'); ?></p>
				<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_PURPOSE_P3'); ?></p>
			</div>
			<div class="col-12 col-lg-5">
				<h2 class="h3 mb-3"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_AUDIENCE_TITLE'); ?></h2>
				<ul class="mb-0">
					<li class="mb-2"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_AUDIENCE_CLUBS'); ?></li>
					<li class="mb-2"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_AUDIENCE_LEAGUES'); ?></li>
					<li class="mb-2"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_AUDIENCE_ORGANISERS'); ?></li>
					<li><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_AUDIENCE_COMMUNITIES'); ?></li>
				</ul>
			</div>
		</div>
	</section>

	<section class="mb-5" aria-labelledby="joomleague-about-installation">
		<h2 id="joomleague-about-installation" class="h3 mb-3"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_INSTALLATION_TITLE'); ?></h2>
	<div class="row g-3 mb-4">
		<div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_AUTHOR'); ?></div><strong><?php echo htmlspecialchars($this->installation['author'], ENT_QUOTES, 'UTF-8'); ?></strong></div></div></div>
		<div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_COMPONENT_VERSION'); ?></div><strong><?php echo htmlspecialchars($this->installation['component_version'], ENT_QUOTES, 'UTF-8'); ?></strong></div></div></div>
		<div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_PROFILES'); ?></div><strong><?php echo (int) $this->installation['profile_count']; ?></strong></div></div></div>
	</div>
	</section>

	<section class="mb-5" aria-labelledby="joomleague-about-resources">
		<h2 id="joomleague-about-resources" class="h3 mb-3"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_RESOURCES_TITLE'); ?></h2>
		<div class="list-group">
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3" href="https://joomleague.eu/" target="_blank" rel="noopener noreferrer"><span><strong class="d-block"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_RESOURCE_WEBSITE'); ?></strong><span class="text-body-secondary">joomleague.eu</span></span><span class="icon-out-2" aria-hidden="true"></span></a>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3" href="https://docs.joomleague.eu/" target="_blank" rel="noopener noreferrer"><span><strong class="d-block"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_RESOURCE_DOCUMENTATION'); ?></strong><span class="text-body-secondary">docs.joomleague.eu</span></span><span class="icon-out-2" aria-hidden="true"></span></a>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3" href="https://support.joomleague.eu/" target="_blank" rel="noopener noreferrer"><span><strong class="d-block"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_RESOURCE_SUPPORT'); ?></strong><span class="text-body-secondary">support.joomleague.eu</span></span><span class="icon-out-2" aria-hidden="true"></span></a>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3" href="https://translate.joomleague.eu/" target="_blank" rel="noopener noreferrer"><span><strong class="d-block"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_RESOURCE_TRANSLATIONS'); ?></strong><span class="text-body-secondary">translate.joomleague.eu</span></span><span class="icon-out-2" aria-hidden="true"></span></a>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3" href="https://demo.joomleague.eu/" target="_blank" rel="noopener noreferrer"><span><strong class="d-block"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_RESOURCE_DEMO'); ?></strong><span class="text-body-secondary">demo.joomleague.eu</span></span><span class="icon-out-2" aria-hidden="true"></span></a>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3" href="https://github.com/klucon/com_joomleague" target="_blank" rel="noopener noreferrer"><span><strong class="d-block"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_RESOURCE_SOURCE'); ?></strong><span class="text-body-secondary">github.com/klucon/com_joomleague</span></span><span class="icon-out-2" aria-hidden="true"></span></a>
		</div>
	</section>

	<section class="border-top pt-4" aria-labelledby="joomleague-about-license">
		<h2 id="joomleague-about-license" class="h3"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_LICENSE_TITLE'); ?></h2>
		<p><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_LICENSE_DESC'); ?></p>
		<p class="text-body-secondary small mb-1"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_COPYRIGHT'); ?></p>
		<p class="text-body-secondary small mb-0"><?php echo Text::_('COM_JOOMLEAGUE_ABOUT_TRADEMARK_NOTICE'); ?></p>
	</section>
</div>
