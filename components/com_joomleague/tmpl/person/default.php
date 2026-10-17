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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Domain\Service\PublicHtmlSanitizer;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Person\HtmlView $this */
$data = $this->person;
$measurement = static fn (mixed $value): string => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
$countryFlag = static function (mixed $value): string {
	$code = strtoupper(trim((string) $value));

	if (preg_match('/^[A-Z]{2}$/', $code) !== 1) {
		return '';
	}

	return sprintf('&#%d;&#%d;', 127397 + ord($code[0]), 127397 + ord($code[1]));
};
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
$roleLabel = static function (object $role): string {
	if (trim((string) ($role->role_name_key ?? '')) !== '') return Text::_((string) $role->role_name_key);
	if (trim((string) ($role->role_name ?? '')) !== '') return (string) $role->role_name;
	return (string) ($role->role_code ?? '');
};
$domainLabel = static fn (object $role): string => Text::_('COM_JOOMLEAGUE_ROLE_DOMAIN_' . strtoupper((string) ($role->role_domain ?? 'competition_official')));
$officiating = $data['officiating_summary'] ?? [];
$siteClubMatches = [];
foreach (($officiating['site_club_stats']['matches'] ?? []) as $clubMatch) $siteClubMatches[(int) $clubMatch['match_id']] = $clubMatch;
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
						<?php if ($person->country_code) : ?><span><?php $flag = $countryFlag($person->country_code); if ($flag !== '') : ?><span role="img" aria-label="<?php echo htmlspecialchars((string) $person->country_code, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $flag; ?></span><?php endif; ?> <?php echo htmlspecialchars((string) $person->country_code, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
					</div>
					<dl class="row row-cols-auto g-3 mt-2 mb-0 small">
						<?php if ($person->birth_date) : ?><div class="col"><dt class="text-body-secondary fw-normal"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_BIRTH_DATE'); ?></dt><dd class="mb-0 fw-semibold"><time datetime="<?php echo htmlspecialchars((string) $person->birth_date, ENT_QUOTES, 'UTF-8'); ?>"><?php echo HTMLHelper::_('date', $person->birth_date, Text::_('DATE_FORMAT_LC3'), 'UTC'); ?></time></dd></div><?php endif; ?>
						<?php if ($person->height_cm !== null) : ?><div class="col"><dt class="text-body-secondary fw-normal"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_HEIGHT'); ?></dt><dd class="mb-0 fw-semibold"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_HEIGHT_VALUE', $measurement($person->height_cm)); ?></dd></div><?php endif; ?>
						<?php if ($person->weight_kg !== null) : ?><div class="col"><dt class="text-body-secondary fw-normal"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_WEIGHT'); ?></dt><dd class="mb-0 fw-semibold"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_WEIGHT_VALUE', $measurement($person->weight_kg)); ?></dd></div><?php endif; ?>
					</dl>
				</div>
			</div>
			<?php if (trim((string) $person->description) !== '') : ?><div class="mt-3"><?php echo PublicHtmlSanitizer::clean($person->description); ?></div><?php endif; ?>
		</header>
		<?php echo LayoutHelper::render('joomleague.fields', ['context' => 'com_joomleague.person', 'item' => $person], JPATH_ROOT . '/components/com_joomleague/layouts'); ?>
		<?php if ($data['carom']['available'] ?? false) require __DIR__ . '/carom.php'; ?>

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
						<strong><?php echo htmlspecialchars((string) $membership->entry_name, ENT_QUOTES, 'UTF-8'); ?></strong><br>
						<small class="text-body-secondary"><?php echo htmlspecialchars((string) $membership->project_name, ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">&middot;</span> <?php echo $formatMembership($membership); ?></small>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ($data['event_assignments'] !== []) : ?>
			<section class="mt-4" aria-labelledby="person-event-assignments">
				<h2 class="h4" id="person-event-assignments"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_EVENT_ASSIGNMENTS'); ?></h2>
				<div class="row row-cols-2 row-cols-md-4 g-3 mb-3">
					<div class="col"><div class="border rounded p-3 h-100"><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_TOTAL'); ?></div><div class="h4 mb-0"><?php echo (int) ($officiating['total'] ?? 0); ?></div></div></div>
					<div class="col"><div class="border rounded p-3 h-100"><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_COMPLETED'); ?></div><div class="h4 mb-0"><?php echo (int) ($officiating['completed'] ?? 0); ?></div></div></div>
					<div class="col"><div class="border rounded p-3 h-100"><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_PROJECTS'); ?></div><div class="h4 mb-0"><?php echo (int) ($officiating['project_count'] ?? 0); ?></div></div></div>
					<div class="col"><div class="border rounded p-3 h-100"><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_SEASONS'); ?></div><div class="h4 mb-0"><?php echo (int) ($officiating['season_count'] ?? 0); ?></div></div></div>
				</div>
				<?php if (($officiating['first_assignment'] ?? null) !== null) : ?><p class="text-body-secondary small"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_CAREER_PERIOD', HTMLHelper::_('date', $officiating['first_assignment'], Text::_('DATE_FORMAT_LC3')), HTMLHelper::_('date', $officiating['last_assignment'], Text::_('DATE_FORMAT_LC3'))); ?></p><?php endif; ?>
				<?php if (($officiating['roles'] ?? []) !== []) : ?><div class="d-flex flex-wrap gap-2 mb-3"><?php foreach ($officiating['roles'] as $roleStats) : $roleText = $roleStats['role_name_key'] !== '' ? Text::_($roleStats['role_name_key']) : ($roleStats['role_name'] !== '' ? $roleStats['role_name'] : $roleStats['role_code']); ?><span class="badge text-bg-light border"><?php echo htmlspecialchars((string) $roleText, ENT_QUOTES, 'UTF-8'); ?>: <?php echo (int) $roleStats['count']; ?></span><?php endforeach; ?></div><?php endif; ?>
				<?php $filters = $officiating['filters'] ?? []; ?><form class="row g-2 align-items-end mb-3" method="get" action="<?php echo Route::_('index.php'); ?>"><input type="hidden" name="option" value="com_joomleague"><input type="hidden" name="view" value="person"><input type="hidden" name="person_id" value="<?php echo (int) $person->id; ?>"><div class="col-12 col-md-4"><label class="form-label" for="referee-filter-project"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_PROJECT'); ?></label><select class="form-select" id="referee-filter-project" name="filter_project_id"><option value="0"><?php echo Text::_('JALL'); ?></option><?php foreach (($filters['projects'] ?? []) as $id => $label) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) ($filters['project_id'] ?? 0) === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div><div class="col-12 col-md-3"><label class="form-label" for="referee-filter-role"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_ROLE'); ?></label><select class="form-select" id="referee-filter-role" name="filter_role_code"><option value=""><?php echo Text::_('JALL'); ?></option><?php foreach (($filters['roles'] ?? []) as $code => $role) : $label = $role['name_key'] !== '' ? Text::_($role['name_key']) : ($role['name'] !== '' ? $role['name'] : $code); ?><option value="<?php echo htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) ($filters['role_code'] ?? '') === (string) $code ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div><div class="col-12 col-md-3"><label class="form-label" for="referee-filter-opponent"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_OPPONENT'); ?></label><select class="form-select" id="referee-filter-opponent" name="filter_opponent_club_id"><option value="0"><?php echo Text::_('JALL'); ?></option><?php foreach (($filters['opponents'] ?? []) as $id => $label) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) ($filters['opponent_club_id'] ?? 0) === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div><div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_APPLY'); ?></button><a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $person->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_RESET'); ?>"><span class="icon-refresh" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_RESET'); ?></span></a></div></form>
				<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMME_EVENT'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ROLE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PERSON_ASSIGNMENT_STATUS'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PERSON_ROLE_CHANGES'); ?></th></tr></thead><tbody>
				<?php foreach ($data['officiating_summary']['projects'] ?? [] as $summary) : ?><tr class="table-light"><th colspan="4"><div class="d-flex flex-wrap justify-content-between gap-2"><span><?php echo htmlspecialchars($summary['project_name'], ENT_QUOTES, 'UTF-8'); ?> <small class="fw-normal text-body-secondary"><?php echo htmlspecialchars($summary['competition_name'] . ' · ' . $summary['season_name'], ENT_QUOTES, 'UTF-8'); ?></small></span><span class="d-flex flex-wrap gap-2"><span class="badge text-bg-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_TOTAL'); ?>: <?php echo (int) $summary['total']; ?></span><span class="badge text-bg-success"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_COMPLETED'); ?>: <?php echo (int) $summary['completed']; ?></span><span class="badge text-bg-light border"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_UPCOMING'); ?>: <?php echo (int) $summary['upcoming']; ?></span></span></div></th></tr><?php if ($summary['site_club_stats'] !== null) : $clubStats = $summary['site_club_stats']; ?><tr><td colspan="4"><div class="small fw-semibold mb-2"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_SITE_CLUB_BALANCE', htmlspecialchars((string) $clubStats['club_name'], ENT_QUOTES, 'UTF-8')); ?></div><div class="mb-2"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_BALANCE_VALUE', (int) $clubStats['played'], (int) $clubStats['wins'], (int) $clubStats['draws'], (int) $clubStats['losses'], htmlspecialchars((string) $clubStats['scored'], ENT_QUOTES, 'UTF-8'), htmlspecialchars((string) $clubStats['conceded'], ENT_QUOTES, 'UTF-8')); ?></div><div class="d-flex flex-wrap gap-2"><?php foreach ($clubStats['opponents'] as $opponentStats) : ?><span class="border rounded px-2 py-1"><strong><a href="<?php echo Route::_('index.php?option=com_joomleague&view=club&club_id=' . (int) $opponentStats['club_id']); ?>"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_AGAINST', htmlspecialchars((string) $opponentStats['club_name'], ENT_QUOTES, 'UTF-8')); ?></a></strong> <span class="text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_BALANCE_VALUE', (int) $opponentStats['played'], (int) $opponentStats['wins'], (int) $opponentStats['draws'], (int) $opponentStats['losses'], htmlspecialchars((string) $opponentStats['scored'], ENT_QUOTES, 'UTF-8'), htmlspecialchars((string) $opponentStats['conceded'], ENT_QUOTES, 'UTF-8')); ?></span></span><?php endforeach; ?></div></td></tr><?php endif; ?><?php foreach ($summary['assignments'] as $assignment) : $changes = $data['role_changes_by_match'][(int) $assignment->match_id] ?? []; $clubMatch = $siteClubMatches[(int) $assignment->match_id] ?? null; ?><tr>
					<th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $assignment->match_id); ?>"><?php echo htmlspecialchars((string) ($assignment->match_number ?: $assignment->round_name), ENT_QUOTES, 'UTF-8'); ?></a><?php if ($clubMatch !== null) : ?><div class="fw-normal"><?php echo htmlspecialchars($clubMatch['site_team_name'] . ' ' . $clubMatch['scored'] . ':' . $clubMatch['conceded'] . ' ' . $clubMatch['opponent_team_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><div class="small fw-normal text-body-secondary"><?php echo htmlspecialchars((string) $assignment->project_name, ENT_QUOTES, 'UTF-8'); ?><?php if ($assignment->scheduled_start) : ?> <span aria-hidden="true">&middot;</span> <time datetime="<?php echo htmlspecialchars((string) $assignment->scheduled_start, ENT_QUOTES, 'UTF-8'); ?>"><?php echo HTMLHelper::_('date', $assignment->scheduled_start, Text::_('DATE_FORMAT_LC3'), (string) ($assignment->timezone ?: 'UTC')); ?></time><?php endif; ?></div></th>
					<td><?php echo htmlspecialchars($roleLabel($assignment), ENT_QUOTES, 'UTF-8'); ?><div class="small text-body-secondary"><?php echo $domainLabel($assignment); ?></div></td>
					<td><?php if ((string) $assignment->delegation_status === 'non_delegated') : ?><span class="badge text-bg-warning"><?php echo Text::_('COM_JOOMLEAGUE_DELEGATION_STATUS_NON_DELEGATED'); ?></span><?php else : ?><span class="badge text-bg-success"><?php echo Text::_('COM_JOOMLEAGUE_DELEGATION_STATUS_DELEGATED'); ?></span><?php endif; ?><?php if ((int) $assignment->published !== 1) : ?><div class="small text-body-secondary mt-1"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_ORIGINAL_NOMINATION'); ?></div><?php endif; ?></td>
					<td><?php $renderedChange = false; foreach ($changes as $change) : $isIncoming = (int) $change->incoming_match_actor_role_id === (int) $assignment->id; $isOutgoing = (int) $change->outgoing_match_actor_role_id === (int) $assignment->id; if (!$isIncoming && !$isOutgoing) continue; $renderedChange = true; ?><div class="small mb-1"><span class="icon-<?php echo $isIncoming ? 'arrow-left' : 'arrow-right'; ?>" aria-hidden="true"></span> <?php echo Text::_($isIncoming ? 'COM_JOOMLEAGUE_PERSON_ROLE_CHANGE_IN' : 'COM_JOOMLEAGUE_PERSON_ROLE_CHANGE_OUT'); ?>: <?php echo htmlspecialchars($roleLabel($change), ENT_QUOTES, 'UTF-8'); ?><?php if ($change->clock_value !== null) : ?>, <?php echo htmlspecialchars((string) $change->clock_value . ' ' . (string) $change->clock_unit, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div><?php endforeach; ?><?php if (!$renderedChange) echo Text::_('JNONE'); ?></td>
				</tr><?php endforeach; endforeach; ?>
				</tbody><tfoot><tr class="fw-semibold"><th><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_ALL_PROJECTS'); ?></th><td colspan="3"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_TOTAL'); ?>: <?php echo (int) ($data['officiating_summary']['total'] ?? 0); ?><?php if (($data['officiating_summary']['site_club_stats'] ?? null) !== null) : $clubStats = $data['officiating_summary']['site_club_stats']; ?><div class="mt-2 fw-normal"><strong><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_SITE_CLUB_BALANCE', htmlspecialchars((string) $clubStats['club_name'], ENT_QUOTES, 'UTF-8')); ?></strong>: <?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_BALANCE_VALUE', (int) $clubStats['played'], (int) $clubStats['wins'], (int) $clubStats['draws'], (int) $clubStats['losses'], htmlspecialchars((string) $clubStats['scored'], ENT_QUOTES, 'UTF-8'), htmlspecialchars((string) $clubStats['conceded'], ENT_QUOTES, 'UTF-8')); ?><div class="d-flex flex-wrap gap-2 mt-2"><?php foreach ($clubStats['opponents'] as $opponentStats) : ?><span class="border rounded px-2 py-1"><strong><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_AGAINST', htmlspecialchars((string) $opponentStats['club_name'], ENT_QUOTES, 'UTF-8')); ?></strong> <span class="text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_BALANCE_VALUE', (int) $opponentStats['played'], (int) $opponentStats['wins'], (int) $opponentStats['draws'], (int) $opponentStats['losses'], htmlspecialchars((string) $opponentStats['scored'], ENT_QUOTES, 'UTF-8'), htmlspecialchars((string) $opponentStats['conceded'], ENT_QUOTES, 'UTF-8')); ?></span></span><?php endforeach; ?></div></div><?php endif; ?></td></tr></tfoot></table></div>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</div>
