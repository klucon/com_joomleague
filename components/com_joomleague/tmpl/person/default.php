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

$person = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';
$personPublicProfile = $person ? trim((string) ($person->info ?? '')) : '';
$personPublicProfileText = $personPublicProfile !== '' ? trim(strip_tags($personPublicProfile)) : '';

// player.xml / staff.xml / referee.xml – sjednocený pohled 'person' může na jedné
// stránce zobrazovat hráčskou, realizační i rozhodcovskou historii najednou, takže
// pro každou sekci se čte konfigurace té role, ke které skutečně patří.
$playerParams = $this->playerTemplateParams;
$staffParams = $this->staffTemplateParams;
$refereeParams = $this->refereeTemplateParams;

$show = static fn (array $params, string $name, bool $default = true): bool =>
	array_key_exists($name, $params) && $params[$name] !== '' ? (bool) $params[$name] : $default;
$param = static fn (array $params, string $name, $default = null) =>
	array_key_exists($name, $params) && $params[$name] !== '' ? $params[$name] : $default;

$hasPlayerRole = $this->playerHistory !== [] || $this->playerMatches !== [];
$hasStaffRole = $this->staffHistory !== [];
$hasRefereeRole = $this->refereeHistory !== [];

// Profil osoby (foto, národnost, narození, kontakt, ...) se řídí konfigurací primární role.
$profileParams = $hasPlayerRole ? $playerParams : ($hasStaffRole ? $staffParams : $refereeParams);
$profileRole = $hasPlayerRole ? 'player' : ($hasStaffRole ? 'staff' : 'referee');

$nameFormat = (string) $param($profileParams, 'name_format', '0');

$fullName = static function (object $person) use ($nameFormat): string {
	return PersonNameHelper::format(
		(string) ($person->firstname ?? ''),
		(string) ($person->lastname ?? ''),
		(string) ($person->nickname ?? ''),
		$nameFormat
	);
};

// věk z data narození (a případně úmrtí)
$age = static function (?string $birthday, ?string $deathday = null): ?int {
	if (!$birthday || strpos($birthday, '0000-00-00') === 0) {
		return null;
	}
	try {
		$from = new \DateTime($birthday);
		$to   = $deathday && strpos($deathday, '0000-00-00') !== 0 ? new \DateTime($deathday) : new \DateTime('now');
		return (int) $from->diff($to)->y;
	} catch (\Throwable $e) {
		return null;
	}
};

// URL fotky osoby (picture je cesta relativní ke kořeni Joomly)
$pictureUrl = static function (?string $picture): ?string {
	$picture = trim((string) $picture);
	if ($picture === '') {
		$picture = trim((string) ComponentHelper::getParams('com_joomleague')->get('placeholder_person_picture', ''));
	}
	if ($picture === '') {
		return null;
	}
	if (preg_match('#^https?://#i', $picture)) {
		return $picture;
	}
	return Uri::root(true) . '/' . ltrim($picture, '/');
};
$schemaPictureUrl = static function (?string $picture): ?string {
	$picture = trim((string) $picture);

	if ($picture === '') {
		return null;
	}

	return preg_match('#^https?://#i', $picture) ? $picture : Uri::root(true) . '/' . ltrim($picture, '/');
};

$translateValue = static function (?string $value): string {
	$value = trim((string) $value);

	return $value === '' ? '' : Text::_($value);
};

// COM_JOOMLEAGUE_FES_*_PARAM_LABEL_SHOW_BIRTHDAY: 0=skryto, 1=datum+věk, 2=jen datum, 3=jen věk, 4=jen rok narození.
$birthdayMode = (int) $param($profileParams, 'show_birthday', 1);
$showPhoto = $show($profileParams, $profileRole === 'player' ? 'show_player_photo' : 'show_photo', true);
$picHeight = (int) $param($profileParams, 'picture_height', 150);
$picWidth = (int) $param($profileParams, 'picture_width', 0);
$showNationality = $show($profileParams, 'show_nationality', false);
$showHeight = $show($profileParams, 'show_person_height', true);
$showWeight = $show($profileParams, 'show_person_weight', true);
$showRegnr = $show($profileParams, 'show_person_regnr', true);
$showAddress = $show($profileParams, 'show_person_address', false);
$showPhone = $show($profileParams, 'show_person_phone', false);
$showMobile = $show($profileParams, 'show_person_mobile', false);
$showEmail = $show($profileParams, 'show_person_email', false);
$showWebsite = $show($profileParams, 'show_person_website', false);
$showGeneralDescription = $show($profileParams, $profileRole === 'player' ? 'show_player_general_description' : 'show_general_description', false);
$showDescription = $show($profileParams, 'show_description', true);

