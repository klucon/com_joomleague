<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Site\Service\PersonNameHelper;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$team = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';
$projectTeamInfo = $team ? trim((string) ($team->info ?? '')) : '';
$teamInfo = $projectTeamInfo !== '' ? $projectTeamInfo : ($team ? trim((string) ($team->team_info ?? '')) : '');
$params = $this->templateParams;
$show = static fn (string $name, bool $default = true): bool => array_key_exists($name, $params) && $params[$name] !== '' ? (bool) $params[$name] : $default;
$param = static fn (string $name, $default = null) => array_key_exists($name, $params) && $params[$name] !== '' ? $params[$name] : $default;

$layout = (string) $param('layout', 'table') === 'cards' ? 'cards' : 'table';
$playersLayoutRaw = (string) $param('show_players_layout', '');
$playersLayout = $playersLayoutRaw === 'player_card' ? 'cards' : ($playersLayoutRaw === 'player_standard' ? 'table' : $layout);
$staffLayoutRaw = (string) $param('show_staff_layout', '');
$staffLayout = $staffLayoutRaw === 'staff_card' ? 'cards' : ($staffLayoutRaw === 'staff_standard' ? 'table' : $layout);

$nameFormatPlayers = (string) $param('name_format', '0');
$nameFormatStaff = (string) $param('name_format_staff', '0');
$birthdayModePlayers = (int) $param('show_birthday', 1);
$birthdayModeStaff = (int) $param('show_birthday_staff', 1);
$showPlayerIcon = $show('show_player_icon', true);
$playerPicWidth = (int) $param('player_picture_width', 0);
$playerPicHeight = (int) $param('player_picture_height', 40);
$showStaffIcon = $show('show_staff_icon', true);
$staffPicWidth = (int) $param('staff_picture_width', 0);
$staffPicHeight = (int) $param('staff_picture_height', 40);
$linkPlayer = $show('link_player', true);
$linkStaff = $show('link_staff', true);
$showCountryFlagStaff = $show('show_country_flag_staff', true);
$showTeamShortform = $show('show_team_shortform', true);
$showTeamLogo = $show('show_team_logo', true);
$picture = (string) $param('show_picture', 'team_picture');
$teamPicWidth = (int) $param('team_picture_width', 0);
$teamPicHeight = (int) $param('team_picture_height', 150);
$showGamesPlayed = $show('show_games_played', true);
$showSubstitutionStats = $show('show_substitution_stats', true);
$showEventsStats = $show('show_events_stats', true);
$showTotals = $show('show_totals', true);

$styleClass1 = trim((string) $param('style_class1', ''));
$styleClass2 = trim((string) $param('style_class2', ''));
$rowClass = static function (int $index) use ($styleClass1, $styleClass2): string {
	$class = $index % 2 === 0 ? $styleClass1 : $styleClass2;

	return $class !== '' ? ' ' . $class : '';
};

$pictureUrl = static function (?string $picture): ?string {
	$picture = trim((string) $picture);
	if ($picture === '') {
		$picture = trim((string) ComponentHelper::getParams('com_joomleague')->get('placeholder_person_picture', ''));
	}
	if ($picture === '') {
		return null;
	}

	return preg_match('#^https?://#i', $picture) ? $picture : Uri::root(true) . '/' . ltrim($picture, '/');
};
$schemaPictureUrl = static function (?string $picture): ?string {
	$picture = trim((string) $picture);

	if ($picture === '') {
		return null;
	}

	return preg_match('#^https?://#i', $picture) ? $picture : Uri::root(true) . '/' . ltrim($picture, '/');
};

// 0=skryto, 1=datum+věk, 2=jen datum, 3=jen věk, 4=jen rok narození (stejné jako u osoby).
$birthdayLabel = static function (object $person, int $mode): ?string {
	if ($mode < 1 || empty($person->birthday) || strpos((string) $person->birthday, '0000-00-00') === 0) {
		return null;
	}
	$years = null;
	try {
		$from = new \DateTime((string) $person->birthday);
		$to = !empty($person->deathday) && strpos((string) $person->deathday, '0000-00-00') !== 0 ? new \DateTime((string) $person->deathday) : new \DateTime('now');
		$years = (int) $from->diff($to)->y;
	} catch (\Throwable $e) {
		// beze změny, $years zůstane null
	}

	return match ($mode) {
		3 => $years !== null ? $years . ' ' . Text::_('COM_JOOMLEAGUE_SITE_YEARS') : null,
		4 => substr((string) $person->birthday, 0, 4),
		1 => (string) $person->birthday . ($years !== null ? ' (' . $years . ' ' . Text::_('COM_JOOMLEAGUE_SITE_YEARS') . ')' : ''),
		default => (string) $person->birthday,
	};
};

