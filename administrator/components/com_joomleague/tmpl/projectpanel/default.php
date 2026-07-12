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

/** @var Joomleague\Component\Joomleague\Administrator\View\Projectpanel\HtmlView $this */

$id = (int) $this->project->id;
$style = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.4';

$cards = [
	[
		'url' => Route::_('index.php?option=com_joomleague&task=project.edit&id=' . $id),
		'title' => 'COM_JOOMLEAGUE_PROJECT_OPTIONS',
		'description' => 'COM_JOOMLEAGUE_PROJECT_OPTIONS_DESC',
		'count' => null,
		'icon' => 'icon-cog',
		'tone' => 'violet',
		'future' => false,
	],
	[
		'url' => Route::_('index.php?option=com_joomleague&view=templates&project_id=' . $id),
		'title' => 'COM_JOOMLEAGUE_PROJECT_PAGE_OPTIONS',
		'description' => 'COM_JOOMLEAGUE_PROJECT_PAGE_OPTIONS_DESC',
		'count' => null,
		'icon' => 'icon-palette',
		'tone' => 'slate',
		'future' => false,
	],
	[
		'url' => Route::_('index.php?option=com_joomleague&view=projectpositions&project_id=' . $id),
		'title' => 'COM_JOOMLEAGUE_PROJECT_POSITIONS',
		'description' => 'COM_JOOMLEAGUE_PROJECT_POSITIONS_DESC',
		'count' => (int) $this->project->position_count,
		'icon' => 'icon-address',
		'tone' => 'cyan',
		'future' => false,
	],
	[
		'url' => Route::_('index.php?option=com_joomleague&view=projectreferees&project_id=' . $id),
		'title' => 'COM_JOOMLEAGUE_PROJECT_REFEREES',
		'description' => 'COM_JOOMLEAGUE_PROJECT_REFEREES_DESC',
		'count' => (int) $this->project->referee_count,
		'icon' => 'icon-users',
		'tone' => 'amber',
		'future' => false,
	],
	[
		'url' => Route::_('index.php?option=com_joomleague&view=projectteams&project_id=' . $id),
		'title' => 'COM_JOOMLEAGUE_PROJECT_TEAMS',
		'description' => 'COM_JOOMLEAGUE_PROJECT_TEAMS_DESC',
		'count' => (int) $this->project->team_count,
		'icon' => 'icon-users',
		'tone' => 'emerald',
		'future' => false,
	],
	[
		'url' => Route::_('index.php?option=com_joomleague&view=divisions&project_id=' . $id),
		'title' => 'COM_JOOMLEAGUE_PROJECT_DIVISIONS',
		'description' => 'COM_JOOMLEAGUE_PROJECT_DIVISIONS_DESC',
		'count' => (int) $this->project->division_count,
		'icon' => 'icon-tree-2',
		'tone' => 'teal',
		'future' => false,
	],
	[
		'url' => Route::_('index.php?option=com_joomleague&view=rounds&project_id=' . $id),
		'title' => 'COM_JOOMLEAGUE_PROJECT_ROUNDS',
		'description' => 'COM_JOOMLEAGUE_PROJECT_ROUNDS_DESC',
		'count' => (int) $this->project->round_count,
		'icon' => 'icon-calendar',
		'tone' => 'indigo',
		'future' => false,
	],
	[
		'url' => Route::_('index.php?option=com_joomleague&view=predictiongames&project_id=' . $id),
		'title' => 'COM_JOOMLEAGUE_PREDICTIONGAMES_TITLE',
		'description' => 'COM_JOOMLEAGUE_PREDICTIONGAMES_DESC',
		'count' => null,
		'icon' => 'icon-star',
		'tone' => 'rose',
		'future' => false,
	],
	[
		'url' => '',
		'title' => 'COM_JOOMLEAGUE_PROJECT_EXPORT',
		'description' => 'COM_JOOMLEAGUE_PROJECT_EXPORT_DESC',
		'count' => null,
		'icon' => 'icon-download',
		'tone' => 'orange',
		'future' => true,
	],
];

?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
<div class="com-joomleague-dashboard com-joomleague-projectpanel">
	<p class="mb-3">
		<a class="jl-section-back" href="<?php echo Route::_('index.php?option=com_joomleague&view=projects'); ?>">
			<span class="icon-arrow-left" aria-hidden="true"></span>
			<?php echo Text::_('COM_JOOMLEAGUE_PROJECT_BACK'); ?>
		</a>
	</p>

	<header class="jl-dashboard-header mb-4">
		<div class="jl-dashboard-header__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_PANEL_EYEBROW'); ?></p>
			<h1 class="mb-2"><?php echo $this->escape($this->project->name); ?></h1>
			<p class="mb-0">
				<?php echo $this->escape(trim(($this->project->league ?? '') . ' · ' . ($this->project->season ?? '') . ' · ' . ($this->project->sport ?? ''), " \t\n\r " . chr(11) . '·')); ?>
			</p>
		</div>
		<div class="jl-dashboard-total" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_PROJECT_PANEL_TOTAL_LABEL'); ?>">
			<span class="icon-calendar" aria-hidden="true"></span>
			<strong><?php echo number_format((int) $this->project->match_count, 0, ',', ' '); ?></strong>
			<span><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_MATCHES'); ?></span>
		</div>
	</header>

	<div class="jl-dashboard-grid jl-projectpanel-grid" role="list">
		<?php foreach ($cards as $card) : ?>
			<?php $tag = $card['future'] ? 'span' : 'a'; ?>
			<<?php echo $tag; ?>
				class="jl-dashboard-card jl-projectpanel-card jl-dashboard-card--<?php echo htmlspecialchars($card['tone'], ENT_QUOTES, 'UTF-8'); ?><?php echo $card['future'] ? ' jl-projectpanel-card--future' : ''; ?>"
				<?php echo $card['future'] ? '' : 'href="' . $card['url'] . '"'; ?>
				role="listitem"
			>
				<span class="jl-dashboard-card__heading">
					<span class="jl-dashboard-card__icon <?php echo htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
					<span class="jl-dashboard-card__title"><?php echo Text::_($card['title']); ?></span>
					<?php if ($card['future']) : ?>
						<span class="jl-projectpanel-card__badge"><?php echo Text::_('COM_JOOMLEAGUE_FUTURE_FUNCTION'); ?></span>
					<?php else : ?>
						<span class="jl-dashboard-card__arrow icon-arrow-right" aria-hidden="true"></span>
					<?php endif; ?>
				</span>
				<span class="jl-projectpanel-card__description"><?php echo Text::_($card['description']); ?></span>
				<?php if ($card['count'] !== null) : ?>
					<span class="jl-dashboard-card__metric" aria-label="<?php echo Text::sprintf('COM_JOOMLEAGUE_DASHBOARD_COUNT_LABEL', $card['count']); ?>">
						<strong><?php echo number_format((int) $card['count'], 0, ',', ' '); ?></strong>
						<span><?php echo Text::_('COM_JOOMLEAGUE_SECTION_RECORDS'); ?></span>
					</span>
				<?php endif; ?>
			</<?php echo $tag; ?>>
		<?php endforeach; ?>
	</div>
</div>
