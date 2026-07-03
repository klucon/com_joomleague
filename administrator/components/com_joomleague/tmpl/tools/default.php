<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$style = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.6';
$cards = [
	['view' => 'databasetools', 'title' => 'COM_JOOMLEAGUE_DBTOOLS_TITLE', 'desc' => 'COM_JOOMLEAGUE_DBTOOLS_DESC', 'icon' => 'icon-database', 'tone' => 'cyan'],
	['view' => 'updates', 'title' => 'COM_JOOMLEAGUE_UPDATES_TITLE', 'desc' => 'COM_JOOMLEAGUE_UPDATES_DESC', 'icon' => 'icon-refresh', 'tone' => 'violet'],
	['view' => 'import', 'title' => 'COM_JOOMLEAGUE_IMPORT_TITLE', 'desc' => 'COM_JOOMLEAGUE_IMPORT_DESC', 'icon' => 'icon-upload', 'tone' => 'emerald'],
	['view' => 'jlxmlexports', 'title' => 'COM_JOOMLEAGUE_EXPORT_TITLE', 'desc' => 'COM_JOOMLEAGUE_EXPORT_DESC', 'icon' => 'icon-download', 'tone' => 'orange'],
	['view' => 'treetos', 'title' => 'COM_JOOMLEAGUE_TREES_TITLE', 'desc' => 'COM_JOOMLEAGUE_TREES_DESC', 'icon' => 'icon-tree-2', 'tone' => 'teal'],
	['view' => 'predictiongames', 'title' => 'COM_JOOMLEAGUE_PREDICTIONGAMES_TITLE', 'desc' => 'COM_JOOMLEAGUE_PREDICTIONGAMES_DESC', 'icon' => 'icon-star', 'tone' => 'rose'],
];
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
<div class="com-joomleague-dashboard">
	<header class="jl-dashboard-header mb-4">
		<div class="jl-dashboard-header__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_DASHBOARD_EYEBROW'); ?></p>
			<h1 class="mb-2"><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_TITLE'); ?></h1>
			<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_DESC'); ?></p>
		</div>
	</header>
	<div class="jl-dashboard-grid" role="list">
		<?php foreach ($cards as $card) : ?>
			<a class="jl-dashboard-card jl-dashboard-card--<?php echo htmlspecialchars($card['tone'], ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo Route::_('index.php?option=com_joomleague&view=' . $card['view']); ?>" role="listitem">
				<span class="jl-dashboard-card__heading">
					<span class="jl-dashboard-card__icon <?php echo htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
					<span class="jl-dashboard-card__title"><?php echo Text::_($card['title']); ?></span>
					<span class="jl-dashboard-card__arrow icon-arrow-right" aria-hidden="true"></span>
				</span>
				<span class="jl-projectpanel-card__description"><?php echo Text::_($card['desc']); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</div>
