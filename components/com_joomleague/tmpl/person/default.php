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

/** @var Joomleague\Component\Joomleague\Site\View\Person\HtmlView $this */
$data = $this->person;
$personTypeLabels = [
	'player' => 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_PLAYER',
	'staff' => 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_STAFF',
	'official' => 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_OFFICIAL',
	'participant' => 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_PARTICIPANT',
];
$formatMembership = static function (object $membership) use ($personTypeLabels): string {
	$role = trim((string) ($membership->role_name_key ?? '')) !== ''
		? Text::_((string) $membership->role_name_key)
		: (trim((string) ($membership->role_name ?? '')) !== '' ? (string) $membership->role_name : (string) ($membership->role_code ?? ''));
	$parts = [Text::_($personTypeLabels[(string) $membership->member_person_type] ?? 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_PARTICIPANT')];

	if ($role !== '') {
		$parts[] = $role;
	}

	if ($membership->valid_from || $membership->valid_until) {
		$parts[] = Text::sprintf(
			'COM_JOOMLEAGUE_PERSON_MEMBERSHIP_PERIOD',
			$membership->valid_from ?: Text::_('COM_JOOMLEAGUE_PERSON_PERIOD_OPEN'),
			$membership->valid_until ?: Text::_('COM_JOOMLEAGUE_PERSON_PERIOD_OPEN')
		);
	}

	return implode(' · ', array_map(static fn (string $part): string => htmlspecialchars($part, ENT_QUOTES, 'UTF-8'), $parts));
};
?>
<div class="com-joomleague-person">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<?php $person = $data['person']; $name = trim((string) $person->first_name . ' ' . (string) $person->last_name); ?>
		<header class="border-bottom pb-3 mb-4">
			<div class="d-flex flex-wrap gap-3 align-items-start">
				<?php if ($person->picture) : ?><img src="<?php echo htmlspecialchars((string) $person->picture, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid" width="128" height="128"><?php else : ?><span class="icon-user fs-1 text-body-secondary" aria-hidden="true"></span><?php endif; ?>
				<div class="flex-grow-1">
					<h1 class="mb-1"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h1>
					<?php if (trim((string) $person->nickname) !== '') : ?><div class="text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_NICKNAME', htmlspecialchars((string) $person->nickname, ENT_QUOTES, 'UTF-8')); ?></div><?php endif; ?>
					<div class="d-flex flex-wrap gap-3 mt-2 small text-body-secondary">
						<?php if ($person->club_name) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=club&club_id=' . (int) $person->club_id); ?>"><?php echo htmlspecialchars((string) $person->club_name, ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?>
						<?php if ($person->country_code) : ?><span><?php echo htmlspecialchars((string) $person->country_code, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
					</div>
				</div>
			</div>
			<?php if (trim((string) $person->description) !== '') : ?><p class="mt-3 mb-0"><?php echo nl2br(htmlspecialchars((string) $person->description, ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?>
		</header>

		<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CURRENT_MEMBERSHIPS'); ?></h2>
		<?php if ($data['memberships'] === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_MEMBERSHIPS_EMPTY'); ?></div>
		<?php else : ?>
			<div class="list-group">
				<?php foreach ($data['memberships'] as $membership) : ?>
					<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $membership->project_id . '&entry_id=' . (int) $membership->entry_id); ?>">
						<span><strong><?php echo htmlspecialchars((string) $membership->entry_name, ENT_QUOTES, 'UTF-8'); ?></strong><br><small class="text-body-secondary"><?php echo htmlspecialchars((string) $membership->project_name, ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">&middot;</span> <?php echo htmlspecialchars((string) $membership->sport_type_name, ENT_QUOTES, 'UTF-8'); ?><br><?php echo $formatMembership($membership); ?></small></span>
						<span class="d-flex gap-2 align-items-center"><?php if ($membership->shirt_number) : ?><span class="badge text-bg-light border"><?php echo Text::sprintf('COM_JOOMLEAGUE_PARTICIPANT_MEMBER_NUMBER', htmlspecialchars((string) $membership->shirt_number, ENT_QUOTES, 'UTF-8')); ?></span><?php endif; ?><?php if ((int) $membership->is_captain === 1) : ?><span class="badge text-bg-primary"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANT_MEMBER_CAPTAIN'); ?></span><?php endif; ?><span class="icon-chevron-right" aria-hidden="true"></span></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ($data['membership_history'] !== []) : ?>
			<h2 class="h4 mt-4"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_MEMBERSHIP_HISTORY'); ?></h2>
			<div class="list-group">
				<?php foreach ($data['membership_history'] as $membership) : ?>
					<a class="list-group-item list-group-item-action" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $membership->project_id . '&entry_id=' . (int) $membership->entry_id); ?>">
						<strong><?php echo htmlspecialchars((string) $membership->entry_name, ENT_QUOTES, 'UTF-8'); ?></strong>
						<span class="badge text-bg-secondary ms-2"><?php echo Text::_('COM_JOOMLEAGUE_LIFECYCLE_' . strtoupper((string) $membership->lifecycle_state)); ?></span><br>
						<small class="text-body-secondary"><?php echo htmlspecialchars((string) $membership->project_name, ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">&middot;</span> <?php echo $formatMembership($membership); ?></small>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
