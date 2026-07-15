<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Site\Service\RankingColumnsHelper;

/** @var Joomleague\Component\Joomleague\Site\View\Ranking\HtmlView $this */

$params = $this->templateParams;
$show = static fn (string $name, bool $default = true): bool => array_key_exists($name, $params) ? (bool) $params[$name] : $default;
$projectId = $this->project ? (int) $this->project->id : 0;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';
$input = Factory::getApplication()->getInput();

$scopes = [
	'total' => 'COM_JOOMLEAGUE_SITE_RANKING_SCOPE_TOTAL',
	'home' => 'COM_JOOMLEAGUE_SITE_RANKING_SCOPE_HOME',
	'away' => 'COM_JOOMLEAGUE_SITE_RANKING_SCOPE_AWAY',
];
$useTabbedView = (string) ($params['use_tabbed_view'] ?? '0') !== '0';

// Zóny (postup/sestup): "od,do,barva,popisek;..." – viz COM_JOOMLEAGUE_FES_RANKING_PARAM_LABEL_TABLE_COLORS.
$zones = [];
foreach (explode(';', (string) ($params['colors'] ?? '')) as $zoneDef) {
	$zoneDef = trim($zoneDef);

	if ($zoneDef === '') {
		continue;
	}

	$parts = array_map('trim', explode(',', $zoneDef, 4));

	if (count($parts) < 3 || !ctype_digit($parts[0]) || !ctype_digit($parts[1]) || !preg_match('/^#[0-9a-f]{3,6}$/i', $parts[2])) {
		continue;
	}

	$zones[] = [
		'from' => (int) $parts[0],
		'to' => (int) $parts[1],
		'color' => $parts[2],
		'label' => $parts[3] ?? '',
	];
}

$zoneColorForRank = static function (int $rank) use ($zones): ?string {
	foreach ($zones as $zone) {
		if ($rank >= $zone['from'] && $rank <= $zone['to']) {
			return $zone['color'];
		}
	}

	return null;
};

$showLegend = $zones !== [] && (string) ($params['show_colorlegend'] ?? '2') !== '0';
$colorWholeRow = (string) ($params['use_background_row_color'] ?? '1') !== '0';

// Oblíbený tým (nastavení projektu) – zvýraznění řádku/jména v tabulce.
$favTeamIds = array_map(
	'intval',
	array_filter(
		array_map('trim', explode(',', (string) ($this->project->fav_team ?? ''))),
		static fn (string $v): bool => $v !== '' && ctype_digit($v)
	)
);
$favHighlightWholeRow = (string) ($params['fav_highlight_type'] ?? '1') !== '0';
$favBackgroundColor = trim((string) ($this->project->fav_team_color ?? '')) ?: null;
$favTextColor = trim((string) ($this->project->fav_team_text_color ?? '')) ?: null;
$favTextBold = (string) ($this->project->fav_team_text_bold ?? '0') === '1';

// Obrázek/logo u týmu.
$showPicture = (string) ($params['show_picture'] ?? 'logo_small');
$pictureWidth = (int) ($params['picture_width'] ?? 0);
$pictureHeight = (int) ($params['picture_height'] ?? 21) ?: 21;
$resolvePicturePath = static function (object $row) use ($showPicture): string {
	return trim((string) match ($showPicture) {
		'logo_small' => $row->club_logo_small,
		'logo_middle' => $row->club_logo_middle,
		'logo_big' => $row->club_logo_big,
		'projectteam_picture' => $row->projectteam_picture,
		'team_picture' => $row->team_picture,
		default => '',
	});
};
$resolveImageUrl = static function (string $path): string {
	if ($path === '') {
		return '';
	}

	return preg_match('#^https?://#i', $path) ? $path : Uri::root(true) . '/' . ltrim($path, '/');
};

// Formát zobrazovaného názvu týmu.
$teamNameFormat = (string) ($params['team_name_format'] ?? '2');
$formatTeamName = static function (object $row) use ($teamNameFormat): string {
	return match ($teamNameFormat) {
		'0' => $row->team_short_name !== '' ? $row->team_short_name : $row->team_name,
		'1' => $row->team_middle_name !== '' ? $row->team_middle_name : $row->team_name,
		default => $row->team_name,
	};
};

