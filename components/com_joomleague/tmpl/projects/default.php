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

/** @var Joomleague\Component\Joomleague\Site\View\Projects\HtmlView $this */
?>
<div class="com-joomleague-projects">
	<h1><?php echo Text::_('COM_JOOMLEAGUE_PROJECTS_VIEW_TITLE'); ?></h1>
	<p class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTS_VIEW_INTRO'); ?></p>

	<?php if ($this->projects === []) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTS_VIEW_EMPTY'); ?></div>
	<?php else : ?>
		<div class="row row-cols-1 row-cols-lg-2 g-3">
			<?php foreach ($this->projects as $project) : ?>
				<div class="col">
					<article class="card h-100">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start gap-3 mb-2">
								<div>
									<h2 class="h4 mb-1">
										<a class="stretched-link text-decoration-none" href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $project->id); ?>">
											<?php echo htmlspecialchars((string) $project->name, ENT_QUOTES, 'UTF-8'); ?>
										</a>
									</h2>
									<div class="text-body-secondary">
										<?php echo htmlspecialchars((string) $project->competition_name, ENT_QUOTES, 'UTF-8'); ?>
										<span aria-hidden="true">&middot;</span>
										<?php echo htmlspecialchars((string) $project->season_name, ENT_QUOTES, 'UTF-8'); ?>
									</div>
								</div>
								<span class="badge text-bg-secondary"><?php echo htmlspecialchars((string) $project->sport_type_name, ENT_QUOTES, 'UTF-8'); ?></span>
							</div>
							<?php if (trim((string) $project->description) !== '') : ?>
								<p class="mb-3"><?php echo nl2br(htmlspecialchars((string) $project->description, ENT_QUOTES, 'UTF-8')); ?></p>
							<?php endif; ?>
							<div class="d-flex flex-wrap gap-3 small text-body-secondary">
								<span><?php echo Text::sprintf('COM_JOOMLEAGUE_PROJECTS_ENTRY_COUNT', (int) $project->entry_count); ?></span>
								<span><?php echo Text::sprintf('COM_JOOMLEAGUE_PROJECTS_PROGRAM_COUNT', (int) $project->match_count); ?></span>
							</div>
						</div>
					</article>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
