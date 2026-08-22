<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$projectId = (int) $this->project->id;
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=standings&project_id=' . $projectId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<div class="alert alert-info"><strong><?php echo $this->escape($this->project->name); ?></strong><?php if ($this->stage !== null) : ?><span class="mx-2">/</span><strong><?php echo $this->escape($this->stage->name); ?></strong><?php endif; ?><span class="mx-2">/</span><?php echo Text::_('COM_JOOMLEAGUE_STANDINGS_SCOPE_' . strtoupper($this->scope)); ?><div><?php echo Text::_('COM_JOOMLEAGUE_STANDINGS_DESC'); ?></div></div>
		<?php if (count($this->availableScopes) > 1) : ?><div class="row mb-3"><div class="col-12 col-md-4"><label class="form-label" for="standingsScope"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_STANDING_SCOPE_LABEL'); ?></label><select class="form-select" id="standingsScope" name="scope"><?php foreach ($this->availableScopes as $scope) : ?><option value="<?php echo $this->escape($scope); ?>"<?php echo $scope === $this->scope ? ' selected' : ''; ?>><?php echo Text::_('COM_JOOMLEAGUE_STANDINGS_SCOPE_' . strtoupper($scope)); ?></option><?php endforeach; ?></select></div><div class="col-12 col-md-auto d-flex align-items-end mt-2 mt-md-0"><button class="btn btn-primary" type="submit"><?php echo Text::_('JAPPLY'); ?></button></div></div><?php endif; ?>
		<?php if ($this->snapshot === null) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_STANDINGS_EMPTY'); ?></div>
		<?php else : ?><p class="text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_STANDINGS_GENERATED', HTMLHelper::_('date', $this->snapshot->generated_at, Text::_('DATE_FORMAT_LC6'))); ?></p>
		<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_RANK_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ENTRY_LABEL'); ?></th><?php foreach ($this->metrics as $metric) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_STANDING_METRIC_' . strtoupper($metric['code'])); ?></th><?php endforeach; ?></tr></thead><tbody>
		<?php foreach ($this->rows as $row) : ?><tr><td><?php echo (int) $row->rank_number; ?></td><th scope="row"><?php echo $this->escape($row->entry_name_snapshot); ?></th><?php foreach ($this->metrics as $metric) : $value = $row->metrics[$metric['code']] ?? null; ?><td><?php echo $value === null ? Text::_('JNONE') : $this->escape((string) $value); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
		</tbody></table></div><?php endif; ?>
	</div>
	<input type="hidden" name="task" value=""><input type="hidden" name="project_id" value="<?php echo $projectId; ?>"><input type="hidden" name="stage_id" value="<?php echo (int) ($this->stageId ?? 0); ?>"><?php if (count($this->availableScopes) <= 1) : ?><input type="hidden" name="scope" value="<?php echo $this->escape($this->scope); ?>"><?php endif; ?><?php echo HTMLHelper::_('form.token'); ?>
</form>
