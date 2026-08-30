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

/** @var Joomleague\Component\Joomleague\Site\View\Team\HtmlView $this */
$data = $this->team;
?>
<div class="com-joomleague-team">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<?php $team = $data['team']; $media = $team->logo ?: $team->picture; ?>
		<header class="border-bottom pb-3 mb-4">
			<div class="d-flex flex-wrap gap-3 align-items-start">
				<?php if ($media) : ?><img src="<?php echo htmlspecialchars((string) $media, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid" width="128" height="128"><?php else : ?><span class="icon-users fs-1 text-body-secondary" aria-hidden="true"></span><?php endif; ?>
				<div class="flex-grow-1">
					<h1 class="mb-1"><?php echo htmlspecialchars((string) $team->name, ENT_QUOTES, 'UTF-8'); ?></h1>
					<?php if ($team->middle_name || $team->short_name) : ?><div class="text-body-secondary"><?php echo htmlspecialchars((string) ($team->middle_name ?: $team->short_name), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
					<div class="d-flex flex-wrap gap-2 mt-3">
						<?php if ($team->club_name) : ?><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=club&club_id=' . (int) $team->club_id); ?>"><span class="icon-shield" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $team->club_name, ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?>
						<?php if ($team->website) : ?><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars((string) $team->website, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><span class="icon-out-2" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_TEAM_WEBSITE'); ?></a><?php endif; ?>
					</div>
				</div>
			</div>
			<?php if (trim((string) $team->description) !== '') : ?><p class="mt-3 mb-0"><?php echo nl2br(htmlspecialchars((string) $team->description, ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?>
		</header>

		<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_TEAM_PROJECTS_TITLE'); ?></h2>
		<?php if ($data['projects'] === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_TEAM_PROJECTS_EMPTY'); ?></div>
		<?php else : ?>
			<div class="list-group">
				<?php foreach ($data['projects'] as $project) : ?>
					<div class="list-group-item">
						<strong><?php echo htmlspecialchars((string) $project->project_name, ENT_QUOTES, 'UTF-8'); ?></strong>
						<small class="d-block text-body-secondary mb-2"><?php echo htmlspecialchars((string) $project->competition_name, ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">&middot;</span> <?php echo htmlspecialchars((string) $project->season_name, ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">&middot;</span> <?php echo htmlspecialchars((string) $project->sport_type_name, ENT_QUOTES, 'UTF-8'); ?></small>
						<div class="d-flex flex-wrap gap-2">
							<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $project->project_id . '&entry_id=' . (int) $project->id); ?>"><span class="icon-user" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_CLUB_PARTICIPANT_DETAIL'); ?></a>
							<a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=teamplan&project_id=' . (int) $project->project_id . '&entry_id=' . (int) $project->id); ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_CLUB_TEAM_PROGRAMME'); ?></a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
