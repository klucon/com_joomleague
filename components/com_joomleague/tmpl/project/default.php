<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Project\HtmlView $this */
$data = $this->project;
$config = $this->templateConfig;
?>
<div class="com-joomleague-project">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<?php $project = $data['project']; ?>
		<?php if ($config['show_hero'] ?? true) : ?><header class="border-bottom pb-3 mb-4">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
				<div>
					<h1 class="mb-1"><?php echo htmlspecialchars((string) $project->name, ENT_QUOTES, 'UTF-8'); ?></h1>
					<?php if (($config['show_competition_info'] ?? true) || ($config['show_season'] ?? true)) : ?><div class="text-body-secondary">
						<?php if ($config['show_competition_info'] ?? true) : ?><?php echo htmlspecialchars((string) $project->competition_name, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
						<?php if (($config['show_competition_info'] ?? true) && ($config['show_season'] ?? true)) : ?><span aria-hidden="true">&middot;</span><?php endif; ?>
						<?php if ($config['show_season'] ?? true) : ?><?php echo htmlspecialchars((string) $project->season_name, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
					</div><?php endif; ?>
				</div>
				<?php if ($config['show_sport'] ?? true) : ?><span class="badge text-bg-secondary"><?php echo htmlspecialchars((string) $project->sport_type_name, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
			</div>
			<?php if (trim((string) $project->description) !== '') : ?>
				<p class="mt-3 mb-0"><?php echo nl2br(htmlspecialchars((string) $project->description, ENT_QUOTES, 'UTF-8')); ?></p>
			<?php endif; ?>
		</header><?php else : ?><h1 class="mb-4"><?php echo htmlspecialchars((string) $project->name, ENT_QUOTES, 'UTF-8'); ?></h1><?php endif; ?>
		<?php echo LayoutHelper::render('joomleague.fields', ['context' => 'com_joomleague.project', 'item' => $project], JPATH_ROOT . '/components/com_joomleague/layouts'); ?>

		<div class="row row-cols-2 row-cols-lg-4 g-3 mb-4" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_PROJECT_SUMMARY_LABEL'); ?>">
			<?php foreach (['entries', 'stages', 'rounds', 'program'] as $metric) : ?>
				<div class="col">
					<div class="border rounded p-3 h-100">
						<div class="fs-3 fw-semibold"><?php echo (int) $data['counts'][$metric]; ?></div>
						<div class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_COUNT_' . strtoupper($metric)); ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAVIGATION_TITLE'); ?></h2>
		<div class="list-group">
			<?php if ($data['capabilities']['participants']) : ?>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=participants&project_id=' . (int) $project->id); ?>">
				<span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_PARTICIPANTS'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_PARTICIPANTS_DESC'); ?></small></span>
				<span class="icon-chevron-right" aria-hidden="true"></span>
			</a>
			<?php endif; ?>
			<?php if ($data['capabilities']['personnel']) : ?>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=personnel&project_id=' . (int) $project->id); ?>">
				<span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_PERSONNEL'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_PERSONNEL_DESC'); ?></small></span>
				<span class="icon-chevron-right" aria-hidden="true"></span>
			</a>
			<?php endif; ?>
			<?php if ($data['capabilities']['program']) : ?>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=results&project_id=' . (int) $project->id); ?>">
				<span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_PROGRAM'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_PROGRAM_DESC'); ?></small></span>
				<span class="icon-chevron-right" aria-hidden="true"></span>
			</a>
			<?php endif; ?>
			<?php if ($data['capabilities']['standings'] && $data['capabilities']['program']) : ?>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=standings&project_id=' . (int) $project->id); ?>">
				<span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_STANDINGS'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_STANDINGS_DESC'); ?></small></span>
				<span class="icon-chevron-right" aria-hidden="true"></span>
			</a>
			<?php endif; ?>
			<?php if ((int) $data['capabilities']['bracket_stage_id'] > 0) : ?>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=bracket&project_id=' . (int) $project->id . '&stage_id=' . (int) $data['capabilities']['bracket_stage_id']); ?>">
				<span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_BRACKET'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_BRACKET_DESC'); ?></small></span>
				<span class="icon-chevron-right" aria-hidden="true"></span>
			</a>
			<?php endif; ?>
			<?php if ($data['capabilities']['statistics_overview']) : ?><a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=statisticsoverview&project_id=' . (int) $project->id); ?>"><span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_STATSOVERVIEW'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_STATSOVERVIEW_DESC'); ?></small></span><span class="icon-chevron-right" aria-hidden="true"></span></a><?php endif; ?>
			<?php if ($data['capabilities']['result_matrix']) : ?><a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=resultmatrix&project_id=' . (int) $project->id); ?>"><span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_RESULTMATRIX'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_RESULTMATRIX_DESC'); ?></small></span><span class="icon-chevron-right" aria-hidden="true"></span></a><?php endif; ?>
			<?php if ($data['capabilities']['comparison']) : ?><a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=comparison&project_id=' . (int) $project->id); ?>"><span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_COMPARISON'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_COMPARISON_DESC'); ?></small></span><span class="icon-chevron-right" aria-hidden="true"></span></a><?php endif; ?>
			<?php if ($data['capabilities']['progression']) : ?><a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=standingprogression&project_id=' . (int) $project->id); ?>"><span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_PROGRESSION'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_PROGRESSION_DESC'); ?></small></span><span class="icon-chevron-right" aria-hidden="true"></span></a><?php endif; ?>
		</div>
	<?php endif; ?>
</div>
