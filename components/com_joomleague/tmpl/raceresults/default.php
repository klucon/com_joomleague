<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$project = $this->project;
$input = \Joomla\CMS\Factory::getApplication()->getInput();
$roundId = (int) $input->getInt('round_id');
$categoryId = (int) $input->getInt('category_id');
$sex = strtoupper((string) $input->getCmd('sex'));
$status = strtoupper((string) $input->getCmd('status'));
$translateLegacyName = static function ($value): string {
	$value = trim((string) $value);

	return preg_match('/^(COM|JLM)_[A-Z0-9_-]+$/', $value) ? Text::_($value) : $value;
};
$sportName = $project ? $translateLegacyName($project->sport_name ?? '') : '';

if ($project) {
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'SportsEvent',
		'@id' => StructuredDataHelper::currentUrl() . '#race-results',
		'name' => (string) $project->name,
		'sport' => $sportName !== '' ? $sportName : null,
		'competitor' => array_map(
			static fn (object $result): array => [
				'@type' => 'Person',
				'name' => (string) ($result->runner_name ?: $result->bib_number),
			],
			array_slice($this->raceResults, 0, 50)
		),
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$project) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT_NOT_FOUND'); ?></div>
		<?php return; ?>
	<?php endif; ?>

	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape(trim(($project->league_name ?? '') . ' · ' . ($project->season_name ?? ''), ' ·')); ?></div>
		<h1 class="jl-site-title"><?php echo $this->escape($project->name); ?></h1>
		<p class="jl-site-muted mb-3"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_RESULTS'); ?></p>
	</section>

	<form action="<?php echo Route::_('index.php'); ?>" method="get" class="jl-site-panel mb-4">
		<input type="hidden" name="option" value="com_joomleague">
		<input type="hidden" name="view" value="raceresults">
		<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
		<div class="row g-3 align-items-end">
			<div class="col-md-3">
				<label class="form-label" for="jl-race-round"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROUND'); ?></label>
				<select class="form-select" id="jl-race-round" name="round_id">
					<option value="0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ALL_ROUNDS'); ?></option>
					<?php foreach ($this->rounds as $round) : ?>
						<option value="<?php echo (int) $round->id; ?>"<?php echo $roundId === (int) $round->id ? ' selected' : ''; ?>><?php echo $this->escape($round->name); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-3">
				<label class="form-label" for="jl-race-category"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_CATEGORY'); ?></label>
				<select class="form-select" id="jl-race-category" name="category_id">
					<option value="0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ALL_CATEGORIES'); ?></option>
					<?php foreach ($this->raceCategories as $category) : ?>
						<option value="<?php echo (int) $category->id; ?>"<?php echo $categoryId === (int) $category->id ? ' selected' : ''; ?>><?php echo $this->escape($category->name); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-2">
				<label class="form-label" for="jl-race-sex"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_SEX'); ?></label>
				<select class="form-select" id="jl-race-sex" name="sex">
					<option value=""><?php echo Text::_('COM_JOOMLEAGUE_SITE_ALL'); ?></option>
					<option value="M"<?php echo $sex === 'M' ? ' selected' : ''; ?>><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_SEX_MALE'); ?></option>
					<option value="F"<?php echo $sex === 'F' ? ' selected' : ''; ?>><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_SEX_FEMALE'); ?></option>
					<option value="X"<?php echo $sex === 'X' ? ' selected' : ''; ?>><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_SEX_OTHER'); ?></option>
				</select>
			</div>
			<div class="col-md-2">
				<label class="form-label" for="jl-race-status"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_STATUS'); ?></label>
				<select class="form-select" id="jl-race-status" name="status">
					<option value=""><?php echo Text::_('COM_JOOMLEAGUE_SITE_ALL'); ?></option>
					<?php foreach (['FINISHED', 'DNS', 'DNF', 'DSQ', 'NC'] as $raceStatus) : ?>
						<option value="<?php echo $raceStatus; ?>"<?php echo $status === $raceStatus ? ' selected' : ''; ?>><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_STATUS_' . $raceStatus); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-2">
				<button type="submit" class="btn btn-primary w-100"><?php echo Text::_('COM_JOOMLEAGUE_SITE_APPLY'); ?></button>
			</div>
		</div>
	</form>

	<div class="jl-site-panel">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_RESULTS'); ?></h2>
		<?php if ($this->raceResults === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div>
		<?php else : ?>
			<div class="table-responsive">
				<table class="table table-striped align-middle">
					<thead>
						<tr>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_PLACE'); ?></th>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_BIB'); ?></th>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_SEX'); ?></th>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_CATEGORY'); ?></th>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB'); ?></th>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_TIME'); ?></th>
							<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_RACE_STATUS'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($this->raceResults as $result) : ?>
							<tr>
								<td><?php echo (int) $result->overall_place > 0 ? (int) $result->overall_place : '–'; ?></td>
								<td><?php echo $this->escape($result->bib_number); ?></td>
								<td><?php echo $this->escape($result->runner_name ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></td>
								<td><?php echo $this->escape(Text::_('COM_JOOMLEAGUE_SITE_RACE_SEX_' . (($result->sex ?: 'OTHER') === 'M' ? 'MALE' : (($result->sex ?: 'OTHER') === 'F' ? 'FEMALE' : 'OTHER')))); ?></td>
								<td><?php echo $this->escape($result->category_name ?: ''); ?></td>
								<td><?php echo $this->escape($result->club_name ?: $result->team_name ?: ''); ?></td>
								<td><?php echo $this->escape($result->duration_text ?: '–'); ?></td>
								<td><?php echo $this->escape(Text::_('COM_JOOMLEAGUE_SITE_RACE_STATUS_' . $result->status)); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
