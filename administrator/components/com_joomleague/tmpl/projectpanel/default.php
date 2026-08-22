<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$projectId = (int) $this->project->id;
$panelUrl = 'index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId;
$return = base64_encode($panelUrl);
$activeItems = [
	[
		'icon' => 'check-circle',
		'title' => 'COM_JOOMLEAGUE_PREFLIGHT_PANEL_TITLE',
		'description' => 'COM_JOOMLEAGUE_PREFLIGHT_PANEL_DESC',
		'url' => 'index.php?option=com_joomleague&view=projectpreflight&project_id=' . $projectId,
		'badge' => Text::_('COM_JOOMLEAGUE_PREFLIGHT_PANEL_BADGE'),
	],
	[
		'icon' => 'tree',
		'title' => 'COM_JOOMLEAGUE_STAGES_TITLE',
		'description' => 'COM_JOOMLEAGUE_PROJECTPANEL_STAGES_DESC',
		'url' => 'index.php?option=com_joomleague&view=stages&project_id=' . $projectId,
		'badge' => Text::sprintf(
			'COM_JOOMLEAGUE_PROJECTPANEL_SCHEDULE_BADGE',
			$this->aggregateCounts['stages'],
			$this->aggregateCounts['rounds'],
			$this->aggregateCounts['matches']
		),
	],
	[
		'icon' => 'calendar',
		'title' => 'COM_JOOMLEAGUE_PROJECTSCHEDULE_HEADING',
		'description' => 'COM_JOOMLEAGUE_PROJECTPANEL_SCHEDULE_OVERVIEW_DESC',
		'url' => 'index.php?option=com_joomleague&view=projectschedule&project_id=' . $projectId,
		'badge' => Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_MATCHES_BADGE', $this->aggregateCounts['matches']),
	],
	[
		'icon' => 'users',
		'title' => 'COM_JOOMLEAGUE_PROJECTENTRIES_HEADING',
		'description' => 'COM_JOOMLEAGUE_PROJECTPANEL_ENTRIES_DESC',
		'url' => 'index.php?option=com_joomleague&view=projectentries&project_id=' . $projectId,
		'badge' => Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_ENTRIES_BADGE', $this->aggregateCounts['entries']),
	],
	[
		'icon' => 'user-secret',
		'title' => 'COM_JOOMLEAGUE_PROJECTOFFICIALS_HEADING',
		'description' => 'COM_JOOMLEAGUE_PROJECTPANEL_OFFICIALS_DESC',
		'url' => 'index.php?option=com_joomleague&view=projectofficials&project_id=' . $projectId,
		'badge' => Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_OFFICIALS_BADGE', $this->aggregateCounts['officials']),
	],
	[
		'icon' => 'edit',
		'title' => 'COM_JOOMLEAGUE_PROJECTPANEL_SETTINGS',
		'description' => 'COM_JOOMLEAGUE_PROJECTPANEL_SETTINGS_DESC',
		'url' => 'index.php?option=com_joomleague&task=project.edit&id=' . $projectId . '&return=' . rawurlencode($return),
		'badge' => null,
	],
	[
		'icon' => 'sliders-h',
		'title' => 'COM_JOOMLEAGUE_PROJECTPANEL_RULES',
		'description' => 'COM_JOOMLEAGUE_PROJECTPANEL_RULES_DESC',
		'url' => 'index.php?option=com_joomleague&view=projectrules&project_id=' . $projectId,
		'badge' => Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_OVERRIDES_BADGE', $this->overrideCounts['rules']),
	],
	[
		'icon' => 'palette',
		'title' => 'COM_JOOMLEAGUE_PROJECTPANEL_TEMPLATES',
		'description' => 'COM_JOOMLEAGUE_PROJECTPANEL_TEMPLATES_DESC',
		'url' => 'index.php?option=com_joomleague&view=projecttemplates&project_id=' . $projectId,
		'badge' => Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_OVERRIDES_BADGE', $this->overrideCounts['templates']),
	],
	[
		'icon' => 'ranking-star',
		'title' => 'COM_JOOMLEAGUE_STANDINGS_TITLE',
		'description' => 'COM_JOOMLEAGUE_PROJECTPANEL_STANDINGS_DESC',
		'url' => 'index.php?option=com_joomleague&view=standings&project_id=' . $projectId,
		'badge' => null,
	],
];
?>
<div class="container-fluid">
	<div class="row g-4">
		<div class="col-xl-8">
			<div class="alert alert-info" role="status">
				<h2 class="h4 mb-2"><?php echo $this->escape($this->project->name); ?></h2>
				<div><?php echo $this->escape($this->project->competition_name); ?> &middot; <?php echo $this->escape($this->project->season_name); ?></div>
				<div class="small"><?php echo $this->escape($this->project->sport_type_name); ?> &middot; <?php echo Text::_($this->project->profile_name_key); ?> <?php echo $this->escape($this->project->profile_version); ?></div>
			</div>

			<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_AVAILABLE_TITLE'); ?></h2>
			<div class="row g-3 mb-4">
			<?php foreach ($activeItems as $item) : ?>
				<div class="col-md-4">
					<div class="card h-100">
						<div class="card-body">
							<h3 class="h5"><span class="icon-<?php echo $this->escape($item['icon']); ?> me-2" aria-hidden="true"></span><?php echo Text::_($item['title']); ?></h3>
							<p><?php echo Text::_($item['description']); ?></p>
							<?php if ($item['badge'] !== null) : ?><span class="badge bg-info text-dark"><?php echo $this->escape($item['badge']); ?></span><?php endif; ?>
						</div>
						<?php if ($this->canEdit) : ?><div class="card-footer"><a class="btn btn-primary" href="<?php echo Route::_($item['url']); ?>"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_OPEN'); ?></a></div><?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
			</div>

		</div>

		<div class="col-xl-4">
			<div class="card">
				<div class="card-header"><h2 class="h5 mb-0"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_CONTEXT_TITLE'); ?></h2></div>
				<dl class="card-body row mb-0">
					<dt class="col-5"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROJECT_TYPE_LABEL'); ?></dt><dd class="col-7"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_TYPE_' . strtoupper($this->project->project_type)); ?></dd>
					<dt class="col-5"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_LIFECYCLE_STATE_LABEL'); ?></dt><dd class="col-7"><?php echo Text::_('COM_JOOMLEAGUE_LIFECYCLE_' . strtoupper($this->project->lifecycle_state)); ?></dd>
					<dt class="col-5"><?php echo Text::_('JSTATUS'); ?></dt><dd class="col-7"><?php echo Text::_((int) $this->project->published === 1 ? 'JPUBLISHED' : 'JUNPUBLISHED'); ?></dd>
					<dt class="col-5"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_TIMEZONE_LABEL'); ?></dt><dd class="col-7"><?php echo $this->project->timezone === '' ? Text::_('COM_JOOMLEAGUE_PROJECT_OPTION_USE_DEFAULT_TIMEZONE') : $this->escape($this->project->timezone); ?></dd>
					<dt class="col-5"><?php echo Text::_('JGLOBAL_FIELD_ID_LABEL'); ?></dt><dd class="col-7"><?php echo $projectId; ?></dd>
				</dl>
			</div>
		</div>
	</div>
</div>
