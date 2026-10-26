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
use Joomleague\Component\Joomleague\Domain\Service\CountryPresentation;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Person\HtmlView $this */
$data = $this->person;
$measurement = static fn (mixed $value): string => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
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
				<?php if ($person->picture) : ?><img src="<?php echo htmlspecialchars((string) $person->picture, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid flex-shrink-0 rounded" width="200" height="200" style="width:200px;height:200px;object-fit:contain"><?php else : ?><span class="jl-person-placeholder d-flex align-items-center justify-content-center flex-shrink-0 bg-body-secondary text-body-secondary rounded" style="width:200px;height:200px;font-size:10rem" aria-hidden="true"><span class="icon-user"></span></span><?php endif; ?>
				<div class="flex-grow-1">
					<h1 class="mb-1"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h1>
					<?php if (trim((string) $person->nickname) !== '') : ?><div class="text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_NICKNAME', htmlspecialchars((string) $person->nickname, ENT_QUOTES, 'UTF-8')); ?></div><?php endif; ?>
					<div class="d-flex flex-wrap gap-3 mt-2 small text-body-secondary">
						<?php if ($person->club_name) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=club&club_id=' . (int) $person->club_id); ?>"><?php echo htmlspecialchars((string) $person->club_name, ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?>
						<?php if ($person->country_code) : ?><span><?php echo CountryPresentation::html((string) $person->country_code); ?></span><?php endif; ?>
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
		<?php if ($data['player_history']['available'] ?? false) require __DIR__ . '/playerhistory.php'; ?>

		<?php if ($data['career_memberships'] !== []) : ?>
		<h2 class="h4" id="person-career-title"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_MEMBERSHIP_HISTORY'); ?></h2>
		<?php $careerSeasons = []; foreach ($data['career_memberships'] as $membership) $careerSeasons[(int) $membership->season_id][] = $membership; ?>
			<div class="table-responsive jl-person-career" role="region" tabindex="0" aria-labelledby="person-career-title"><table class="table table-striped align-middle" style="table-layout:fixed;width:100%;min-width:40rem;overflow-wrap:anywhere">
				<colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
				<thead class="visually-hidden"><tr>
					<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAREER_PROFILE'); ?></th>
					<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAREER_CLUB'); ?></th>
					<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAREER_POSITION'); ?></th>
					<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAREER_LEAGUE'); ?></th>
				</tr></thead>
				<?php foreach ($careerSeasons as $seasonId => $seasonMemberships) : ?>
				<tbody data-career-season="<?php echo $seasonId; ?>">
				<tr class="table-active"><th scope="rowgroup" colspan="4"><h3 class="h5 mb-0" id="career-season-<?php echo $seasonId; ?>"><?php echo htmlspecialchars((string) $seasonMemberships[0]->season_name, ENT_QUOTES, 'UTF-8'); ?></h3></th></tr>
				<?php foreach ($seasonMemberships as $membership) : ?>
				<tr>
					<td><?php echo htmlspecialchars($membership->profile_name_key ? Text::_((string) $membership->profile_name_key) : (string) $membership->sport_type_name, ENT_QUOTES, 'UTF-8'); ?></td>
					<td><?php if ($membership->membership_club_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=club&club_id=' . (int) $membership->membership_club_id); ?>"><?php echo htmlspecialchars((string) $membership->membership_club_name, ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><?php echo Text::_('JNONE'); ?><?php endif; ?><div class="small text-body-secondary"><?php echo htmlspecialchars((string) $membership->entry_name, ENT_QUOTES, 'UTF-8'); ?></div></td>
					<td><?php echo htmlspecialchars($roleLabel($membership) ?: Text::_($personTypeLabels[(string) $membership->member_person_type] ?? 'COM_JOOMLEAGUE_PARTICIPANT_MEMBER_PARTICIPANT'), ENT_QUOTES, 'UTF-8'); ?></td>
					<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $person->id . '&player_project_id=' . (int) $membership->project_id . '&player_limit=5&player_limitstart=0') . '#player-statistics-title'; ?>"><?php echo htmlspecialchars((string) $membership->competition_name, ENT_QUOTES, 'UTF-8'); ?></a></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
				<?php endforeach; ?>
			</table></div>
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
				<?php require __DIR__ . '/officiating_filters.php'; ?>
				<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMME_EVENT'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ROLE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PERSON_ASSIGNMENT_STATUS'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PERSON_ROLE_CHANGES'); ?></th></tr></thead><tbody>
				<?php foreach ($data['officiating_summary']['projects'] ?? [] as $summary) : ?><tr class="table-light"><th colspan="4"><div class="d-flex flex-wrap justify-content-between gap-2"><span><?php echo htmlspecialchars($summary['project_name'], ENT_QUOTES, 'UTF-8'); ?> <small class="fw-normal text-body-secondary"><?php echo htmlspecialchars($summary['competition_name'] . ' · ' . $summary['season_name'], ENT_QUOTES, 'UTF-8'); ?></small></span><span class="d-flex flex-wrap gap-2"><span class="badge text-bg-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_TOTAL'); ?>: <?php echo (int) $summary['total']; ?></span><span class="badge text-bg-success"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_COMPLETED'); ?>: <?php echo (int) $summary['completed']; ?></span><span class="badge text-bg-light border"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_UPCOMING'); ?>: <?php echo (int) $summary['upcoming']; ?></span></span></div></th></tr><?php if ($summary['site_club_stats'] !== null) : $clubStats = $summary['site_club_stats']; ?><tr><td colspan="4"><div class="small fw-semibold mb-2"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_SITE_CLUB_BALANCE', htmlspecialchars((string) ($officiating['team']['name'] ?? $clubStats['club_name']), ENT_QUOTES, 'UTF-8')); ?></div><div class="mb-2"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_BALANCE_VALUE', (int) $clubStats['played'], (int) $clubStats['wins'], (int) $clubStats['draws'], (int) $clubStats['losses'], htmlspecialchars((string) $clubStats['scored'], ENT_QUOTES, 'UTF-8'), htmlspecialchars((string) $clubStats['conceded'], ENT_QUOTES, 'UTF-8')); ?></div><div class="d-flex flex-wrap gap-2"><?php foreach ($clubStats['opponents'] as $opponentStats) : ?><span class="border rounded px-2 py-1"><strong><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&team_id=' . (int) $opponentStats['team_id']); ?>"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_AGAINST', htmlspecialchars((string) $opponentStats['team_name'], ENT_QUOTES, 'UTF-8')); ?></a></strong> <span class="text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_BALANCE_VALUE', (int) $opponentStats['played'], (int) $opponentStats['wins'], (int) $opponentStats['draws'], (int) $opponentStats['losses'], htmlspecialchars((string) $opponentStats['scored'], ENT_QUOTES, 'UTF-8'), htmlspecialchars((string) $opponentStats['conceded'], ENT_QUOTES, 'UTF-8')); ?></span></span><?php endforeach; ?></div></td></tr><?php endif; ?><?php foreach ($summary['assignments'] as $assignment) : $changes = $data['role_changes_by_match'][(int) $assignment->match_id] ?? []; $clubMatch = $siteClubMatches[(int) $assignment->match_id] ?? null; ?><tr>
					<th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $assignment->match_id); ?>"><?php echo htmlspecialchars((string) ($assignment->match_number ?: $assignment->round_name), ENT_QUOTES, 'UTF-8'); ?></a><?php if ($clubMatch !== null) : ?><div class="fw-normal"><?php echo htmlspecialchars($clubMatch['site_team_name'] . ' ' . $clubMatch['scored'] . ':' . $clubMatch['conceded'] . ' ' . $clubMatch['opponent_team_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><div class="small fw-normal text-body-secondary"><?php echo htmlspecialchars((string) $assignment->project_name, ENT_QUOTES, 'UTF-8'); ?><?php if ($assignment->scheduled_start) : ?> <span aria-hidden="true">&middot;</span> <time datetime="<?php echo htmlspecialchars((string) $assignment->scheduled_start, ENT_QUOTES, 'UTF-8'); ?>"><?php echo HTMLHelper::_('date', $assignment->scheduled_start, Text::_('DATE_FORMAT_LC3'), (string) ($assignment->timezone ?: 'UTC')); ?></time><?php endif; ?></div></th>
					<td><?php echo htmlspecialchars($roleLabel($assignment), ENT_QUOTES, 'UTF-8'); ?><div class="small text-body-secondary"><?php echo $domainLabel($assignment); ?></div></td>
					<td><?php if ((string) $assignment->delegation_status === 'non_delegated') : ?><span class="badge text-bg-warning"><?php echo Text::_('COM_JOOMLEAGUE_DELEGATION_STATUS_NON_DELEGATED'); ?></span><?php else : ?><span class="badge text-bg-success"><?php echo Text::_('COM_JOOMLEAGUE_DELEGATION_STATUS_DELEGATED'); ?></span><?php endif; ?><?php if ((int) $assignment->published !== 1) : ?><div class="small text-body-secondary mt-1"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_ORIGINAL_NOMINATION'); ?></div><?php endif; ?></td>
					<td><?php $renderedChange = false; foreach ($changes as $change) : $isIncoming = (int) $change->incoming_match_actor_role_id === (int) $assignment->id; $isOutgoing = (int) $change->outgoing_match_actor_role_id === (int) $assignment->id; if (!$isIncoming && !$isOutgoing) continue; $renderedChange = true; ?><div class="small mb-1"><span class="icon-<?php echo $isIncoming ? 'arrow-left' : 'arrow-right'; ?>" aria-hidden="true"></span> <?php echo Text::_($isIncoming ? 'COM_JOOMLEAGUE_PERSON_ROLE_CHANGE_IN' : 'COM_JOOMLEAGUE_PERSON_ROLE_CHANGE_OUT'); ?>: <?php echo htmlspecialchars($roleLabel($change), ENT_QUOTES, 'UTF-8'); ?><?php if ($change->clock_value !== null) : ?>, <?php echo htmlspecialchars((string) $change->clock_value . ' ' . (string) $change->clock_unit, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div><?php endforeach; ?><?php if (!$renderedChange) echo Text::_('JNONE'); ?></td>
				</tr><?php endforeach; endforeach; ?>
				</tbody><tfoot><tr class="fw-semibold"><th><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_ALL_PROJECTS'); ?></th><td colspan="3"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_TOTAL'); ?>: <?php echo (int) ($data['officiating_summary']['total'] ?? 0); ?><?php if (($data['officiating_summary']['site_club_stats'] ?? null) !== null) : $clubStats = $data['officiating_summary']['site_club_stats']; ?><div class="mt-2 fw-normal"><strong><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_SITE_CLUB_BALANCE', htmlspecialchars((string) ($officiating['team']['name'] ?? $clubStats['club_name']), ENT_QUOTES, 'UTF-8')); ?></strong>: <?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_BALANCE_VALUE', (int) $clubStats['played'], (int) $clubStats['wins'], (int) $clubStats['draws'], (int) $clubStats['losses'], htmlspecialchars((string) $clubStats['scored'], ENT_QUOTES, 'UTF-8'), htmlspecialchars((string) $clubStats['conceded'], ENT_QUOTES, 'UTF-8')); ?><div class="d-flex flex-wrap gap-2 mt-2"><?php foreach ($clubStats['opponents'] as $opponentStats) : ?><span class="border rounded px-2 py-1"><strong><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_AGAINST', htmlspecialchars((string) $opponentStats['team_name'], ENT_QUOTES, 'UTF-8')); ?></strong> <span class="text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_OFFICIATED_BALANCE_VALUE', (int) $opponentStats['played'], (int) $opponentStats['wins'], (int) $opponentStats['draws'], (int) $opponentStats['losses'], htmlspecialchars((string) $opponentStats['scored'], ENT_QUOTES, 'UTF-8'), htmlspecialchars((string) $opponentStats['conceded'], ENT_QUOTES, 'UTF-8')); ?></span></span><?php endforeach; ?></div></div><?php endif; ?></td></tr></tfoot></table></div>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</div>
