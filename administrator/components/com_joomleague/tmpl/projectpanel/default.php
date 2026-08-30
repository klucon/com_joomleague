<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$projectId = (int) $this->project->id;
$panelUrl = 'index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId;
$return = base64_encode($panelUrl);

$operationItems = [
	['check-circle', 'COM_JOOMLEAGUE_PREFLIGHT_PANEL_TITLE', 'COM_JOOMLEAGUE_PREFLIGHT_PANEL_DESC', 'index.php?option=com_joomleague&view=projectpreflight&project_id=' . $projectId, Text::_('COM_JOOMLEAGUE_PREFLIGHT_PANEL_BADGE')],
	['users', 'COM_JOOMLEAGUE_PROJECTENTRIES_HEADING', 'COM_JOOMLEAGUE_PROJECTPANEL_ENTRIES_DESC', 'index.php?option=com_joomleague&view=projectentries&project_id=' . $projectId, Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_ENTRIES_BADGE', $this->aggregateCounts['entries'])],
	['tree', 'COM_JOOMLEAGUE_STAGES_TITLE', 'COM_JOOMLEAGUE_PROJECTPANEL_STAGES_DESC', 'index.php?option=com_joomleague&view=stages&project_id=' . $projectId, Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_SCHEDULE_BADGE', $this->aggregateCounts['stages'], $this->aggregateCounts['rounds'], $this->aggregateCounts['matches'])],
	['calendar', 'COM_JOOMLEAGUE_PROJECTSCHEDULE_HEADING', 'COM_JOOMLEAGUE_PROJECTPANEL_SCHEDULE_OVERVIEW_DESC', 'index.php?option=com_joomleague&view=projectschedule&project_id=' . $projectId, Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_MATCHES_BADGE', $this->aggregateCounts['matches'])],
	['list', 'COM_JOOMLEAGUE_STANDINGS_TITLE', 'COM_JOOMLEAGUE_PROJECTPANEL_STANDINGS_DESC', 'index.php?option=com_joomleague&view=standings&project_id=' . $projectId, null],
];
$configurationItems = [
	['cog', 'COM_JOOMLEAGUE_PROJECTPANEL_SETTINGS', 'COM_JOOMLEAGUE_PROJECTPANEL_SETTINGS_DESC', 'index.php?option=com_joomleague&task=project.edit&id=' . $projectId . '&return=' . rawurlencode($return), null],
	['user', 'COM_JOOMLEAGUE_PROJECTOFFICIALS_HEADING', 'COM_JOOMLEAGUE_PROJECTPANEL_OFFICIALS_DESC', 'index.php?option=com_joomleague&view=projectofficials&project_id=' . $projectId, Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_OFFICIALS_BADGE', $this->aggregateCounts['officials'])],
	['options', 'COM_JOOMLEAGUE_PROJECTPANEL_RULES', 'COM_JOOMLEAGUE_PROJECTPANEL_RULES_DESC', 'index.php?option=com_joomleague&view=projectrules&project_id=' . $projectId, Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_OVERRIDES_BADGE', $this->overrideCounts['rules'])],
	['palette', 'COM_JOOMLEAGUE_PROJECTPANEL_TEMPLATES', 'COM_JOOMLEAGUE_PROJECTPANEL_TEMPLATES_DESC', 'index.php?option=com_joomleague&view=projecttemplates&project_id=' . $projectId, Text::sprintf('COM_JOOMLEAGUE_PROJECTPANEL_OVERRIDES_BADGE', $this->overrideCounts['templates'])],
];

$renderAction = function (array $item): void {
	$tag = $this->canEdit ? 'a' : 'div';
	$attributes = $this->canEdit ? ' href="' . Route::_($item[3]) . '"' : ' aria-disabled="true"';
	?>
	<<?php echo $tag; ?> class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3"<?php echo $attributes; ?>>
		<span class="icon-<?php echo $this->escape($item[0]); ?> fs-3 text-primary flex-shrink-0" aria-hidden="true"></span>
		<span class="flex-grow-1">
			<span class="d-flex flex-wrap align-items-center gap-2 mb-1">
				<strong><?php echo Text::_($item[1]); ?></strong>
				<?php if ($item[4] !== null) : ?><span class="badge bg-info text-dark"><?php echo $this->escape($item[4]); ?></span><?php endif; ?>
			</span>
			<span class="d-block text-body-secondary small"><?php echo Text::_($item[2]); ?></span>
		</span>
		<?php if ($this->canEdit) : ?>
			<span class="icon-chevron-right text-body-secondary flex-shrink-0" aria-hidden="true"></span>
			<span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_OPEN'); ?></span>
		<?php endif; ?>
	</<?php echo $tag; ?>>
	<?php
};
?>
<div class="container-fluid px-0">
	<section class="card mb-4" aria-labelledby="projectpanel-project-title">
		<div class="card-body p-4">
			<div class="row g-4 align-items-start">
				<div class="col-lg-7">
					<p class="text-uppercase text-body-secondary small fw-semibold mb-2"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_WORKSPACE'); ?></p>
					<h2 id="projectpanel-project-title" class="h3 mb-2"><?php echo $this->escape($this->project->name); ?></h2>
					<p class="mb-2"><?php echo $this->escape($this->project->competition_name); ?> &middot; <?php echo $this->escape($this->project->season_name); ?></p>
					<div class="d-flex flex-wrap gap-2">
						<span class="badge bg-primary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_TYPE_' . strtoupper($this->project->project_type)); ?></span>
						<span class="badge bg-light text-dark border"><?php echo $this->escape($this->project->sport_type_name); ?></span>
						<span class="badge bg-light text-dark border"><?php echo Text::_($this->project->profile_name_key); ?> <?php echo $this->escape($this->project->profile_version); ?></span>
						<span class="badge <?php echo (int) $this->project->published === 1 ? 'bg-success' : 'bg-secondary'; ?>"><?php echo Text::_((int) $this->project->published === 1 ? 'JPUBLISHED' : 'JUNPUBLISHED'); ?></span>
					</div>
				</div>
				<div class="col-lg-5">
					<div class="row g-3">
						<div class="col-6"><div class="border-start border-primary border-4 ps-3"><div class="h4 mb-0"><?php echo (int) $this->aggregateCounts['entries']; ?></div><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_METRIC_ENTRIES'); ?></div></div></div>
						<div class="col-6"><div class="border-start border-success border-4 ps-3"><div class="h4 mb-0"><?php echo (int) $this->aggregateCounts['stages']; ?></div><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_METRIC_STAGES'); ?></div></div></div>
						<div class="col-6"><div class="border-start border-warning border-4 ps-3"><div class="h4 mb-0"><?php echo (int) $this->aggregateCounts['rounds']; ?></div><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_METRIC_ROUNDS'); ?></div></div></div>
						<div class="col-6"><div class="border-start border-info border-4 ps-3"><div class="h4 mb-0"><?php echo (int) $this->aggregateCounts['matches']; ?></div><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_METRIC_ITEMS'); ?></div></div></div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<div class="row g-4 align-items-start">
		<div class="col-xl-8">
			<section class="card mb-4" aria-labelledby="projectpanel-operations-title">
				<div class="card-header">
					<div>
						<h2 id="projectpanel-operations-title" class="h5 mb-1"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_OPERATIONS_TITLE'); ?></h2>
						<p class="small text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_OPERATIONS_DESC'); ?></p>
					</div>
				</div>
				<div class="list-group list-group-flush"><?php foreach ($operationItems as $item) : $renderAction($item); endforeach; ?></div>
			</section>

			<section class="card" aria-labelledby="projectpanel-configuration-title">
				<div class="card-header">
					<div>
						<h2 id="projectpanel-configuration-title" class="h5 mb-1"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_CONFIGURATION_TITLE'); ?></h2>
						<p class="small text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_CONFIGURATION_DESC'); ?></p>
					</div>
				</div>
				<div class="list-group list-group-flush"><?php foreach ($configurationItems as $item) : $renderAction($item); endforeach; ?></div>
			</section>
		</div>

		<div class="col-xl-4">
			<section class="card" aria-labelledby="projectpanel-context-title">
				<div class="card-header"><h2 id="projectpanel-context-title" class="h5 mb-0"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_CONTEXT_TITLE'); ?></h2></div>
				<dl class="card-body row mb-0">
					<dt class="col-5"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROJECT_TYPE_LABEL'); ?></dt><dd class="col-7"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_TYPE_' . strtoupper($this->project->project_type)); ?></dd>
					<dt class="col-5"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTPANEL_PROFILE_LABEL'); ?></dt><dd class="col-7"><?php echo Text::_($this->project->profile_name_key); ?> <?php echo $this->escape($this->project->profile_version); ?></dd>
					<dt class="col-5"><?php echo Text::_('JSTATUS'); ?></dt><dd class="col-7"><?php echo Text::_((int) $this->project->published === 1 ? 'JPUBLISHED' : 'JUNPUBLISHED'); ?></dd>
					<dt class="col-5"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_TIMEZONE_LABEL'); ?></dt><dd class="col-7"><?php echo $this->project->timezone === '' ? Text::_('COM_JOOMLEAGUE_PROJECT_OPTION_USE_DEFAULT_TIMEZONE') : $this->escape($this->project->timezone); ?></dd>
					<dt class="col-5"><?php echo Text::_('JGLOBAL_FIELD_ID_LABEL'); ?></dt><dd class="col-7"><?php echo $projectId; ?></dd>
				</dl>
			</section>
		</div>
	</div>
</div>
