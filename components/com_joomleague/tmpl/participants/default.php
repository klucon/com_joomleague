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

/** @var Joomleague\Component\Joomleague\Site\View\Participants\HtmlView $this */
$data = $this->participants;
$kindLabels = [
	'team' => 'COM_JOOMLEAGUE_PARTICIPANTS_KIND_TEAM',
	'person' => 'COM_JOOMLEAGUE_PARTICIPANTS_KIND_PERSON',
	'group' => 'COM_JOOMLEAGUE_PARTICIPANTS_KIND_GROUP',
];
?>
<div class="com-joomleague-participants">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<header class="border-bottom pb-3 mb-4">
			<h1 class="mb-1"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANTS_VIEW_TITLE'); ?></h1>
			<div class="text-body-secondary">
				<?php echo htmlspecialchars((string) $data['project']->name, ENT_QUOTES, 'UTF-8'); ?>
				<span aria-hidden="true">&middot;</span>
				<?php echo htmlspecialchars((string) $data['project']->sport_type_name, ENT_QUOTES, 'UTF-8'); ?>
			</div>
		</header>

		<?php if ($data['participants'] === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANTS_VIEW_EMPTY'); ?></div>
		<?php else : ?>
			<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
				<?php foreach ($data['participants'] as $participant) : ?>
					<?php $media = $participant->team_logo ?: ($participant->person_picture ?: $participant->team_picture); ?>
					<div class="col">
						<article class="card h-100">
							<div class="card-body d-flex gap-3 align-items-start">
								<?php if ($media) : ?>
									<img src="<?php echo htmlspecialchars((string) $media, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid" width="64" height="64" loading="lazy">
								<?php else : ?>
									<span class="icon-users fs-2 text-body-secondary" aria-hidden="true"></span>
								<?php endif; ?>
								<div class="flex-grow-1">
									<div class="d-flex justify-content-between gap-2 align-items-start">
										<h2 class="h5 mb-1"><a class="stretched-link text-decoration-none" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $data['project']->id . '&entry_id=' . (int) $participant->id); ?>"><?php echo htmlspecialchars((string) $participant->display_name, ENT_QUOTES, 'UTF-8'); ?></a></h2>
										<span class="badge text-bg-secondary"><?php echo Text::_($kindLabels[(string) $participant->entry_kind] ?? 'COM_JOOMLEAGUE_PARTICIPANTS_KIND_GROUP'); ?></span>
									</div>
									<?php if ($participant->club_name) : ?><div class="mb-2"><a class="position-relative z-2 text-body-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=club&club_id=' . (int) $participant->club_id); ?>"><?php echo htmlspecialchars((string) $participant->club_name, ENT_QUOTES, 'UTF-8'); ?></a></div><?php endif; ?>
									<div class="d-flex flex-wrap gap-3 small text-body-secondary">
										<?php if ($participant->seed_number !== null) : ?><span><?php echo Text::sprintf('COM_JOOMLEAGUE_PARTICIPANTS_SEED', (int) $participant->seed_number); ?></span><?php endif; ?>
										<?php if ($participant->bib_number !== null && $participant->bib_number !== '') : ?><span><?php echo Text::sprintf('COM_JOOMLEAGUE_PARTICIPANTS_BIB', htmlspecialchars((string) $participant->bib_number, ENT_QUOTES, 'UTF-8')); ?></span><?php endif; ?>
										<?php if ((int) $participant->member_count > 0) : ?><span><?php echo Text::sprintf('COM_JOOMLEAGUE_PARTICIPANTS_MEMBER_COUNT', (int) $participant->member_count); ?></span><?php endif; ?>
									</div>
								</div>
							</div>
						</article>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