$renderHistory = function (string $title, array $items, bool $showNumber = false) use ($translateValue): void {
	?>
	<div class="jl-site-panel table-responsive mb-4">
		<h2><?php echo Text::_($title); ?></h2>
		<?php if ($items === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div>
		<?php else : ?>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POSITION'); ?></th>
						<?php if ($showNumber) : ?>
							<th class="text-end">#</th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($items as $item) : ?>
						<tr>
							<td>
								<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $item->project_id); ?>">
									<?php echo $this->escape((string) $item->project_name); ?>
								</a>
								<div class="jl-site-muted small">
									<?php echo $this->escape(trim((string) ($item->league_name ?? '') . ' · ' . (string) ($item->season_name ?? ''), ' ·')); ?>
								</div>
							</td>
							<td>
								<?php if (!empty($item->projectteam_id)) : ?>
									<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $item->projectteam_id); ?>">
										<?php echo $this->escape((string) ($item->team_name ?? '')); ?>
									</a>
								<?php else : ?>
									<?php echo $this->escape((string) ($item->team_name ?? '')); ?>
								<?php endif; ?>
							</td>
							<td><?php echo $this->escape($translateValue($item->position_name ?? '')); ?></td>
							<?php if ($showNumber) : ?>
								<td class="text-end"><?php echo $this->escape((string) ($item->jerseynumber ?? '')); ?></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
};

