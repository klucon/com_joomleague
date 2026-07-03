<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$person = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';

$fullName = static function (object $person): string {
	return trim((string) ($person->firstname ?? '') . ' ' . (string) ($person->lastname ?? ''));
};

$renderHistory = function (string $title, array $items, bool $showNumber = false): void {
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
							<td><?php echo $this->escape((string) ($item->position_name ?? '')); ?></td>
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
		'jobTitle' => $person->default_position_name ?? null,
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$person) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON_NOT_FOUND'); ?></div>
		<?php return; ?>
	<?php endif; ?>

	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></div>
		<h1 class="jl-site-title"><?php echo $this->escape($fullName($person)); ?></h1>
		<?php if (!empty($person->nickname)) : ?>
			<p class="jl-site-muted mb-0"><?php echo $this->escape((string) $person->nickname); ?></p>
		<?php endif; ?>
	</section>

	<div class="jl-site-grid mb-4">
		<div class="jl-site-panel">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON_PROFILE'); ?></h2>
			<dl class="row mb-0">
				<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_POSITION'); ?></dt>
				<dd class="col-sm-8"><?php echo $this->escape((string) ($person->default_position_name ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'))); ?></dd>
				<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_SITE_BIRTHDAY'); ?></dt>
				<dd class="col-sm-8"><?php echo $this->escape((string) ($person->birthday ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'))); ?></dd>
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
			<p class="mb-1"><?php echo $this->escape(trim((string) ($person->location ?? '') . ' ' . (string) ($person->state ?? ''))); ?></p>
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

	<?php $renderHistory('COM_JOOMLEAGUE_SITE_STAFF_HISTORY', $this->staffHistory); ?>
	<?php $renderHistory('COM_JOOMLEAGUE_SITE_REFEREE_HISTORY', $this->refereeHistory); ?>
</div>
