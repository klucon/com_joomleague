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
use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$person = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';

$fullName = static function (object $person): string {
	return trim((string) ($person->firstname ?? '') . ' ' . (string) ($person->lastname ?? ''));
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
		return null;
	}
	if (preg_match('#^https?://#i', $picture)) {
		return $picture;
	}
	return Uri::root(true) . '/' . ltrim($picture, '/');
};

$translateValue = static function (?string $value): string {
	$value = trim((string) $value);

	return $value === '' ? '' : Text::_($value);
};

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

if ($person) {
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'Person',
		'@id' => StructuredDataHelper::currentUrl() . '#person',
		'name' => $fullName($person),
		'givenName' => $person->firstname ?? null,
		'familyName' => $person->lastname ?? null,
		'url' => StructuredDataHelper::absoluteUrl($person->website ?? null),
		'birthDate' => $person->birthday ?? null,
		'height' => !empty($person->height) ? (int) $person->height . ' cm' : null,
		'weight' => !empty($person->weight) ? (int) $person->weight . ' kg' : null,
		'jobTitle' => $translateValue($person->default_position_name ?? ''),
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$person) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON_NOT_FOUND'); ?></div>
		<?php return; ?>
	<?php endif; ?>

	<section class="jl-site-hero mb-4">
		<div class="d-flex align-items-center gap-3 flex-wrap">
			<?php if ($photo = $pictureUrl($person->picture ?? null)) : ?>
				<img class="jl-person-photo rounded" src="<?php echo $this->escape($photo); ?>" alt="<?php echo $this->escape($fullName($person)); ?>" loading="lazy" style="max-height:120px;width:auto;">
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
				<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_BIRTHDAY'); ?></dt>
				<dd class="col-sm-8">
					<?php if (!empty($person->birthday) && strpos((string) $person->birthday, '0000-00-00') !== 0) : ?>
						<?php echo $this->escape((string) $person->birthday); ?>
						<?php if (($years = $age($person->birthday, $person->deathday ?? null)) !== null) : ?>
							<span class="jl-site-muted">(<?php echo $years . ' ' . Text::_('COM_JOOMLEAGUE_SITE_YEARS'); ?>)</span>
						<?php endif; ?>
					<?php else : ?>
						<?php echo Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?>
					<?php endif; ?>
				</dd>
				<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_HEIGHT'); ?></dt>
				<dd class="col-sm-8"><?php echo $person->height ? (int) $person->height . ' cm' : Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></dd>
				<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_WEIGHT'); ?></dt>
				<dd class="col-sm-8"><?php echo $person->weight ? (int) $person->weight . ' kg' : Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?></dd>
				<?php if (!empty($person->country)) : ?>
					<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_COUNTRY'); ?></dt>
					<dd class="col-sm-8"><?php echo LayoutHelper::render('joomleague.flag', ['code' => $person->country], $jlFlagPath); ?></dd>
				<?php endif; ?>
			</dl>
		</div>
		<div class="jl-site-panel">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_CONTACT'); ?></h2>
			<?php $addr = trim((string) ($person->location ?? '') . ' ' . (string) ($person->state ?? '')); ?>
			<?php if ($addr !== '') : ?>
				<p class="mb-1"><?php echo $this->escape($addr); ?></p>
			<?php endif; ?>
			<?php if (!empty($person->email)) : ?>
				<p class="mb-1"><?php echo Text::_('COM_JOOMLEAGUE_SITE_EMAIL'); ?>: <a href="mailto:<?php echo $this->escape((string) $person->email); ?>"><?php echo $this->escape((string) $person->email); ?></a></p>
			<?php endif; ?>
			<?php if (!empty($person->phone)) : ?>
				<p class="mb-1"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PHONE'); ?>: <?php echo $this->escape((string) $person->phone); ?></p>
			<?php endif; ?>
			<?php if (!empty($person->mobile)) : ?>
				<p class="mb-1"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MOBILE'); ?>: <?php echo $this->escape((string) $person->mobile); ?></p>
			<?php endif; ?>
			<?php if (!empty($person->website)) : ?>
				<p class="mb-1"><a href="<?php echo $this->escape((string) $person->website); ?>" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_SITE_WEBSITE'); ?></a></p>
			<?php endif; ?>
			<?php if (!empty($person->info)) : ?>
				<p class="jl-site-muted mb-0"><?php echo $this->escape((string) $person->info); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php $renderHistory('COM_JOOMLEAGUE_SITE_PLAYER_HISTORY', $this->playerHistory, true); ?>

	<?php if ($this->personStats !== []) : ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYER_STATS'); ?></h2>
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

	<?php if (!empty($this->playerMatches)) : ?>
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
								<?php if ((int) $g->came_in > 0) : ?><span class="text-success" title="<?php echo Text::_('COM_JOOMLEAGUE_SITE_SUB_IN'); ?>">&#9650; <?php echo (int) $g->came_in; ?>'</span> <?php endif; ?>
								<?php if ((int) $g->out > 0) : ?><span class="text-danger" title="<?php echo Text::_('COM_JOOMLEAGUE_SITE_SUB_OUT'); ?>">&#9660; <?php echo (int) $g->out; ?>'</span> <?php endif; ?>
								<?php if ((int) $g->in_out_time > 0) : ?><span class="jl-site-muted"><?php echo (int) $g->in_out_time; ?>'</span><?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php $renderHistory('COM_JOOMLEAGUE_SITE_STAFF_HISTORY', $this->staffHistory); ?>
	<?php $renderHistory('COM_JOOMLEAGUE_SITE_REFEREE_HISTORY', $this->refereeHistory); ?>
</div>