// Sekce ovládané pořadím z player.xml (show_order_plinfo/description/gameshistory/plstats/plcareer).
// Když osoba nemá hráčskou historii, chybí i konfigurace pořadí – řadí se pak podle výchozích čísel
// a "hráčské" sekce stejně nevykreslí nic (nejsou data), takže na pořadí v tom případě nezáleží.
$renderInfoSection = function () use (
	$person, $fullName, $showPhoto, $pictureUrl, $picHeight, $picWidth, $birthdayMode, $age,
	$showHeight, $showWeight, $showNationality, $jlFlagPath, $showRegnr, $showAddress, $showEmail,
	$showPhone, $showMobile, $showWebsite, $showGeneralDescription, $showDescription, $personPublicProfileText, $translateValue
): void {
	?>
	<section class="jl-site-hero mb-4">
		<div class="d-flex align-items-center gap-3 flex-wrap">
			<?php if ($showPhoto && ($photo = $pictureUrl($person->picture ?? null))) : ?>
				<?php
				$photoStyle = ($picHeight > 0 ? 'max-height:' . $picHeight . 'px;' : '') . ($picWidth > 0 ? 'max-width:' . $picWidth . 'px;' : 'width:auto;');
				?>
				<img class="jl-person-photo rounded" src="<?php echo $this->escape($photo); ?>" alt="<?php echo $this->escape($fullName($person)); ?>" loading="lazy" style="<?php echo $this->escape($photoStyle); ?>">
			<?php endif; ?>
			<div>
				<div class="jl-site-eyebrow"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></div>
				<h1 class="jl-site-title mb-1"><?php echo $this->escape($fullName($person)); ?></h1>
				<?php if (!empty($person->nickname)) : ?>
					<p class="jl-site-muted mb-0">„<?php echo $this->escape((string) $person->nickname); ?>"</p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<div class="jl-site-grid mb-4">
		<div class="jl-site-panel">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON_PROFILE'); ?></h2>
			<dl class="row mb-0">
				<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_POSITION'); ?></dt>
				<dd class="col-sm-8"><?php echo $this->escape(!empty($person->default_position_name) ? $translateValue($person->default_position_name) : Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></dd>
				<?php if ($birthdayMode > 0 && !empty($person->birthday) && strpos((string) $person->birthday, '0000-00-00') !== 0) : ?>
					<?php $years = $age($person->birthday, $person->deathday ?? null); ?>
					<?php if ($birthdayMode === 3) : ?>
						<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_AGE'); ?></dt>
						<dd class="col-sm-8"><?php echo $years !== null ? $years . ' ' . Text::_('COM_JOOMLEAGUE_SITE_YEARS') : Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></dd>
					<?php elseif ($birthdayMode === 4) : ?>
						<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_YEAR_OF_BIRTH'); ?></dt>
						<dd class="col-sm-8"><?php echo $this->escape(substr((string) $person->birthday, 0, 4)); ?></dd>
					<?php else : ?>
						<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_BIRTHDAY'); ?></dt>
						<dd class="col-sm-8">
							<?php echo $this->escape((string) $person->birthday); ?>
							<?php if ($birthdayMode === 1 && $years !== null) : ?>
								<span class="jl-site-muted">(<?php echo $years . ' ' . Text::_('COM_JOOMLEAGUE_SITE_YEARS'); ?>)</span>
							<?php endif; ?>
						</dd>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ($showHeight) : ?>
					<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_HEIGHT'); ?></dt>
					<dd class="col-sm-8"><?php echo $person->height ? (int) $person->height . ' cm' : Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></dd>
				<?php endif; ?>
				<?php if ($showWeight) : ?>
					<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_WEIGHT'); ?></dt>
					<dd class="col-sm-8"><?php echo $person->weight ? (int) $person->weight . ' kg' : Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></dd>
				<?php endif; ?>
				<?php if ($showNationality && !empty($person->country)) : ?>
					<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_COUNTRY'); ?></dt>
					<dd class="col-sm-8"><?php echo LayoutHelper::render('joomleague.flag', ['code' => $person->country], $jlFlagPath); ?></dd>
				<?php endif; ?>
				<?php if ($showRegnr && !empty($person->knvbnr)) : ?>
					<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_REG_NUMBER'); ?></dt>
					<dd class="col-sm-8"><?php echo $this->escape((string) $person->knvbnr); ?></dd>
				<?php endif; ?>
			</dl>
		</div>
		<div class="jl-site-panel">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_CONTACT'); ?></h2>
			<?php $addr = trim((string) ($person->location ?? '') . ' ' . (string) ($person->state ?? '')); ?>
			<?php if ($showAddress && $addr !== '') : ?>
				<p class="mb-1"><?php echo $this->escape($addr); ?></p>
			<?php endif; ?>
			<?php if ($showEmail && !empty($person->email)) : ?>
				<p class="mb-1"><?php echo Text::_('COM_JOOMLEAGUE_SITE_EMAIL'); ?>: <a href="mailto:<?php echo $this->escape((string) $person->email); ?>"><?php echo $this->escape((string) $person->email); ?></a></p>
			<?php endif; ?>
			<?php if ($showPhone && !empty($person->phone)) : ?>
				<p class="mb-1"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PHONE'); ?>: <?php echo $this->escape((string) $person->phone); ?></p>
			<?php endif; ?>
			<?php if ($showMobile && !empty($person->mobile)) : ?>
				<p class="mb-1"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MOBILE'); ?>: <?php echo $this->escape((string) $person->mobile); ?></p>
			<?php endif; ?>
			<?php if ($showWebsite && !empty($person->website)) : ?>
				<p class="mb-1"><a href="<?php echo $this->escape((string) $person->website); ?>" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_SITE_WEBSITE'); ?></a></p>
			<?php endif; ?>
			<?php if ($showGeneralDescription && !$showDescription && $personPublicProfileText !== '') : ?>
				<p class="jl-site-muted mb-0"><?php echo $this->escape($personPublicProfileText); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
};

$renderDescriptionSection = function () use ($personPublicProfile, $showDescription): void {
	if (!$showDescription || $personPublicProfile === '') {
		return;
	}
	?>
	<div class="jl-site-panel mb-4">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_DESCRIPTION'); ?></h2>
		<div class="jl-site-richtext"><?php echo HTMLHelper::_('content.prepare', $personPublicProfile); ?></div>
	</div>
	<?php
};

$renderGameshistorySection = function () use ($show, $playerParams, $translateValue): void {
	if (!$show($playerParams, 'show_gameshistory', true) || empty($this->playerMatches)) {
		return;
	}
	?>
	<div class="jl-site-panel table-responsive mb-4">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_GAMES_HISTORY'); ?></h2>
		<table class="table jl-site-table align-middle">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCH'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POSITION'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PARTICIPATION'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->playerMatches as $g) : ?>
					<?php
					$hasResult = $g->team1_result !== null && $g->team2_result !== null;
					$score     = $hasResult ? (int) $g->team1_result . ':' . (int) $g->team2_result : '–';
					$homeMine  = (int) $g->player_projectteam_id === (int) $g->projectteam1_id;
					$awayMine  = (int) $g->player_projectteam_id === (int) $g->projectteam2_id;
					$hasDate   = !empty($g->match_date) && strpos((string) $g->match_date, '0000-00-00') !== 0;
					?>
					<tr>
						<td class="text-nowrap"><?php echo $hasDate ? $this->escape(\Joomla\CMS\HTML\HTMLHelper::_('date', $g->match_date, Text::_('DATE_FORMAT_LC4'))) : ''; ?></td>
						<td>
							<a href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $g->match_id); ?>">
								<span class="<?php echo $homeMine ? 'fw-bold' : ''; ?>"><?php echo $this->escape((string) $g->home_team_name); ?></span>
								<strong class="mx-1"><?php echo $this->escape($score); ?></strong>
								<span class="<?php echo $awayMine ? 'fw-bold' : ''; ?>"><?php echo $this->escape((string) $g->away_team_name); ?></span>
							</a>
							<?php if (!empty($g->round_name)) : ?>
								<div class="jl-site-muted small"><?php echo $this->escape((string) $g->round_name); ?></div>
							<?php endif; ?>
						</td>
						<td><?php echo $this->escape($translateValue($g->position_name ?? '')); ?></td>
						<td class="small">
							<?php $ms = $this->playerMatchStats[(int) $g->match_id] ?? null; ?>
							<?php if ($ms) : ?>
								<?php if ($ms->sub_in > 0) : ?>
									<span class="text-success" title="<?php echo Text::_('COM_JOOMLEAGUE_SITE_SUB_IN'); ?>">&#9650; <?php echo (int) $ms->sub_in_minute; ?>'</span>
								<?php endif; ?>
								<?php if ($ms->sub_out > 0) : ?>
									<span class="text-danger" title="<?php echo Text::_('COM_JOOMLEAGUE_SITE_SUB_OUT'); ?>">&#9660; <?php echo (int) $ms->sub_out_minute; ?>'</span>
								<?php endif; ?>
								<span class="jl-site-muted">(<?php echo (int) $ms->minutes; ?>')</span>
								<?php foreach ($ms->events as $ev) : ?>
									<span class="badge bg-secondary" title="<?php echo $this->escape($translateValue($ev->name)); ?>">
										<?php echo $this->escape($translateValue($ev->name)); ?>
										<?php if ((float) $ev->total > 1.0) : ?>×<?php echo rtrim(rtrim(number_format((float) $ev->total, 1), '0'), '.'); ?><?php endif; ?>
									</span>
								<?php endforeach; ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
};

$renderPlstatsSection = function () use ($show, $playerParams, $translateValue): void {
	if (!$show($playerParams, 'show_plstats', true) || $this->playerCareerStats === []) {
		return;
	}

	$eventColumns = [];
	foreach ($this->playerCareerStats as $stat) {
		foreach ($stat->events as $eventTypeId => $ev) {
			if (!isset($eventColumns[$eventTypeId])) {
				$eventColumns[$eventTypeId] = $ev->name;
			}
		}
	}
	$careerTotal = (object) [
		'played' => 0, 'started' => 0, 'sub_in' => 0, 'sub_out' => 0, 'minutes' => 0,
		'events' => [],
	];
	foreach ($this->playerCareerStats as $stat) {
		$careerTotal->played += $stat->played;
		$careerTotal->started += $stat->started;
		$careerTotal->sub_in += $stat->sub_in;
		$careerTotal->sub_out += $stat->sub_out;
		$careerTotal->minutes += $stat->minutes;
		foreach ($stat->events as $eventTypeId => $ev) {
			$careerTotal->events[$eventTypeId] = (object) [
				'name' => $ev->name,
				'total' => ($careerTotal->events[$eventTypeId]->total ?? 0.0) + $ev->total,
			];
		}
	}
	?>
	<div class="jl-site-panel table-responsive mb-4">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYER_STATS'); ?></h2>
		<table class="table jl-site-table align-middle">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_SITE_STARTED'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SUB_IN'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SUB_OUT'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MINUTES'); ?></th>
					<?php foreach ($eventColumns as $eventName) : ?>
						<th class="text-end"><?php echo $this->escape($translateValue($eventName)); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->playerHistory as $item) : ?>
					<?php $stat = $this->playerCareerStats[(int) $item->projectteam_id] ?? null; ?>
					<?php if (!$stat) : continue; endif; ?>
					<tr>
						<td>
							<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $item->project_id); ?>">
								<?php echo $this->escape((string) $item->project_name); ?>
							</a>
							<div class="jl-site-muted small">
								<?php echo $this->escape(trim((string) ($item->league_name ?? '') . ' · ' . (string) ($item->season_name ?? ''), ' ·')); ?>
							</div>
						</td>
						<td>
							<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $item->projectteam_id); ?>">
								<?php echo $this->escape((string) ($item->team_name ?? '')); ?>
							</a>
						</td>
						<td class="text-end"><?php echo (int) $stat->played; ?></td>
						<td class="text-end"><?php echo (int) $stat->started; ?></td>
						<td class="text-end"><?php echo (int) $stat->sub_in; ?></td>
						<td class="text-end"><?php echo (int) $stat->sub_out; ?></td>
						<td class="text-end"><?php echo (int) $stat->minutes; ?></td>
						<?php foreach ($eventColumns as $eventTypeId => $eventName) : ?>
							<td class="text-end"><?php echo isset($stat->events[$eventTypeId]) ? rtrim(rtrim(number_format((float) $stat->events[$eventTypeId]->total, 1), '0'), '.') : ''; ?></td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr class="fw-bold">
					<td colspan="2"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CAREER_TOTAL'); ?></td>
					<td class="text-end"><?php echo (int) $careerTotal->played; ?></td>
					<td class="text-end"><?php echo (int) $careerTotal->started; ?></td>
					<td class="text-end"><?php echo (int) $careerTotal->sub_in; ?></td>
					<td class="text-end"><?php echo (int) $careerTotal->sub_out; ?></td>
					<td class="text-end"><?php echo (int) $careerTotal->minutes; ?></td>
					<?php foreach ($eventColumns as $eventTypeId => $eventName) : ?>
						<td class="text-end"><?php echo isset($careerTotal->events[$eventTypeId]) ? rtrim(rtrim(number_format((float) $careerTotal->events[$eventTypeId]->total, 1), '0'), '.') : ''; ?></td>
					<?php endforeach; ?>
				</tr>
			</tfoot>
		</table>
	</div>
	<?php
};

