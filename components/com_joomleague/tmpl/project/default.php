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
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Project\HtmlView $this */
$data = $this->project;
?>
<div class="com-joomleague-project">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<?php $project = $data['project']; ?>
		<header class="border-bottom pb-3 mb-4">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
				<div>
					<h1 class="mb-1"><?php echo htmlspecialchars((string) $project->name, ENT_QUOTES, 'UTF-8'); ?></h1>
					<div class="text-body-secondary">
						<?php echo htmlspecialchars((string) $project->competition_name, ENT_QUOTES, 'UTF-8'); ?>
						<span aria-hidden="true">&middot;</span>
						<?php echo htmlspecialchars((string) $project->season_name, ENT_QUOTES, 'UTF-8'); ?>
					</div>
				</div>
				<span class="badge text-bg-secondary"><?php echo htmlspecialchars((string) $project->sport_type_name, ENT_QUOTES, 'UTF-8'); ?></span>
			</div>
			<?php if (trim((string) $project->description) !== '') : ?>
				<p class="mt-3 mb-0"><?php echo nl2br(htmlspecialchars((string) $project->description, ENT_QUOTES, 'UTF-8')); ?></p>
			<?php endif; ?>
		</header>

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
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=results&project_id=' . (int) $project->id); ?>">
				<span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_PROGRAM'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_PROGRAM_DESC'); ?></small></span>
				<span class="icon-chevron-right" aria-hidden="true"></span>
			</a>
			<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo Route::_('index.php?option=com_joomleague&view=standings&project_id=' . (int) $project->id); ?>">
				<span><strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_STANDINGS'); ?></strong><br><small class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_NAV_STANDINGS_DESC'); ?></small></span>
				<span class="icon-chevron-right" aria-hidden="true"></span>
			</a>
		</div>
	<?php endif; ?>
</div>
