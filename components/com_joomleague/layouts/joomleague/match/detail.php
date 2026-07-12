<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


/**
 * Sdílený layout detailu zápasu – používá ho frontend view (matchreport) i
 * content plugin ({jlmatch id=X}). Přepisovatelný přes:
 *   templates/<sablona>/html/layouts/joomleague/match/detail.php
 *
 * @var array $displayData  ['match' => object, 'events' => array, 'options' => array]
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$match   = $displayData['match'] ?? null;
$events  = $displayData['events'] ?? [];
$referees = $displayData['referees'] ?? [];
$options = $displayData['options'] ?? [];

$showSummary = $options['summary'] ?? true;
$showEvents  = $options['events'] ?? true;
$showMeta    = $options['meta'] ?? true;
$showSplit   = $options['split'] ?? true;
$showPreview = $options['preview'] ?? true;
$showReferees = $options['referees'] ?? true;
$showLink    = $options['link'] ?? false;
$headingTag  = \in_array($options['heading'] ?? 'h2', ['h1', 'h2', 'h3'], true) ? $options['heading'] : 'h2';
$escape      = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$translateLegacyName = static function ($value): string {
	$value = trim((string) $value);

	return preg_match('/^(COM|JLM)_[A-Z0-9_-]+$/', $value) ? Text::_($value) : $value;
};

if (!$match) {
	echo '<div class="alert alert-warning">' . Text::_('COM_JOOMLEAGUE_SITE_MATCH_NOT_FOUND') . '</div>';

	return;
}

$sportName = $translateLegacyName($match->sport_name ?? '');

// Strukturovaná data pro vyhledávače (jednou za zápas na stránce).
if (class_exists(StructuredDataHelper::class)) {
	StructuredDataHelper::add(Factory::getApplication()->getDocument(), [
		'@context'    => 'https://schema.org',
		'@type'       => 'SportsEvent',
		'name'        => trim((string) ($match->home_name ?? '') . ' - ' . (string) ($match->away_name ?? '')),
		'startDate'   => !empty($match->match_date) ? date('c', strtotime((string) $match->match_date)) : null,
		'eventStatus' => !empty($match->cancel) ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
		'sport'       => $sportName !== '' ? $sportName : null,
		'location'    => !empty($match->playground_name) ? ['@type' => 'SportsActivityLocation', 'name' => (string) $match->playground_name] : null,
		'homeTeam'    => !empty($match->home_name) ? ['@type' => 'SportsOrganization', 'name' => (string) $match->home_name] : null,
		'awayTeam'    => !empty($match->away_name) ? ['@type' => 'SportsOrganization', 'name' => (string) $match->away_name] : null,
		'organizer'   => !empty($match->project_name) ? ['@type' => 'SportsOrganization', 'name' => (string) $match->project_name] : null,
		'url'         => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id, false)),
	]);
}

$splitHome = ($match->team1_result_split ?? '') !== '' ? explode(';', (string) $match->team1_result_split) : [];
$splitAway = ($match->team2_result_split ?? '') !== '' ? explode(';', (string) $match->team2_result_split) : [];
$splitParts = [];

for ($i = 0, $n = max(\count($splitHome), \count($splitAway)); $i < $n; $i++) {
	$splitParts[] = ($splitHome[$i] ?? '-') . ':' . ($splitAway[$i] ?? '-');
}

?>
<div class="com-joomleague-site jl-match-detail">
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $escape(trim(($match->project_name ?? '') . ' · ' . ($match->round_name ?? ''), ' ·')); ?></div>
		<<?php echo $headingTag; ?> class="jl-site-title"><?php echo $escape(($match->home_name ?? '') . ' – ' . ($match->away_name ?? '')); ?></<?php echo $headingTag; ?>>
		<p class="mb-0">
			<span class="jl-site-score"><?php echo $match->team1_result === null || $match->team2_result === null
				? Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED')
				: $escape((string) (float) $match->team1_result . ' : ' . (string) (float) $match->team2_result); ?></span>
		</p>
		<?php if ($showSplit && $splitParts !== []) : ?>
			<p class="jl-site-muted mb-0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SPLIT'); ?>: <?php echo $escape(implode(' · ', $splitParts)); ?></p>
		<?php endif; ?>
	</section>

	<?php if ($showMeta) : ?><div class="jl-site-grid mb-4">
		<div class="jl-site-card"><strong><?php echo $escape($match->match_date ? date('d.m.Y H:i', strtotime((string) $match->match_date)) : Text::_('COM_JOOMLEAGUE_SITE_DATE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $escape($match->playground_name ?? Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) ($match->crowd ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ATTENDANCE'); ?></span></div>
	</div><?php endif; ?>

	<?php if ($showPreview && trim((string) ($match->preview ?? '')) !== '') : ?>
		<div class="jl-site-panel mb-4"><h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREVIEW'); ?></h3><?php echo HTMLHelper::_('content.prepare', (string) $match->preview); ?></div>
	<?php endif; ?>

	<?php if ($showSummary && trim((string) ($match->summary ?? '')) !== '') : ?>
		<div class="jl-site-panel mb-4"><h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_SUMMARY'); ?></h3><?php echo $match->summary; ?></div>
	<?php endif; ?>

	<?php if ($showReferees && $referees !== []) : ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_REFEREES'); ?></h3>
			<table class="table jl-site-table">
				<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POSITION'); ?></th></tr></thead>
				<tbody>
					<?php foreach ($referees as $referee) : ?>
						<tr>
							<td>
								<?php if (!empty($referee->person_id)) : ?>
									<a href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $referee->person_id)); ?>"><?php echo $escape($referee->person_name ?: $referee->nickname); ?></a>
								<?php else : ?>
									<?php echo $escape($referee->person_name ?: $referee->nickname); ?>
								<?php endif; ?>
							</td>
							<td><?php echo $escape($referee->position_name ?? ''); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if ($showEvents) : ?>
		<div class="jl-site-panel table-responsive mb-3">
			<h3><?php echo Text::_('COM_JOOMLEAGUE_SITE_EVENTS'); ?></h3>
			<?php if ($events) : ?>
				<table class="table jl-site-table">
					<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_MINUTE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_EVENT'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th></tr></thead>
					<tbody>
						<?php foreach ($events as $event) : ?>
							<tr><td><?php echo $escape($event->event_time); ?></td><td><?php echo $escape($translateLegacyName($event->event_name ?? '')); ?></td><td><?php echo $escape($event->person_name ?? ''); ?></td><td><?php echo $escape($event->team_name ?? ''); ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_EVENTS'); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ($showLink) : ?>
		<p><a class="jl-site-more" href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id)); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCH_DETAIL'); ?> →</a></p>
	<?php endif; ?>
</div>
