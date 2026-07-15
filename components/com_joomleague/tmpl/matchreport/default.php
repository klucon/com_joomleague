<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$match = $this->item;
$params = $this->templateParams;
$show = static fn (string $name, bool $default = true): bool => array_key_exists($name, $params) ? (bool) $params[$name] : $default;

$favTeamIds = array_map(
	'intval',
	array_filter(
		array_map('trim', explode(',', (string) ($this->project->fav_team ?? ''))),
		static fn (string $v): bool => $v !== '' && ctype_digit($v)
	)
);
?>
<div class="com-joomleague-site">
	<?php if (!$match) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCH_NOT_FOUND'); ?></div>
		<?php return; ?>
	<?php endif; ?>
	<?php if ($match && $show('show_navigation')) : ?>
		<nav class="jl-site-nav mb-4">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $match->projectteam1_id); ?>"><?php echo $this->escape($match->home_name ?? Text::_('COM_JOOMLEAGUE_SITE_HOME')); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $match->projectteam2_id); ?>"><?php echo $this->escape($match->away_name ?? Text::_('COM_JOOMLEAGUE_SITE_AWAY')); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $match->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=results&project_id=' . (int) $match->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS'); ?></a>
		</nav>
	<?php endif; ?>

	<?php
	echo LayoutHelper::render(
		'joomleague.match.detail',
		[
			'match' => $match,
			'events' => $this->items,
			'referees' => $this->matchReferees,
			'roster' => $this->matchRoster,
			'staff' => $this->matchStaffList,
			'statistics' => $this->matchPlayerStatistics,
			'favTeamIds' => $favTeamIds,
			'options' => [
				'link' => false,
				'heading' => 'h1',
				'meta' => $show('show_meta'),
				'details' => $show('show_details'),
				'split' => $show('show_split_results') && $show('show_period_result'),
				'preview' => $show('show_preview'),
				'summary' => $show('show_summary'),
				'referees' => $show('show_referees'),
				'matchReferees' => $show('show_match_referees'),
				'refereePosition' => $show('show_referee_position', false),
				'events' => $show('show_events'),
				'sectionHeader' => $show('show_sectionheader'),
				'result' => $show('show_result'),
				'extended' => $show('show_extended'),
				'timeline' => $show('show_timeline', false),
				'overtimeResult' => $show('show_overtime_result'),
				'shotoutResult' => $show('show_shotout_result'),
				'matchDate' => $show('show_match_date'),
				'matchTime' => $show('show_match_time'),
				'timeSuffix' => $show('show_time_suffix'),
				'matchNumber' => $show('show_match_number', false),
				'matchPlayground' => $show('show_match_playground'),
				'matchCrowd' => $show('show_match_crowd'),
				'eventMinute' => $show('show_event_minute'),
				'eventSum' => $show('show_event_sum', false),
				'eventNotice' => $show('show_event_notice'),
				'eventLinkPlayer' => $show('event_link_player'),
				'eventTeamName' => $show('show_event_team_name'),
				'sortEventsDesc' => $show('sort_events_desc'),
				'teamNameField' => (string) ($params['names'] ?? 'name'),
				'showTeamLogo' => $show('show_team_logo'),
				'picture' => (string) ($params['show_picture'] ?? 'logo_big'),
				'pictureWidth' => (int) ($params['team_picture_width'] ?? 150),
				'pictureHeight' => (int) ($params['team_picture_height'] ?? 0),
				'roster' => $show('show_roster'),
				'substitutions' => $show('show_substitutions'),
				'stats' => $show('show_stats'),
				'playerNameFormat' => (string) ($params['name_format'] ?? '3'),
				'playerProfileLink' => (string) ($params['show_player_profile_link'] ?? '1'),
				'playerPicture' => $show('show_player_picture'),
				'playerPictureWidth' => (int) ($params['player_picture_width'] ?? 0),
				'playerPictureHeight' => (int) ($params['player_picture_height'] ?? 40),
				'styleClass1' => (string) ($params['style_class1'] ?? ''),
				'styleClass2' => (string) ($params['style_class2'] ?? ''),
			],
		],
		JPATH_SITE . '/components/com_joomleague/layouts'
	);
	?>

	<?php if ($match && $show('show_head_to_head') && $this->headToHeadMatches) : ?>
		<div class="jl-site-panel table-responsive mt-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_HEAD_TO_HEAD'); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></th>
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
							<td><?php echo $this->escape($previousMatch->project_name ?? ''); ?></td>
							<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $previousMatch->home_projectteam_id); ?>"><?php echo $this->escape($previousMatch->home_name ?? ''); ?></a></td>
							<td><span class="jl-site-score"><?php echo $this->escape((string) $previousMatch->team1_result . ' : ' . (string) $previousMatch->team2_result); ?></span></td>
							<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $previousMatch->away_projectteam_id); ?>"><?php echo $this->escape($previousMatch->away_name ?? ''); ?></a></td>
							<td><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $previousMatch->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
