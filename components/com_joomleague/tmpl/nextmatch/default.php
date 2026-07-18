<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$match = $this->item;
$comparison = $this->matchTeamComparison;

$params = $this->templateParams;
$show = static fn (string $name, bool $default = true): bool => array_key_exists($name, $params) && $params[$name] !== '' ? (bool) $params[$name] : $default;
$param = static fn (string $name, $default = null) => array_key_exists($name, $params) && $params[$name] !== '' ? $params[$name] : $default;

$nameFormat = (string) $param('name_format', '0');

StructuredDataHelper::add($this->getDocument(), [
	'@context' => 'https://schema.org',
] + StructuredDataHelper::webPage(
	Text::_('COM_JOOMLEAGUE_SITE_NEXT_MATCH'),
	$match ? trim((string) ($match->home_name ?? '') . ' - ' . (string) ($match->away_name ?? '')) : null,
	null
));
?>
<div class="com-joomleague-site">
	<?php if (!$match) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NEXT_MATCH_NOT_FOUND'); ?></div>
		<?php return; ?>
	<?php endif; ?>

	<?php if ($show('show_sectionheader')) : ?>
		<h1 class="jl-site-title mb-3"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NEXT_MATCH'); ?></h1>
	<?php endif; ?>

	<nav class="jl-site-nav mb-4">
		<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $match->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></a>
		<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $match->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
		<a href="<?php echo Route::_('index.php?option=com_joomleague&view=ranking&project_id=' . (int) $match->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></a>
	</nav>

	<?php
	echo LayoutHelper::render(
		'joomleague.match.detail',
		[
			'match'    => $match,
			'events'   => $this->items,
			'referees' => $this->matchReferees,
			'options'  => [
				'events' => false,
				'heading' => 'h2',
				'sectionHeader' => $show('show_nextmatch'),
				'details' => $show('show_details'),
				'matchDate' => $show('show_match_date'),
				'matchTime' => $show('show_match_time'),
				'timeSuffix' => $show('show_time_suffix'),
				'matchNumber' => $show('show_match_number', false),
				'matchPlayground' => $show('show_match_playground'),
				'matchCrowd' => false,
				'referees' => $show('show_match_referees'),
				'matchReferees' => $show('show_match_referees'),
				'refereePosition' => true,
				'refereeNameFormat' => $nameFormat,
				'showTeamLogo' => $show('show_logo'),
				'picture' => (string) $param('show_picture', 'logo_big'),
				'pictureWidth' => (int) $param('team_picture_width', 150),
				'pictureHeight' => (int) $param('team_picture_height', 0),
				'preview' => false,
				'roster' => false,
				'stats' => false,
			],
		],
		JPATH_SITE . '/components/com_joomleague/layouts'
	);
	?>

	<?php if ($show('show_preview') && !empty($match->preview)) : ?>
		<div class="jl-site-panel mt-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREVIEW'); ?></h2>
			<div class="jl-site-richtext"><?php echo HTMLHelper::_('content.prepare', $match->preview); ?></div>
		</div>
	<?php endif; ?>

	<?php if ($show('show_previousx') && ($this->homeForm !== [] || $this->awayForm !== [])) : ?>
		<div class="jl-site-panel mt-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_RECENT_FORM'); ?></h2>
			<div class="jl-nm-form-grid">
				<?php foreach ([[$match->home_name ?? Text::_('COM_JOOMLEAGUE_SITE_HOME'), $this->homeForm], [$match->away_name ?? Text::_('COM_JOOMLEAGUE_SITE_AWAY'), $this->awayForm]] as $block) : ?>
					<div class="jl-nm-form-col">
						<h3><?php echo $this->escape((string) $block[0]); ?></h3>
						<?php if ($block[1] === []) : ?>
							<p class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></p>
						<?php else : ?>
							<ul class="jl-nm-form-list">
								<?php foreach ($block[1] as $game) : ?>
									<li>
										<span class="jl-nm-badge jl-nm-<?php echo $this->escape($game->form_result); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_FORM_' . strtoupper($game->form_result)); ?></span>
										<a class="jl-nm-opp" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $game->id); ?>"><?php echo $this->escape($game->form_opponent); ?></a>
										<span class="jl-site-score"><?php echo $this->escape($game->form_score); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($show('show_stats') && $comparison !== []) : ?>
		<?php
		$homeSide = $comparison['home'];
		$awaySide = $comparison['away'];
		$formatRecord = static fn (?array $r): string => $r !== null ? $r['won'] . ' / ' . $r['drawn'] . ' / ' . $r['lost'] : Text::_('COM_JOOMLEAGUE_SITE_NOT_SET');
		$formatRecordMatch = function (?object $m) use ($match): string {
			if ($m === null) {
				return '----';
			}
			$label = $this->escape((string) $m->home_name) . ' ' . (int) $m->team1_result . ':' . (int) $m->team2_result . ' ' . $this->escape((string) $m->away_name);

			return '<a href="' . Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $m->id) . '">' . $label . '</a>';
		};
		?>
		<div class="jl-site-panel table-responsive mt-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_COMPARISON'); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th><?php echo $this->escape($match->home_name ?? Text::_('COM_JOOMLEAGUE_SITE_HOME')); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_STATISTIC'); ?></th>
						<th><?php echo $this->escape($match->away_name ?? Text::_('COM_JOOMLEAGUE_SITE_AWAY')); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($show('show_chances') && $homeSide['chance'] !== null) : ?>
						<tr>
							<td><?php echo $this->escape((string) $homeSide['chance']); ?> %</td>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_CHANCES'); ?></th>
							<td><?php echo $this->escape((string) $awaySide['chance']); ?> %</td>
						</tr>
					<?php endif; ?>
					<?php if ($show('show_current_rank')) : ?>
						<tr>
							<td><?php echo $homeSide['rank'] ?? Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></td>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_RANKING'); ?></th>
							<td><?php echo $awaySide['rank'] ?? Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ($show('show_match_count')) : ?>
						<tr>
							<td><?php echo (int) ($homeSide['stats']['played'] ?? 0); ?></td>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED_MATCHES'); ?></th>
							<td><?php echo (int) ($awaySide['stats']['played'] ?? 0); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ($show('show_match_total')) : ?>
						<tr>
							<td><?php echo (int) ($homeSide['stats']['wins'] ?? 0); ?> / <?php echo (int) ($homeSide['stats']['draws'] ?? 0); ?> / <?php echo (int) ($homeSide['stats']['losses'] ?? 0); ?></td>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_WIN_DRAW_LOSS'); ?></th>
							<td><?php echo (int) ($awaySide['stats']['wins'] ?? 0); ?> / <?php echo (int) ($awaySide['stats']['draws'] ?? 0); ?> / <?php echo (int) ($awaySide['stats']['losses'] ?? 0); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ($show('show_match_total_home')) : ?>
						<tr>
							<td><?php echo $this->escape($formatRecord($homeSide['home_split'])); ?></td>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME_RECORD'); ?></th>
							<td><?php echo $this->escape($formatRecord($awaySide['home_split'])); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ($show('show_match_total_away')) : ?>
						<tr>
							<td><?php echo $this->escape($formatRecord($homeSide['away_split'])); ?></td>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_AWAY_RECORD'); ?></th>
							<td><?php echo $this->escape($formatRecord($awaySide['away_split'])); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ($show('show_match_points')) : ?>
						<tr>
							<td><?php echo $homeSide['points'] ?? Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></td>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POINTS'); ?></th>
							<td><?php echo $awaySide['points'] ?? Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ($show('show_match_goals')) : ?>
						<tr>
							<td><?php echo $this->escape((string) ($homeSide['stats']['goals_for'] ?? 0) . ':' . (string) ($homeSide['stats']['goals_against'] ?? 0)); ?></td>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOALS'); ?></th>
							<td><?php echo $this->escape((string) ($awaySide['stats']['goals_for'] ?? 0) . ':' . (string) ($awaySide['stats']['goals_against'] ?? 0)); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ($show('show_match_diff')) : ?>
						<tr>
							<td><?php echo $this->escape((string) ($homeSide['stats']['goal_difference'] ?? 0)); ?></td>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOAL_DIFFERENCE'); ?></th>
							<td><?php echo $this->escape((string) ($awaySide['stats']['goal_difference'] ?? 0)); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ($show('show_match_highest_stats')) : ?>
						<?php if ($show('show_match_highest_won')) : ?>
							<tr>
								<td><?php echo $formatRecordMatch($homeSide['records']['highest_home_win'] ?? null); ?></td>
								<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HIGHEST_WIN_HOME'); ?></th>
								<td><?php echo $formatRecordMatch($awaySide['records']['highest_home_win'] ?? null); ?></td>
							</tr>
						<?php endif; ?>
						<?php if ($show('show_match_highest_loss')) : ?>
							<tr>
								<td><?php echo $formatRecordMatch($homeSide['records']['highest_home_loss'] ?? null); ?></td>
								<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HIGHEST_LOSS_HOME'); ?></th>
								<td><?php echo $formatRecordMatch($awaySide['records']['highest_home_loss'] ?? null); ?></td>
							</tr>
						<?php endif; ?>
						<?php if ($show('show_match_highest_won_away')) : ?>
							<tr>
								<td><?php echo $formatRecordMatch($homeSide['records']['highest_away_win'] ?? null); ?></td>
								<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HIGHEST_WIN_AWAY'); ?></th>
								<td><?php echo $formatRecordMatch($awaySide['records']['highest_away_win'] ?? null); ?></td>
							</tr>
						<?php endif; ?>
						<?php if ($show('show_match_highest_loss_away')) : ?>
							<tr>
								<td><?php echo $formatRecordMatch($homeSide['records']['highest_away_loss'] ?? null); ?></td>
								<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HIGHEST_LOSS_AWAY'); ?></th>
								<td><?php echo $formatRecordMatch($awaySide['records']['highest_away_loss'] ?? null); ?></td>
							</tr>
						<?php endif; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if ($show('show_history') && $this->headToHeadMatches) : ?>
		<div class="jl-site-panel table-responsive mt-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_HEAD_TO_HEAD'); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCORE'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_AWAY'); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->headToHeadMatches as $previousMatch) : ?>
						<tr>
							<td><?php echo $this->escape($previousMatch->match_date ? date('d.m.Y H:i', strtotime((string) $previousMatch->match_date)) : ''); ?></td>
							<td><?php echo $this->escape($previousMatch->home_name ?? ''); ?></td>
							<td><span class="jl-site-score"><?php echo $this->escape((string) $previousMatch->team1_result . ' : ' . (string) $previousMatch->team2_result); ?></span></td>
							<td><?php echo $this->escape($previousMatch->away_name ?? ''); ?></td>
							<td><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $previousMatch->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