// Odkazy u jména týmu – každý je volitelný přes vlastní show_*_link parametr.
// show_curve_link (křivka vývoje pořadí) není zahrnut: porovnává vždy 2 týmy, takže
// z jednoho řádku tabulky nelze rozumně předvyplnit obě strany porovnání.
$teamLinkSpecs = [];

if ($show('show_club_link')) {
	$teamLinkSpecs[] = ['view' => 'club', 'param' => 'id', 'field' => 'club_id', 'icon' => 'icon-home', 'label' => 'COM_JOOMLEAGUE_SITE_CLUB'];
}

if ($show('show_team_link')) {
	$teamLinkSpecs[] = ['view' => 'roster', 'param' => 'id', 'field' => 'projectteam_id', 'icon' => 'icon-users', 'label' => 'COM_JOOMLEAGUE_SITE_ROSTER'];
}

if ($show('show_teaminfo_link')) {
	$teamLinkSpecs[] = ['view' => 'team', 'param' => 'id', 'field' => 'projectteam_id', 'icon' => 'icon-info-circle', 'label' => 'COM_JOOMLEAGUE_SITE_TEAM'];
}

if ($show('show_teamstats_link')) {
	$teamLinkSpecs[] = ['view' => 'teamstats', 'param' => 'id', 'field' => 'projectteam_id', 'icon' => 'icon-list', 'label' => 'COM_JOOMLEAGUE_SITE_TEAM_STATS'];
}

if ($show('show_plan_link')) {
	$teamLinkSpecs[] = ['view' => 'schedule', 'param' => 'projectteam_id', 'field' => 'projectteam_id', 'icon' => 'icon-calendar', 'label' => 'COM_JOOMLEAGUE_SITE_SCHEDULE'];
}

if ($show('show_clubplan_link')) {
	$teamLinkSpecs[] = ['view' => 'schedule', 'param' => 'club_id', 'field' => 'club_id', 'icon' => 'icon-calendar-alt', 'label' => 'COM_JOOMLEAGUE_SITE_CLUB'];
}

if ($show('show_rivals_link')) {
	$teamLinkSpecs[] = ['view' => 'rivals', 'param' => 'id', 'field' => 'projectteam_id', 'icon' => 'icon-users', 'label' => 'COM_JOOMLEAGUE_SITE_RIVALS'];
}

$infoLinkMode = (string) ($params['show_info_link'] ?? '1');
$isTeamNameLinked = static function (object $row) use ($infoLinkMode, $favTeamIds): bool {
	return match ($infoLinkMode) {
		'0' => false,
		'2' => in_array((int) $row->projectteam_id, $favTeamIds, true),
		default => true,
	};
};

// Sloupce tabulky: buď vlastní sada kódů (ordered_columns), nebo výchozích 7 přepínačů.
$orderedColumnsRaw = trim((string) ($params['ordered_columns'] ?? ''));
$altLegTerm = trim((string) ($params['alternative_legs'] ?? ''));

if ($orderedColumnsRaw !== '') {
	$columns = RankingColumnsHelper::resolveColumns($orderedColumnsRaw, (string) ($params['ordered_columns_names'] ?? ''), $altLegTerm);
} else {
	$columns = [];

	if ($show('show_played')) {
		$columns[] = ['code' => 'PLAYED', 'header' => Text::_('COM_JOOMLEAGUE_SITE_PLAYED_SHORT')];
	}

	if ($show('show_won')) {
		$columns[] = ['code' => 'WINS', 'header' => Text::_('COM_JOOMLEAGUE_SITE_WON_SHORT')];
	}

	if ($show('show_drawn')) {
		$columns[] = ['code' => 'TIES', 'header' => Text::_('COM_JOOMLEAGUE_SITE_DRAWN_SHORT')];
	}

	if ($show('show_lost')) {
		$columns[] = ['code' => 'LOSSES', 'header' => Text::_('COM_JOOMLEAGUE_SITE_LOST_SHORT')];
	}

	if ($show('show_goals')) {
		$columns[] = ['code' => 'RESULTS', 'header' => Text::_('COM_JOOMLEAGUE_SITE_GOALS')];
	}

	if ($show('show_goal_difference')) {
		$columns[] = ['code' => 'DIFF', 'header' => Text::_('COM_JOOMLEAGUE_SITE_GOAL_DIFFERENCE_SHORT')];
	}

	if ($show('show_points')) {
		$columns[] = ['code' => 'POINTS', 'header' => Text::_('COM_JOOMLEAGUE_SITE_POINTS')];
	}
}

