<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Dashboard\HtmlView $this */

$dashboardStyle = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.5.4';

?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($dashboardStyle, ENT_QUOTES, 'UTF-8'); ?>">
<div class="com-joomleague-dashboard">
	<header class="jl-dashboard-header mb-4">
		<div class="jl-dashboard-header__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_DASHBOARD_EYEBROW'); ?></p>
			<h1 class="mb-2"><?php echo Text::_('COM_JOOMLEAGUE_DASHBOARD_TITLE'); ?></h1>
			<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_DASHBOARD_DESCRIPTION'); ?></p>
		</div>
		<div class="jl-dashboard-total" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_DASHBOARD_TOTAL_LABEL'); ?>">
			<span class="icon-database" aria-hidden="true"></span>
			<strong><?php echo number_format($this->totalItems, 0, ',', ' '); ?></strong>
			<span><?php echo Text::_('COM_JOOMLEAGUE_DASHBOARD_TOTAL_LABEL'); ?></span>
		</div>
	</header>

	<div class="jl-dashboard-grid" role="list">
		<?php foreach ($this->sections as $section) : ?>
			<a
				class="jl-dashboard-card jl-dashboard-card--<?php echo htmlspecialchars($section['tone'], ENT_QUOTES, 'UTF-8'); ?>"
				href="<?php echo Route::_('index.php?option=com_joomleague&view=' . $section['view']); ?>"
				role="listitem"
			>
				<span class="jl-dashboard-card__heading">
					<span class="jl-dashboard-card__icon <?php echo htmlspecialchars($section['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
					<span class="jl-dashboard-card__title"><?php echo Text::_($section['title']); ?></span>
					<span class="jl-dashboard-card__arrow icon-arrow-right" aria-hidden="true"></span>
				</span>
				<?php if ($section['count'] !== null) : ?>
					<span class="jl-dashboard-card__metric" aria-label="<?php echo Text::sprintf('COM_JOOMLEAGUE_DASHBOARD_COUNT_LABEL', $section['count']); ?>">
						<strong><?php echo number_format($section['count'], 0, ',', ' '); ?></strong>
						<span><?php echo Text::_('COM_JOOMLEAGUE_SECTION_RECORDS'); ?></span>
					</span>
				<?php else : ?>
					<span class="jl-projectpanel-card__description"><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_CARD_DESC'); ?></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
</div>
