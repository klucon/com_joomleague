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

/** @var Joomleague\Component\Joomleague\Site\View\Participant\HtmlView $this */
$data = $this->participant;
$kindLabels = [
	'team' => 'COM_JOOMLEAGUE_PARTICIPANTS_KIND_TEAM',
	'person' => 'COM_JOOMLEAGUE_PARTICIPANTS_KIND_PERSON',
	'group' => 'COM_JOOMLEAGUE_PARTICIPANTS_KIND_GROUP',
];
$personTypeLabels = [
	'player' => 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_PLAYER',
	'staff' => 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_STAFF',
	'official' => 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_OFFICIAL',
	'participant' => 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_PARTICIPANT',
];
?>
<div class="com-joomleague-participant">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<?php
		$participant = $data['participant'];
		$media = $participant->team_logo ?: ($participant->person_picture ?: $participant->team_picture);
		$description = $participant->entry_kind === 'team' ? $participant->team_description : $participant->person_description;
		?>
		<header class="border-bottom pb-3 mb-4">
			<div class="d-flex flex-wrap align-items-start gap-3">
				<?php if ($media) : ?><img src="<?php echo htmlspecialchars((string) $media, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid" width="96" height="96"><?php endif; ?>
				<div class="flex-grow-1">
					<div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
						<div><h1 class="mb-1"><?php echo htmlspecialchars((string) $participant->display_name, ENT_QUOTES, 'UTF-8'); ?></h1><div class="text-body-secondary"><?php echo htmlspecialchars((string) $participant->project_name, ENT_QUOTES, 'UTF-8'); ?></div></div>
						<span class="badge text-bg-secondary"><?php echo Text::_($kindLabels[(string) $participant->entry_kind] ?? 'COM_JOOMLEAGUE_PARTICIPANTS_KIND_GROUP'); ?></span>
					</div>
					<div class="d-flex flex-wrap gap-3 mt-2 small text-body-secondary">
						<?php if ($participant->club_name) : ?><span><?php echo htmlspecialchars((string) $participant->club_name, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
						<?php if ($participant->seed_number !== null) : ?><span><?php echo Text::sprintf('COM_JOOMLEAGUE_PARTICIPANTS_SEED', (int) $participant->seed_number); ?></span><?php endif; ?>
						<?php if ($participant->bib_number) : ?><span><?php echo Text::sprintf('COM_JOOMLEAGUE_PARTICIPANTS_BIB', htmlspecialchars((string) $participant->bib_number, ENT_QUOTES, 'UTF-8')); ?></span><?php endif; ?>
					</div>
				</div>
			</div>
			<?php if (trim((string) $description) !== '') : ?><p class="mt-3 mb-0"><?php echo nl2br(htmlspecialchars((string) $description, ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?>
		</header>

		<div class="d-flex flex-wrap gap-2 mb-4">
			<a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=teamplan&project_id=' . (int) $participant->project_id . '&entry_id=' . (int) $participant->id); ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANT_OPEN_PROGRAM'); ?></a>
			<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=participants&project_id=' . (int) $participant->project_id); ?>"><span class="icon-users" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANT_BACK_TO_LIST'); ?></a>
		</div>

		<?php if ($data['members'] !== []) : ?>
			<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANT_MEMBERS_TITLE'); ?></h2>
			<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
				<?php foreach ($data['members'] as $member) : ?>
					<div class="col"><article class="card h-100"><div class="card-body d-flex gap-3 align-items-start">
						<?php if ($member->picture) : ?><img src="<?php echo htmlspecialchars((string) $member->picture, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid" width="64" height="64" loading="lazy"><?php else : ?><span class="icon-user fs-2 text-body-secondary" aria-hidden="true"></span><?php endif; ?>
						<div><h3 class="h5 mb-1"><a class="stretched-link text-decoration-none" href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $member->person_id); ?>"><?php echo htmlspecialchars(trim((string) $member->first_name . ' ' . (string) $member->last_name), ENT_QUOTES, 'UTF-8'); ?></a></h3><div class="text-body-secondary"><?php echo Text::_($personTypeLabels[(string) $member->member_person_type] ?? 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_PARTICIPANT'); ?></div><?php if ($member->shirt_number) : ?><span class="badge text-bg-light border mt-2"><?php echo Text::sprintf('COM_JOOMLEAGUE_PARTICIPANT_MEMBER_NUMBER', htmlspecialchars((string) $member->shirt_number, ENT_QUOTES, 'UTF-8')); ?></span><?php endif; ?><?php if ((int) $member->is_captain === 1) : ?> <span class="badge text-bg-primary mt-2"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANT_MEMBER_CAPTAIN'); ?></span><?php endif; ?></div>
					</div></article></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