$rankingWinPoints = 3;

if ($this->project && trim((string) ($this->project->points_after_regular_time ?? '')) !== '') {
	$pointsParts = array_map('trim', explode(',', (string) $this->project->points_after_regular_time));

	if (count($pointsParts) === 3 && is_numeric($pointsParts[0])) {
		$rankingWinPoints = (int) $pointsParts[0];
	}
}

$leader = $this->standings[0] ?? null;
$columnContext = ['winPoints' => $rankingWinPoints, 'leader' => $leader];

// Klikací řazení podle sloupce (column_sorting) – řadí jen zobrazení, neovlivňuje sloupec #.
$columnSortingEnabled = $show('column_sorting');
$activeSort = $columnSortingEnabled ? strtoupper((string) $input->getCmd('sort', '')) : '';
$sortDir = strtolower((string) $input->getCmd('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

$displayRows = $this->standings;

if ($activeSort !== '' && in_array($activeSort, RankingColumnsHelper::SORTABLE_CODES, true)) {
	usort($displayRows, static function (object $a, object $b) use ($activeSort, $sortDir): int {
		$valueA = RankingColumnsHelper::sortableValue($a, $activeSort) ?? 0.0;
		$valueB = RankingColumnsHelper::sortableValue($b, $activeSort) ?? 0.0;

		return $sortDir === 'asc' ? ($valueA <=> $valueB) : ($valueB <=> $valueA);
	});
}

$sortLink = static function (string $code, string $label) use ($activeSort, $sortDir, $projectId): string {
	if (!in_array($code, RankingColumnsHelper::SORTABLE_CODES, true)) {
		return $label;
	}

	$nextDir = ($activeSort === $code && $sortDir === 'desc') ? 'asc' : 'desc';
	$url = Route::_('index.php?option=com_joomleague&view=ranking&project_id=' . $projectId . '&sort=' . $code . '&dir=' . $nextDir);
	$arrow = $activeSort === $code ? ($sortDir === 'asc' ? ' ▲' : ' ▼') : '';

	return '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '" class="jl-site-sort-link">' . htmlspecialchars($label, ENT_QUOTES) . $arrow . '</a>';
};

$styleClass1 = trim((string) ($params['style_class1'] ?? ''));
$styleClass2 = trim((string) ($params['style_class2'] ?? ''));
$rowExtraClass = static function (int $index) use ($styleClass1, $styleClass2): string {
	$class = $index % 2 === 0 ? $styleClass1 : $styleClass2;

	return $class !== '' ? ' ' . $class : '';
};

// Oddělovací čára na hranici barevné zóny (např. mezi pohárovou pozicí a zbytkem tabulky).
$separationStyle = trim((string) ($params['show_separation_lines'] ?? '0'));
$separationEnabled = $separationStyle !== '' && $separationStyle !== '0';
$separationColor = trim((string) ($params['separation_lines_color'] ?? '')) ?: '#000000';
$isZoneBoundary = static function (int $rank) use ($zoneColorForRank, $separationEnabled): bool {
	if (!$separationEnabled) {
		return false;
	}

	$current = $zoneColorForRank($rank);
	$next = $zoneColorForRank($rank + 1);

	return $current !== null && $current !== $next;
};

$showPreviousRank = $show('last_ranking');
$showSectionHeader = $show('show_sectionheader');
$showHelp = $show('show_help', false);
$showExplanation = $show('show_explanation');
?>
<div class="com-joomleague-site">
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->project ? $this->escape($this->project->name) : ''; ?></div>
		<?php if ($showSectionHeader) : ?><h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></h1><?php endif; ?>
		<?php
		// Jednotný stavitel odkazů tabulky – zachovává rozsah/divizi/kolo, pokud override neřekne jinak.
		$rankingUrl = function (array $overrides = []) use ($projectId): string {
			$query = [
				'option' => 'com_joomleague',
				'view' => 'ranking',
				'project_id' => $projectId,
				'scope' => $overrides['scope'] ?? $this->rankingScope,
				'division_id' => $overrides['division_id'] ?? $this->rankingDivisionId,
				'round_id' => array_key_exists('round_id', $overrides) ? $overrides['round_id'] : $this->rankingRoundId,
			];

			$parts = [];

			foreach ($query as $key => $value) {
				if ($key === 'scope' && $value === 'total') {
					continue;
				}

				if (in_array($key, ['division_id', 'round_id'], true) && (int) $value === 0) {
					continue;
				}

				$parts[] = $key . '=' . rawurlencode((string) $value);
			}

			return Route::_('index.php?' . implode('&', $parts));
		};

		// standingsRounds je seřazený podle čísla kola (ne podle data odehrání), proto se
		// "aktuální" (nejnovější) kolo musí dohledat podle data zvlášť – používá se v navigaci
		// nahoře i v číslovaném pageru dole, aby oboje ukazovalo na stejné kolo.
		$currentRoundId = null;
		$currentRoundDate = null;

		foreach ($this->standingsRounds as $round) {
			if ($currentRoundDate === null || $round['date'] > $currentRoundDate) {
				$currentRoundDate = $round['date'];
				$currentRoundId = $round['id'];
			}
		}

		$roundIdOrCurrent = static fn (int $id): int => $id === $currentRoundId ? 0 : $id;
		$roundShortLabel = static function (array $round): string {
			return preg_match('/(\d+)/', $round['name'], $matches) === 1 ? $matches[1] : $round['name'];
		};
		?>
		<?php if (count($this->divisions) > 1) : ?>
			<nav class="jl-site-nav mb-2" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_SITE_DIVISIONS'); ?>">
				<?php foreach ($this->divisions as $division) : $divisionId = (int) $division->id; ?>
					<a
						class="jl-site-tab<?php echo $divisionId === $this->rankingDivisionId ? ' jl-site-tab--active' : ''; ?>"
						href="<?php echo $rankingUrl(['division_id' => $divisionId, 'round_id' => 0]); ?>"
						<?php echo $divisionId === $this->rankingDivisionId ? 'aria-current="page"' : ''; ?>
					><?php echo $this->escape($division->name); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
		<nav class="jl-site-nav<?php echo $useTabbedView ? ' jl-site-nav--tabs' : ''; ?>" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING_SCOPE'); ?>">
			<?php foreach ($scopes as $scopeValue => $scopeLabel) : ?>
				<a
					class="jl-site-tab<?php echo $scopeValue === $this->rankingScope ? ' jl-site-tab--active' : ''; ?>"
					href="<?php echo $rankingUrl(['scope' => $scopeValue]); ?>"
					<?php echo $scopeValue === $this->rankingScope ? 'aria-current="page"' : ''; ?>
				><?php echo Text::_($scopeLabel); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php if ($show('show_rankingnav') && count($this->standingsRounds) > 1) : ?>
			<?php
			$activeRoundId = $this->rankingRoundId > 0 ? $this->rankingRoundId : $currentRoundId;
			$roundIds = array_column($this->standingsRounds, 'id');
			$activeIndex = array_search($activeRoundId, $roundIds, true);
			$prevRound = $activeIndex !== false && $activeIndex > 0 ? $this->standingsRounds[$activeIndex - 1] : null;
			$nextRound = $activeIndex !== false && $activeIndex < count($roundIds) - 1 ? $this->standingsRounds[$activeIndex + 1] : null;
			$viewingHistorical = $this->rankingRoundId > 0 && $this->rankingRoundId !== $currentRoundId;
			?>
			<div class="jl-site-round-nav mt-2">
				<a class="jl-site-round-nav__arrow<?php echo $prevRound === null ? ' is-disabled' : ''; ?>" href="<?php echo $prevRound !== null ? $rankingUrl(['round_id' => $roundIdOrCurrent((int) $prevRound['id'])]) : '#'; ?>" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_SITE_PREVIOUS_ROUND'); ?>">‹</a>
				<label class="jl-site-round-nav__select">
					<span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_SITE_BY_ROUND'); ?></span>
					<select onchange="location.href=this.value">
						<?php foreach ($this->standingsRounds as $round) : ?>
							<option value="<?php echo $this->escape($rankingUrl(['round_id' => $roundIdOrCurrent((int) $round['id'])])); ?>" <?php echo (int) $round['id'] === $activeRoundId ? 'selected' : ''; ?>>
								<?php echo $this->escape($round['name']); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<a class="jl-site-round-nav__arrow<?php echo $nextRound === null ? ' is-disabled' : ''; ?>" href="<?php echo $nextRound !== null ? $rankingUrl(['round_id' => $roundIdOrCurrent((int) $nextRound['id'])]) : '#'; ?>" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_SITE_NEXT_ROUND'); ?>">›</a>
				<?php if ($viewingHistorical) : ?>
					<a class="jl-site-round-nav__current" href="<?php echo $rankingUrl(['round_id' => 0]); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CURRENT_STANDINGS'); ?></a>
				<?php endif; ?>
			</div>
			<?php if ($viewingHistorical && $activeIndex !== false) : ?>
				<p class="jl-site-muted mt-2 mb-0"><?php echo Text::sprintf('COM_JOOMLEAGUE_SITE_STANDINGS_AFTER_ROUND', $this->escape($this->standingsRounds[$activeIndex]['name'])); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ($showHelp) : ?><p class="jl-site-muted mt-2 mb-0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING_HELP'); ?></p><?php endif; ?>
	</section>
	<?php if ($show('show_ranking')) : ?>
	<div class="jl-site-panel table-responsive">
		<table class="table jl-site-table align-middle">
			<thead>
				<tr>
					<?php if ($show('show_rank')) : ?><th>#</th><?php endif; ?>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
					<?php foreach ($columns as $column) : ?>
						<th><?php echo $sortLink($column['code'], $column['header']); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($displayRows as $index => $row) : ?>
					<?php
					$rank = (int) $row->rank;
					$zoneColor = $zoneColorForRank($rank);
					$isFav = in_array((int) $row->projectteam_id, $favTeamIds, true);
					$rowStyle = '';

					if ($isFav && $favHighlightWholeRow && $favBackgroundColor !== null) {
						$rowStyle = 'background-color: ' . $favBackgroundColor . ';';
					} elseif ($colorWholeRow && $zoneColor !== null) {
						$rowStyle = 'background-color: ' . $zoneColor . ';';
					}

					// Barevný pruh vlevo – zůstává čitelný i u velmi světlých barev zóny,
					// kdy samotné podbarvení řádku na bílém pozadí téměř splývá. Nastavuje se
					// na první buňku řádku, ne na <tr>, protože border-collapse u tabulky
					// spolehlivě vykresluje jen ohraničení buněk, ne celého řádku.
					$leftAccentColor = $zoneColor ?? (($isFav && $favBackgroundColor !== null) ? $favBackgroundColor : null);

					if ($isZoneBoundary($rank)) {
						$rowStyle .= 'border-bottom: ' . $separationStyle . ' ' . $separationColor . ';';
					}

					$nameStyle = '';

					if ($isFav && $favTextColor !== null) {
						$nameStyle .= 'color: ' . $favTextColor . ';';
					}

					if ($isFav && $favTextBold) {
						$nameStyle .= 'font-weight: 700;';
					}

					$picturePath = $resolvePicturePath($row);
					$pictureUrl = $resolveImageUrl($picturePath);

					$firstCellStyle = $leftAccentColor !== null ? 'border-left: 5px solid ' . $leftAccentColor . ';' : '';

					if ($show('show_rank') && !$colorWholeRow && $zoneColor !== null && !($isFav && $favHighlightWholeRow)) {
						$firstCellStyle .= 'background-color: ' . $zoneColor . ';';
					}
					?>
					<tr<?php echo $rowStyle !== '' ? ' style="' . $this->escape($rowStyle) . '"' : ''; ?> class="<?php echo trim('jl-site-ranking-row' . $rowExtraClass($index)); ?>">
						<?php if ($show('show_rank')) : ?>
							<td<?php echo $firstCellStyle !== '' ? ' style="' . $this->escape($firstCellStyle) . '"' : ''; ?>>
								<?php echo $rank; ?>
								<?php if ($showPreviousRank && $row->previous_rank !== null && $row->previous_rank !== $rank) : ?>
									<span class="jl-site-rank-prev" title="<?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING_PREVIOUS_RANK'); ?>">
										(<?php echo $row->previous_rank > $rank ? '▲' : '▼'; ?> <?php echo (int) $row->previous_rank; ?>)
									</span>
								<?php endif; ?>
							</td>
						<?php endif; ?>
						<td<?php echo !$show('show_rank') && $firstCellStyle !== '' ? ' style="' . $this->escape($firstCellStyle) . '"' : ''; ?>>
							<span class="jl-site-team-cell">
								<?php if ($showPicture !== 'no_logo') : ?>
									<?php if (in_array($showPicture, ['country_small', 'country_big'], true)) : ?>
										<?php echo LayoutHelper::render('joomleague.flag', ['code' => $row->club_country, 'showName' => false, 'size' => $showPicture === 'country_big' ? 32 : 20], $jlFlagPath); ?>
									<?php elseif ($pictureUrl !== '') : ?>
										<img
											class="jl-site-team-logo"
											src="<?php echo $this->escape($pictureUrl); ?>"
											alt=""
											loading="lazy"
											<?php echo $pictureWidth > 0 ? 'width="' . $pictureWidth . '"' : ''; ?>
											height="<?php echo $pictureHeight; ?>"
										>
									<?php endif; ?>
								<?php endif; ?>
								<?php if ($isTeamNameLinked($row)) : ?>
									<a style="<?php echo $this->escape($nameStyle); ?>" href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $row->projectteam_id); ?>"><?php echo $this->escape($formatTeamName($row)); ?></a>
								<?php else : ?>
									<span style="<?php echo $this->escape($nameStyle); ?>"><?php echo $this->escape($formatTeamName($row)); ?></span>
								<?php endif; ?>
								<?php if ($teamLinkSpecs !== []) : ?>
									<span class="jl-site-team-links">
										<?php foreach ($teamLinkSpecs as $spec) : $linkId = (int) ($row->{$spec['field']} ?? 0); ?>
											<?php if ($linkId > 0) : ?>
												<a class="jl-site-team-link" title="<?php echo Text::_($spec['label']); ?>" href="<?php echo Route::_('index.php?option=com_joomleague&view=' . $spec['view'] . '&' . $spec['param'] . '=' . $linkId . '&project_id=' . $projectId); ?>"><span class="<?php echo $this->escape($spec['icon']); ?>" aria-hidden="true"></span></a>
											<?php endif; ?>
										<?php endforeach; ?>
									</span>
								<?php endif; ?>
							</span>
						</td>
						<?php foreach ($columns as $column) : ?>
							<?php if ($column['code'] === 'LASTGAMES') : ?>
								<td><?php echo LayoutHelper::render('joomleague.rankingform', ['projectId' => $projectId, 'projectTeamId' => (int) $row->projectteam_id, 'limit' => (int) ($params['nb_previous'] ?? 5), 'model' => $this->getModel()]); ?></td>
							<?php else : ?>
								<td><?php echo $this->escape(RankingColumnsHelper::value($row, $column['code'], $columnContext)); ?></td>
							<?php endif; ?>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!$this->standings) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
		<?php if ($showLegend) : ?>
			<ul class="jl-site-legend">
				<?php foreach ($zones as $zone) : ?>
					<?php if ($zone['label'] === '') : continue; endif; ?>
					<li><span class="jl-site-legend__swatch" style="background-color: <?php echo $this->escape($zone['color']); ?>"></span><?php echo $this->escape($zone['label']); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ($showExplanation) : ?><p class="jl-site-muted mt-3 mb-0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING_EXPLANATION'); ?></p><?php endif; ?>
		<?php if ($show('show_pagnav') && count($this->standingsRounds) > 1) : ?>
			<nav class="jl-site-round-pager mt-3" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_SITE_BY_ROUND'); ?>">
				<?php foreach ($this->standingsRounds as $round) : $roundId = (int) $round['id']; $isActive = $roundId === ($this->rankingRoundId > 0 ? $this->rankingRoundId : $currentRoundId); ?>
					<a
						class="jl-site-round-pager__item<?php echo $isActive ? ' is-active' : ''; ?>"
						href="<?php echo $rankingUrl(['round_id' => $roundIdOrCurrent($roundId)]); ?>"
						title="<?php echo $this->escape($round['name']); ?>"
						<?php echo $isActive ? 'aria-current="page"' : ''; ?>
					><?php echo $this->escape($roundShortLabel($round)); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>
