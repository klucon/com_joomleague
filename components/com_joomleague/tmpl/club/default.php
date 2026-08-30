<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Club\HtmlView $this */
$data = $this->club;
?>
<div class="com-joomleague-club">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<?php $club = $data['club']; ?>
		<header class="border-bottom pb-3 mb-4">
			<div class="d-flex flex-wrap gap-3 align-items-start">
				<?php if ($club->logo) : ?><img src="<?php echo htmlspecialchars((string) $club->logo, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid" width="128" height="128"><?php else : ?><span class="icon-shield fs-1 text-body-secondary" aria-hidden="true"></span><?php endif; ?>
				<div class="flex-grow-1">
					<h1 class="mb-1"><?php echo htmlspecialchars((string) $club->name, ENT_QUOTES, 'UTF-8'); ?></h1>
					<?php if ($club->short_name) : ?><div class="text-body-secondary"><?php echo htmlspecialchars((string) $club->short_name, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
					<div class="d-flex flex-wrap gap-3 mt-2 small text-body-secondary">
						<?php if ($club->country_code) : ?><span><?php echo htmlspecialchars((string) $club->country_code, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
						<?php if ($club->founded_date) : ?><span><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUB_FOUNDED', HTMLHelper::_('date', $club->founded_date, Text::_('DATE_FORMAT_LC3'))); ?></span><?php endif; ?>
						<?php if ($club->dissolved_date) : ?><span><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUB_DISSOLVED', HTMLHelper::_('date', $club->dissolved_date, Text::_('DATE_FORMAT_LC3'))); ?></span><?php endif; ?>
					</div>
					<?php if ($club->website) : ?><a class="btn btn-sm btn-outline-primary mt-3" href="<?php echo htmlspecialchars((string) $club->website, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><span class="icon-out-2" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_CLUB_WEBSITE'); ?></a><?php endif; ?>
				</div>
			</div>
			<?php if (trim((string) $club->description) !== '') : ?><p class="mt-3 mb-0"><?php echo nl2br(htmlspecialchars((string) $club->description, ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?>
		</header>

		<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_CLUB_TEAMS_TITLE'); ?></h2>
		<?php if ($data['teams'] === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_CLUB_TEAMS_EMPTY'); ?></div>
		<?php else : ?>
			<div class="row row-cols-1 row-cols-md-2 g-3">
				<?php foreach ($data['teams'] as $team) : ?>
					<?php $media = $team->logo ?: $team->picture; ?>
					<div class="col"><article class="card h-100"><div class="card-body">
						<div class="d-flex gap-3 align-items-start">
							<?php if ($media) : ?><img src="<?php echo htmlspecialchars((string) $media, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid" width="72" height="72" loading="lazy"><?php else : ?><span class="icon-users fs-2 text-body-secondary" aria-hidden="true"></span><?php endif; ?>
							<div class="flex-grow-1"><h3 class="h5 mb-1"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&team_id=' . (int) $team->id); ?>"><?php echo htmlspecialchars((string) $team->name, ENT_QUOTES, 'UTF-8'); ?></a></h3><?php if ($team->middle_name || $team->short_name) : ?><div class="text-body-secondary"><?php echo htmlspecialchars((string) ($team->middle_name ?: $team->short_name), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?></div>
						</div>
						<?php if (trim((string) $team->description) !== '') : ?><p class="mt-3 mb-0"><?php echo nl2br(htmlspecialchars((string) $team->description, ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?>
						<?php if ($team->projects !== []) : ?>
							<h4 class="h6 mt-3"><?php echo Text::_('COM_JOOMLEAGUE_CLUB_TEAM_PROJECTS'); ?></h4>
							<div class="list-group list-group-flush">
								<?php foreach ($team->projects as $project) : ?>
									<div class="list-group-item px-0">
										<strong><?php echo htmlspecialchars((string) $project->project_name, ENT_QUOTES, 'UTF-8'); ?></strong>
										<small class="d-block text-body-secondary mb-2"><?php echo htmlspecialchars((string) $project->competition_name, ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">&middot;</span> <?php echo htmlspecialchars((string) $project->season_name, ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">&middot;</span> <?php echo htmlspecialchars((string) $project->sport_type_name, ENT_QUOTES, 'UTF-8'); ?></small>
										<div class="d-flex flex-wrap gap-2">
											<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $project->project_id . '&entry_id=' . (int) $project->id); ?>"><span class="icon-user" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_CLUB_PARTICIPANT_DETAIL'); ?></a>
											<a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=teamplan&project_id=' . (int) $project->project_id . '&entry_id=' . (int) $project->id); ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_CLUB_TEAM_PROGRAMME'); ?></a>
											<a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=clubplan&project_id=' . (int) $project->project_id . '&club_id=' . (int) $club->id); ?>"><span class="icon-calendar-2" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_CLUB_COMBINED_PROGRAMME'); ?></a>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div></article></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