if ($team) {
	$teamLogoPath = match ($picture) {
		'projectteam_picture' => (string) ($team->picture ?? ''),
		'logo_small' => (string) ($team->club_logo_small ?? ''),
		'logo_middle' => (string) ($team->club_logo_middle ?? ''),
		'logo_big' => (string) ($team->club_logo_big ?? ''),
		default => (string) ($team->team_picture ?? ''),
	};
}

$eventColumns = [];
foreach ($this->rosterPlayerStats as $stat) {
	foreach ($stat->events as $eventTypeId => $ev) {
		if (!isset($eventColumns[$eventTypeId])) {
			$eventColumns[$eventTypeId] = $ev->name;
		}
	}
}
$translateValue = static function (?string $value): string {
	$value = trim((string) $value);

	return $value === '' ? '' : Text::_($value);
};

if ($team) {
	$teamUrl = StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id, false));
	$personSchema = static function (object $person, string $format, ?string $position = null) use ($team, $schemaPictureUrl): ?array {
		$name = PersonNameHelper::format((string) $person->firstname, (string) $person->lastname, (string) ($person->nickname ?? ''), $format);

		if ($name === '') {
			return null;
		}

		$personUrl = !empty($person->person_id)
			? StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $person->person_id . '&project_id=' . (int) $team->project_id, false))
			: null;

		return [
			'@type' => 'Person',
			'@id' => $personUrl ? $personUrl . '#person' : null,
			'name' => $name,
			'url' => $personUrl,
			'image' => $schemaPictureUrl($person->person_picture ?? null),
			'nationality' => $person->person_country ?? null,
			'jobTitle' => $position,
		];
	};
	$schemaTeamLogo = isset($teamLogoPath) && trim((string) $teamLogoPath) !== '' ? $schemaPictureUrl($teamLogoPath) : null;

	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'SportsTeam',
		'@id' => $teamUrl ? $teamUrl . '#sportsteam' : null,
		'name' => (string) $team->team_name,
		'url' => $teamUrl,
		'logo' => $schemaTeamLogo,
		'memberOf' => [
			'@type' => 'SportsOrganization',
			'@id' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $team->project_id, false)) . '#competition',
			'name' => (string) ($this->project->name ?? ''),
		],
		'athlete' => array_values(array_filter(array_map(
			static fn (object $player): ?array => $personSchema($player, $nameFormatPlayers, $translateValue($player->position_name ?? '')),
			$this->items
		))),
		'coach' => array_values(array_filter(array_map(
			static fn (object $staffMember): ?array => $personSchema($staffMember, $nameFormatStaff, $translateValue($staffMember->position_name ?? '')),
			$this->rosterStaff
		))),
		'mainEntityOfPage' => StructuredDataHelper::collectionPage(
			Text::_('COM_JOOMLEAGUE_SITE_ROSTER'),
			array_values(array_filter(array_map(
				static fn (object $player): ?array => $personSchema($player, $nameFormatPlayers, $translateValue($player->position_name ?? '')),
				$this->items
			))),
			$this->projectLabel()
		),
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$team) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_NOT_FOUND'); ?></div><?php return; endif; ?>

	<section class="jl-site-hero mb-4">
		<div class="d-flex align-items-center gap-3 flex-wrap">
			<?php if ($showTeamLogo && ($logoUrl = $pictureUrl($teamLogoPath))) : ?>
				<img class="rounded" src="<?php echo $this->escape($logoUrl); ?>" alt="" loading="lazy" style="<?php echo $teamPicHeight > 0 ? 'max-height:' . $teamPicHeight . 'px;' : ''; ?><?php echo $teamPicWidth > 0 ? 'max-width:' . $teamPicWidth . 'px;' : 'width:auto;'; ?>">
			<?php endif; ?>
			<div>
				<div class="jl-site-eyebrow"><?php echo $this->escape(trim($this->projectLabel() . ' · ' . (string) ($showTeamShortform && !empty($team->team_short_name) ? $team->team_short_name : $team->team_name), ' ·')); ?></div>
				<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROSTER'); ?></h1>
			</div>
		</div>
	</section>

	<?php if ($show('show_description') && $teamInfo !== '') : ?>
		<div class="jl-site-panel mb-4">
			<div><?php echo HTMLHelper::_('content.prepare', $teamInfo); ?></div>
		</div>
	<?php endif; ?>

	<?php if ($show('show_players')) : ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYERS'); ?></h2>
			<?php if (!$this->items) : ?>
				<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div>
			<?php elseif ($playersLayout === 'cards') : ?>
				<div class="jl-site-grid">
					<?php foreach ($this->items as $player) : ?>
						<article class="jl-site-card">
							<?php if ($showPlayerIcon && ($photo = $pictureUrl($player->person_picture ?? null))) : ?>
								<img class="rounded" src="<?php echo $this->escape($photo); ?>" alt="" loading="lazy" style="<?php echo $playerPicHeight > 0 ? 'max-height:' . $playerPicHeight . 'px;' : ''; ?><?php echo $playerPicWidth > 0 ? 'max-width:' . $playerPicWidth . 'px;' : 'width:auto;'; ?>">
							<?php endif; ?>
							<strong>
								<?php if ($show('show_jersey_number') && $player->jerseynumber !== null) : ?>#<?php echo (int) $player->jerseynumber; ?> · <?php endif; ?>
								<?php $playerName = PersonNameHelper::format((string) $player->firstname, (string) $player->lastname, (string) ($player->nickname ?? ''), $nameFormatPlayers); ?>
								<?php if ($linkPlayer) : ?>
									<a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $player->person_id . '&project_id=' . (int) $team->project_id); ?>"><?php echo $this->escape($playerName); ?></a>
								<?php else : ?>
									<?php echo $this->escape($playerName); ?>
								<?php endif; ?>
							</strong>
							<?php if ($show('show_position') && !empty($player->position_name)) : ?><span class="jl-site-muted"><?php echo $this->escape($translateValue($player->position_name)); ?></span><?php endif; ?>
							<?php if ($playerBirthdayText = ($birthdayModePlayers > 0 ? $birthdayLabel($player, $birthdayModePlayers) : null)) : ?><span class="jl-site-muted small"><?php echo $this->escape($playerBirthdayText); ?></span><?php endif; ?>
							<?php if ($show('show_country_flag')) : ?><?php echo LayoutHelper::render('joomleague.flag', ['code' => $player->person_country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?><?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<table class="table jl-site-table align-middle">
					<thead>
						<tr>
							<?php if ($showPlayerIcon) : ?><th></th><?php endif; ?>
							<?php if ($show('show_jersey_number')) : ?><th>#</th><?php endif; ?>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th>
							<?php if ($show('show_position')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POSITION'); ?></th><?php endif; ?>
							<?php if ($birthdayModePlayers > 0) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_BIRTHDAY'); ?></th><?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($this->items as $index => $player) : ?>
							<tr class="<?php echo trim($rowClass((int) $index)); ?>">
								<?php if ($showPlayerIcon) : ?>
									<td>
										<?php if ($photo = $pictureUrl($player->person_picture ?? null)) : ?>
											<img class="rounded" src="<?php echo $this->escape($photo); ?>" alt="" loading="lazy" style="<?php echo $playerPicHeight > 0 ? 'max-height:' . $playerPicHeight . 'px;' : ''; ?><?php echo $playerPicWidth > 0 ? 'max-width:' . $playerPicWidth . 'px;' : 'width:auto;'; ?>">
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<?php if ($show('show_jersey_number')) : ?><td><?php echo $this->escape((string) ($player->jerseynumber ?? '')); ?></td><?php endif; ?>
								<td>
									<?php if ($show('show_country_flag')) : ?><?php echo LayoutHelper::render('joomleague.flag', ['code' => $player->person_country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?> <?php endif; ?>
									<?php $playerName = PersonNameHelper::format((string) $player->firstname, (string) $player->lastname, (string) ($player->nickname ?? ''), $nameFormatPlayers); ?>
									<?php if ($linkPlayer) : ?>
										<a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $player->person_id . '&project_id=' . (int) $team->project_id); ?>"><?php echo $this->escape($playerName); ?></a>
									<?php else : ?>
										<?php echo $this->escape($playerName); ?>
									<?php endif; ?>
								</td>
								<?php if ($show('show_position')) : ?><td><?php echo $this->escape($translateValue($player->position_name ?? '')); ?></td><?php endif; ?>
								<?php if ($birthdayModePlayers > 0) : ?><td class="jl-site-muted"><?php echo $this->escape((string) ($birthdayLabel($player, $birthdayModePlayers) ?? '')); ?></td><?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ($show('show_stats') && $this->rosterPlayerStats !== []) : ?>
		<?php
		$total = (object) ['played' => 0, 'started' => 0, 'sub_in' => 0, 'sub_out' => 0, 'minutes' => 0, 'events' => []];
		foreach ($this->rosterPlayerStats as $stat) {
			$total->played += $stat->played;
			$total->started += $stat->started;
			$total->sub_in += $stat->sub_in;
			$total->sub_out += $stat->sub_out;
			$total->minutes += $stat->minutes;
			foreach ($stat->events as $eventTypeId => $ev) {
				$total->events[$eventTypeId] = (object) ['name' => $ev->name, 'total' => ($total->events[$eventTypeId]->total ?? 0.0) + $ev->total];
			}
		}
		?>
		<div class="jl-site-panel table-responsive mb-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYER_STATS'); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th>
						<?php if ($showGamesPlayed) : ?>
							<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED'); ?></th>
							<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_SITE_STARTED'); ?></th>
						<?php endif; ?>
						<?php if ($showSubstitutionStats) : ?>
							<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SUB_IN'); ?></th>
							<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SUB_OUT'); ?></th>
						<?php endif; ?>
						<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MINUTES'); ?></th>
						<?php if ($showEventsStats) : ?>
							<?php foreach ($eventColumns as $eventName) : ?>
								<th class="text-end"><?php echo $this->escape($translateValue($eventName)); ?></th>
							<?php endforeach; ?>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->items as $index => $player) : ?>
						<?php $stat = $this->rosterPlayerStats[(int) $player->id] ?? null; ?>
						<?php if (!$stat) : continue; endif; ?>
						<tr class="<?php echo trim($rowClass((int) $index)); ?>">
							<td>
								<?php $playerName = PersonNameHelper::format((string) $player->firstname, (string) $player->lastname, (string) ($player->nickname ?? ''), $nameFormatPlayers); ?>
								<?php if ($linkPlayer) : ?>
									<a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $player->person_id . '&project_id=' . (int) $team->project_id); ?>"><?php echo $this->escape($playerName); ?></a>
								<?php else : ?>
									<?php echo $this->escape($playerName); ?>
								<?php endif; ?>
							</td>
							<?php if ($showGamesPlayed) : ?>
								<td class="text-end"><?php echo (int) $stat->played; ?></td>
								<td class="text-end"><?php echo (int) $stat->started; ?></td>
							<?php endif; ?>
							<?php if ($showSubstitutionStats) : ?>
								<td class="text-end"><?php echo (int) $stat->sub_in; ?></td>
								<td class="text-end"><?php echo (int) $stat->sub_out; ?></td>
							<?php endif; ?>
							<td class="text-end"><?php echo (int) $stat->minutes; ?></td>
							<?php if ($showEventsStats) : ?>
								<?php foreach ($eventColumns as $eventTypeId => $eventName) : ?>
									<td class="text-end"><?php echo isset($stat->events[$eventTypeId]) ? rtrim(rtrim(number_format((float) $stat->events[$eventTypeId]->total, 1), '0'), '.') : ''; ?></td>
								<?php endforeach; ?>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<?php if ($showTotals) : ?>
					<tfoot>
						<tr class="fw-bold">
							<td><?php echo Text::_('COM_JOOMLEAGUE_SITE_CAREER_TOTAL'); ?></td>
							<?php if ($showGamesPlayed) : ?>
								<td class="text-end"><?php echo (int) $total->played; ?></td>
								<td class="text-end"><?php echo (int) $total->started; ?></td>
							<?php endif; ?>
							<?php if ($showSubstitutionStats) : ?>
								<td class="text-end"><?php echo (int) $total->sub_in; ?></td>
								<td class="text-end"><?php echo (int) $total->sub_out; ?></td>
							<?php endif; ?>
							<td class="text-end"><?php echo (int) $total->minutes; ?></td>
							<?php if ($showEventsStats) : ?>
								<?php foreach ($eventColumns as $eventTypeId => $eventName) : ?>
									<td class="text-end"><?php echo isset($total->events[$eventTypeId]) ? rtrim(rtrim(number_format((float) $total->events[$eventTypeId]->total, 1), '0'), '.') : ''; ?></td>
								<?php endforeach; ?>
							<?php endif; ?>
						</tr>
					</tfoot>
				<?php endif; ?>
			</table>
		</div>
	<?php endif; ?>

	<?php if ($show('show_staff') && $this->rosterStaff !== []) : ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_STAFF'); ?></h2>
			<?php if ($staffLayout === 'cards') : ?>
				<div class="jl-site-grid">
					<?php foreach ($this->rosterStaff as $staffMember) : ?>
						<article class="jl-site-card">
							<?php if ($showStaffIcon && ($photo = $pictureUrl($staffMember->person_picture ?? null))) : ?>
								<img class="rounded" src="<?php echo $this->escape($photo); ?>" alt="" loading="lazy" style="<?php echo $staffPicHeight > 0 ? 'max-height:' . $staffPicHeight . 'px;' : ''; ?><?php echo $staffPicWidth > 0 ? 'max-width:' . $staffPicWidth . 'px;' : 'width:auto;'; ?>">
							<?php endif; ?>
							<strong>
								<?php $staffName = PersonNameHelper::format((string) $staffMember->firstname, (string) $staffMember->lastname, (string) ($staffMember->nickname ?? ''), $nameFormatStaff); ?>
								<?php if ($linkStaff) : ?>
									<a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $staffMember->person_id . '&project_id=' . (int) $team->project_id); ?>"><?php echo $this->escape($staffName); ?></a>
								<?php else : ?>
									<?php echo $this->escape($staffName); ?>
								<?php endif; ?>
							</strong>
							<?php if (!empty($staffMember->position_name)) : ?><span class="jl-site-muted"><?php echo $this->escape($translateValue($staffMember->position_name)); ?></span><?php endif; ?>
							<?php if ($staffBirthdayText = ($birthdayModeStaff > 0 ? $birthdayLabel($staffMember, $birthdayModeStaff) : null)) : ?><span class="jl-site-muted small"><?php echo $this->escape($staffBirthdayText); ?></span><?php endif; ?>
							<?php if ($showCountryFlagStaff) : ?><?php echo LayoutHelper::render('joomleague.flag', ['code' => $staffMember->person_country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?><?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<table class="table jl-site-table align-middle">
					<thead>
						<tr>
							<?php if ($showStaffIcon) : ?><th></th><?php endif; ?>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POSITION'); ?></th>
							<?php if ($birthdayModeStaff > 0) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_BIRTHDAY'); ?></th><?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($this->rosterStaff as $index => $staffMember) : ?>
							<tr class="<?php echo trim($rowClass((int) $index)); ?>">
								<?php if ($showStaffIcon) : ?>
									<td>
										<?php if ($photo = $pictureUrl($staffMember->person_picture ?? null)) : ?>
											<img class="rounded" src="<?php echo $this->escape($photo); ?>" alt="" loading="lazy" style="<?php echo $staffPicHeight > 0 ? 'max-height:' . $staffPicHeight . 'px;' : ''; ?><?php echo $staffPicWidth > 0 ? 'max-width:' . $staffPicWidth . 'px;' : 'width:auto;'; ?>">
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<td>
									<?php if ($showCountryFlagStaff) : ?><?php echo LayoutHelper::render('joomleague.flag', ['code' => $staffMember->person_country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?> <?php endif; ?>
									<?php $staffName = PersonNameHelper::format((string) $staffMember->firstname, (string) $staffMember->lastname, (string) ($staffMember->nickname ?? ''), $nameFormatStaff); ?>
									<?php if ($linkStaff) : ?>
										<a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $staffMember->person_id . '&project_id=' . (int) $team->project_id); ?>"><?php echo $this->escape($staffName); ?></a>
									<?php else : ?>
										<?php echo $this->escape($staffName); ?>
									<?php endif; ?>
								</td>
								<td><?php echo $this->escape($translateValue($staffMember->position_name ?? '')); ?></td>
								<?php if ($birthdayModeStaff > 0) : ?><td class="jl-site-muted"><?php echo $this->escape((string) ($birthdayLabel($staffMember, $birthdayModeStaff) ?? '')); ?></td><?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