$renderPlcareerSection = function () use ($show, $playerParams, $renderHistory): void {
	if (!$show($playerParams, 'show_plcareer', true)) {
		return;
	}
	$renderHistory('COM_JOOMLEAGUE_SITE_PLAYER_HISTORY', $this->playerHistory, true);
};

if ($person) {
	$personUrl = StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $person->id, false));
	$personImage = $schemaPictureUrl($person->picture ?? '');
	$memberOf = [];

	foreach (array_merge($this->playerHistory, $this->staffHistory, $this->refereeHistory) as $historyItem) {
		if (empty($historyItem->project_name)) {
			continue;
		}

		$projectUrl = !empty($historyItem->project_id)
			? StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $historyItem->project_id, false))
			: null;
		$memberOf[] = [
			'@type' => 'SportsOrganization',
			'@id' => $projectUrl ? $projectUrl . '#competition' : null,
			'name' => (string) $historyItem->project_name,
			'url' => $projectUrl,
		];
	}

	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'Person',
		'@id' => $personUrl ? $personUrl . '#person' : null,
		'identifier' => [
			'@type' => 'PropertyValue',
			'propertyID' => 'JoomLeague person ID',
			'value' => (string) (int) $person->id,
		],
		'name' => $fullName($person),
		'givenName' => $person->firstname ?? null,
		'familyName' => $person->lastname ?? null,
		'alternateName' => $person->nickname ?? null,
		'url' => $personUrl,
		'sameAs' => StructuredDataHelper::externalUrl($person->website ?? ''),
		'image' => $personImage,
			'mainEntityOfPage' => StructuredDataHelper::webPage($fullName($person), $personPublicProfileText !== '' ? $personPublicProfileText : null, $personUrl),
		'birthDate' => $person->birthday ?? null,
		'deathDate' => $person->deathday ?? null,
		'nationality' => $person->country ?? null,
		'height' => !empty($person->height) ? (int) $person->height . ' cm' : null,
		'weight' => !empty($person->weight) ? (int) $person->weight . ' kg' : null,
		'jobTitle' => $translateValue($person->default_position_name ?? ''),
		'memberOf' => $memberOf,
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$person) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON_NOT_FOUND'); ?></div>
		<?php return; ?>
	<?php endif; ?>

	<?php
	// Pořadí pěti hlavních sekcí řídí player.xml (show_order_plinfo/description/gameshistory/plstats/plcareer).
	// U osoby bez hráčské role tahle konfigurace neexistuje – použijí se výchozí čísla a "hráčské"
	// sekce (historie zápasů/statistiky/kariéra) stejně nemají data, takže na pořadí nezáleží.
	$orderedSections = [
		['order' => (int) $param($playerParams, 'show_order_plinfo', 1), 'render' => $renderInfoSection],
		['order' => (int) $param($playerParams, 'show_order_description', 4), 'render' => $renderDescriptionSection],
		['order' => (int) $param($playerParams, 'show_order_gameshistory', 5), 'render' => $renderGameshistorySection],
		['order' => (int) $param($playerParams, 'show_order_plstats', 6), 'render' => $renderPlstatsSection],
		['order' => (int) $param($playerParams, 'show_order_plcareer', 7), 'render' => $renderPlcareerSection],
	];
	usort($orderedSections, static fn (array $a, array $b): int => $a['order'] <=> $b['order']);
	foreach ($orderedSections as $section) {
		($section['render'])();
	}
	?>

	<?php if ($this->personStats !== []) : ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_ADDITIONAL_STATS'); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_STATISTIC'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_VALUE'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->personStats as $stat) : ?>
						<tr>
							<td>
								<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $stat->project_id); ?>"><?php echo $this->escape($stat->project_name); ?></a>
								<div class="jl-site-muted small"><?php echo $this->escape(trim((string) ($stat->league_name ?? '') . ' · ' . (string) ($stat->season_name ?? ''), ' ·')); ?></div>
							</td>
							<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $stat->projectteam_id); ?>"><?php echo $this->escape($stat->team_name); ?></a></td>
							<td><?php echo $this->escape($stat->statistic_name); ?></td>
							<td><?php echo (int) $stat->matches; ?></td>
							<td><strong><?php echo $this->escape((string) $stat->value); ?></strong></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if ($show($staffParams, 'show_career', true)) : ?>
		<?php $renderHistory('COM_JOOMLEAGUE_SITE_STAFF_HISTORY', $this->staffHistory); ?>
	<?php endif; ?>
	<?php if ($show($refereeParams, 'show_career', true)) : ?>
		<?php $renderHistory('COM_JOOMLEAGUE_SITE_REFEREE_HISTORY', $this->refereeHistory); ?>
	<?php endif; ?>
</div>
