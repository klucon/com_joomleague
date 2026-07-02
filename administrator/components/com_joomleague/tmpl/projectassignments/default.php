<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$labels = [
	'positions' => 'COM_JOOMLEAGUE_PROJECT_POSITIONS',
	'teams' => 'COM_JOOMLEAGUE_PROJECT_TEAMS',
	'referees' => 'COM_JOOMLEAGUE_PROJECT_REFEREES',
];

$available = array_values(array_filter($this->options, static fn ($o): bool => !(bool) $o->selected));
$assigned = array_values(array_filter($this->options, static fn ($o): bool => (bool) $o->selected));

// Pozn.: WebAssetManager (useScript/registerAndUseScript) v tomto view assety neemituje;
// document API (addScript/addStyleSheet) ano – zapisuje přímo do <head>.
$doc = $this->getDocument();
$doc->addStyleSheet(Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.5');
$doc->addScript(Uri::root(true) . '/media/com_joomleague/js/duallist.js?v=1.0.6', [], ['defer' => true]);

$projectId = $this->project ? (int) $this->project->id : 0;
$sectionTitle = Text::_($labels[$this->section]);

?>
<?php if ($projectId < 1) : ?>
	<div class="alert alert-warning">
		<span class="icon-warning" aria-hidden="true"></span>
		<?php echo Text::_('COM_JOOMLEAGUE_PROJECT_CONTEXT_MISSING'); ?>
	</div>
	<p>
		<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=projects'); ?>">
			<span class="icon-arrow-left" aria-hidden="true"></span>
			<?php echo Text::_('COM_JOOMLEAGUE_PROJECT_BACK'); ?>
		</a>
	</p>
	<?php return; ?>
<?php endif; ?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&task=projectsetup.save'); ?>" method="post" id="adminForm">
	<div class="com-joomleague-dashboard com-joomleague-workflow">
		<div class="jl-section-panel mb-4">
			<span class="jl-section-panel__icon icon-address" aria-hidden="true"></span>
			<div class="jl-section-panel__content">
				<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_CONTEXT'); ?></p>
				<h2 class="h4 mb-1"><?php echo Text::sprintf('COM_JOOMLEAGUE_PROJECT_ASSIGN_TITLE', $sectionTitle, $this->escape($this->project->name)); ?></h2>
				<p class="mb-2"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_ASSIGN_HELP'); ?></p>
				<a class="jl-section-back" href="<?php echo Route::_('index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId); ?>">
					<span class="icon-arrow-left" aria-hidden="true"></span>
					<?php echo Text::_('COM_JOOMLEAGUE_BACK_TO_PROJECT_PANEL'); ?>
				</a>
			</div>
			<div class="jl-section-panel__stats">
				<span><strong><?php echo number_format(count($available), 0, ',', ' '); ?></strong><?php echo Text::_('COM_JOOMLEAGUE_AVAILABLE'); ?></span>
				<span><strong><?php echo number_format(count($assigned), 0, ',', ' '); ?></strong><?php echo Text::_('COM_JOOMLEAGUE_ASSIGNED'); ?></span>
			</div>
		</div>

		<div class="card mb-4">
			<div class="card-body">
				<div class="jl-assignment-layout" data-jl-duallist>
					<div class="jl-assignment-box">
						<label>
							<span><?php echo Text::_('COM_JOOMLEAGUE_AVAILABLE'); ?></span>
							<span class="jl-assignment-count"><?php echo Text::sprintf('COM_JOOMLEAGUE_ITEMS_COUNT', count($available)); ?></span>
						</label>
						<select class="form-select" multiple size="14" data-available>
							<?php foreach ($available as $option) : ?>
								<option value="<?php echo (int) $option->id; ?>"><?php echo $this->escape($option->name); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="jl-assignment-actions">
						<button type="button" class="btn btn-secondary" data-add>
							<span class="icon-chevron-right" aria-hidden="true"></span>
							<span><?php echo Text::_('COM_JOOMLEAGUE_ASSIGN_SELECTED'); ?></span>
						</button>
						<button type="button" class="btn btn-secondary" data-remove>
							<span class="icon-chevron-left" aria-hidden="true"></span>
							<span><?php echo Text::_('COM_JOOMLEAGUE_UNASSIGN_SELECTED'); ?></span>
						</button>
					</div>

					<div class="jl-assignment-box">
						<label>
							<span><?php echo Text::_('COM_JOOMLEAGUE_ASSIGNED'); ?></span>
							<span class="jl-assignment-count"><?php echo Text::sprintf('COM_JOOMLEAGUE_ITEMS_COUNT', count($assigned)); ?></span>
						</label>
						<select class="form-select" name="assigned[]" multiple size="14" data-assigned>
							<?php foreach ($assigned as $option) : ?>
								<option value="<?php echo (int) $option->id; ?>"><?php echo $this->escape($option->name); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<button class="btn btn-primary mt-3" type="submit">
					<span class="icon-save" aria-hidden="true"></span>
					<?php echo Text::_('JSAVE'); ?>
				</button>
			</div>
		</div>
	</div>

	<?php if ($this->section === 'positions') : ?>
		<table class="table">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NAME'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_TRANSLATION'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PARENT'); ?></th>
					<?php foreach (['PLAYER', 'STAFF', 'REFEREE', 'CLUBSTAFF'] as $type) : ?>
						<th><?php echo Text::_('COM_JOOMLEAGUE_PERSONTYPE_' . $type); ?></th>
					<?php endforeach; ?>
					<th><?php echo Text::_('COM_JOOMLEAGUE_MY_EVENTS'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_MY_STATISTICS'); ?></th>
					<th>PID</th>
					<th>ID</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($assigned as $option) : ?>
					<tr>
						<td><?php echo $this->escape($option->name); ?></td>
						<td><?php echo Text::_($option->name) !== $option->name ? $this->escape(Text::_($option->name)) : ''; ?></td>
						<td><?php echo $this->escape($option->parent_name ?? ''); ?></td>
						<?php for ($type = 1; $type <= 4; $type++) : ?>
							<td class="text-center"><?php echo (int) $option->persontype === $type ? '<span class="icon-check text-success"></span>' : ''; ?></td>
						<?php endfor; ?>
						<td><?php echo (int) $option->event_count; ?></td>
						<td><?php echo (int) $option->statistic_count; ?></td>
						<td><?php echo (int) $option->id; ?></td>
						<td><?php echo (int) $option->assignment_id; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php if ($this->section !== 'positions') : ?>
		<table class="table">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_ORDERING'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NAME'); ?></th>
					<?php if ($this->section === 'teams') : ?>
						<th><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAYERS_TITLE'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_TEAMSTAFFS_TITLE'); ?></th>
					<?php endif; ?>
					<th>PID</th>
					<th>ID</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($assigned as $index => $option) : $task = $this->section === 'teams' ? 'projectteam.edit' : 'projectreferee.edit'; ?>
					<tr>
						<td>
							<?php if ($this->section === 'teams') : ?>
								<input class="form-control form-control-sm" type="number" min="1" step="1" name="ordering[<?php echo (int) $option->assignment_id; ?>]" value="<?php echo (int) ($option->ordering ?: ($index + 1)); ?>" style="width: 6rem;">
							<?php else : ?>
								<?php echo $index + 1; ?>
							<?php endif; ?>
						</td>
						<td><a href="<?php echo Route::_('index.php?option=com_joomleague&task=' . $task . '&id=' . (int) $option->assignment_id); ?>"><?php echo $this->escape($option->name); ?></a></td>
						<?php if ($this->section === 'teams') : ?>
							<td>
								<a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=teamplayers&projectteam_id=' . (int) $option->assignment_id); ?>">
									<span class="icon-users" aria-hidden="true"></span>
									<?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAYERS_MANAGE'); ?>
								</a>
							</td>
							<td>
								<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=teamstaffs&projectteam_id=' . (int) $option->assignment_id); ?>">
									<span class="icon-address" aria-hidden="true"></span>
									<?php echo Text::_('COM_JOOMLEAGUE_TEAMSTAFFS_MANAGE'); ?>
								</a>
							</td>
						<?php endif; ?>
						<td><?php echo (int) $option->id; ?></td>
						<td><?php echo (int) $option->assignment_id; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
	<input type="hidden" name="section" value="<?php echo $this->escape($this->section); ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
