<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$o = $this->options;
$context = $this->preview['context'] ?? null;
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=scheduleplanner&stage_id=' . $this->stageId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="main-card">
		<div class="alert alert-info" role="status"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_INTRO'); ?></div>
		<div class="row g-4">
			<div class="col-12 col-xl-6">
				<fieldset><legend><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_SOURCE'); ?></legend>
					<div class="mb-3"><label class="form-label" for="schedule_template_id"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_LABEL'); ?></label><select class="form-select" id="schedule_template_id" name="schedule[template_id]">
					<?php foreach ($this->templates as $template) : $selected = (string) $o['template_id'] === (string) $template->templateId; $executable = in_array($template->type, ['round-robin', 'race'], true); ?>
						<option value="<?php echo $this->escape($template->templateId); ?>"<?php echo $selected ? ' selected' : ''; ?><?php echo !$executable ? ' disabled' : ''; ?>><?php echo Text::_($template->labelKey); ?> (<?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_GENERATION_' . strtoupper($template->generation)); ?>)<?php echo !$executable ? ' - ' . Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_PLANNED') : ''; ?></option>
					<?php endforeach; ?></select><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_DESC'); ?></div></div>
					<div class="row g-3"><div class="col-md-6"><label class="form-label" for="schedule_start_date"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_START_DATE_LABEL'); ?></label><?php echo HTMLHelper::_('calendar', $o['start_date'], 'schedule[start_date]', 'schedule_start_date', '%Y-%m-%d', ['class' => 'form-control', 'showTime' => false]); ?><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_START_DATE_DESC'); ?></div></div>
					<div class="col-md-6"><label class="form-label" for="schedule_start_time"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_START_TIME_LABEL'); ?></label><input class="form-control" type="time" id="schedule_start_time" name="schedule[start_time]" value="<?php echo $this->escape($o['start_time']); ?>"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_START_TIME_DESC'); ?></div></div></div>
				</fieldset>
			</div>
			<div class="col-12 col-xl-6">
				<fieldset><legend><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_OPTIONS'); ?></legend>
					<div class="row g-3"><div class="col-md-6"><label class="form-label" for="schedule_round_interval"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_ROUND_INTERVAL_LABEL'); ?></label><input class="form-control" type="number" min="1" max="365" id="schedule_round_interval" name="schedule[round_interval_days]" value="<?php echo (int) $o['round_interval_days']; ?>"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_ROUND_INTERVAL_DESC'); ?></div></div>
					<div class="col-md-6"><label class="form-label" for="schedule_match_interval"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_MATCH_INTERVAL_LABEL'); ?></label><input class="form-control" type="number" min="0" max="1440" id="schedule_match_interval" name="schedule[match_interval_minutes]" value="<?php echo (int) $o['match_interval_minutes']; ?>"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_MATCH_INTERVAL_DESC'); ?></div></div>
					<div class="col-md-6"><label class="form-label" for="schedule_first_number"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_FIRST_NUMBER_LABEL'); ?></label><input class="form-control" type="number" min="0" id="schedule_first_number" name="schedule[first_match_number]" value="<?php echo (int) $o['first_match_number']; ?>"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_FIRST_NUMBER_DESC'); ?></div></div>
					<div class="col-md-6"><label class="form-label" for="schedule_race_rounds"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_RACE_ROUNDS_LABEL'); ?></label><input class="form-control" type="number" min="1" max="200" id="schedule_race_rounds" name="schedule[race_rounds]" value="<?php echo (int) $o['race_rounds']; ?>"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_RACE_ROUNDS_DESC'); ?></div></div></div>
					<?php foreach (['return_legs', 'assign_home_venues', 'published', 'allow_conflicts'] as $flag) : ?><div class="form-check form-switch mt-3"><input type="hidden" name="schedule[<?php echo $flag; ?>]" value="0"><input class="form-check-input" type="checkbox" role="switch" id="schedule_<?php echo $flag; ?>" name="schedule[<?php echo $flag; ?>]" value="1"<?php echo !empty($o[$flag]) ? ' checked' : ''; ?>><label class="form-check-label" for="schedule_<?php echo $flag; ?>"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_' . strtoupper($flag) . '_LABEL'); ?></label><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_' . strtoupper($flag) . '_DESC'); ?></div></div><?php endforeach; ?>
				</fieldset>
			</div>
		</div>

		<?php if ($this->previewError !== null) : ?><div class="alert alert-danger mt-4" role="alert"><?php echo $this->escape($this->previewError); ?></div><?php endif; ?>
		<?php if ($this->preview !== null) : ?>
			<hr><div class="d-flex flex-wrap gap-2 align-items-center mb-3"><h2 class="h4 mb-0 me-auto"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_PREVIEW_TITLE'); ?></h2><span class="badge bg-info text-dark"><?php echo Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_PREVIEW_COUNTS', count($this->preview['rounds']), $this->preview['match_count']); ?></span></div>
			<?php if ($this->preview['conflicts'] !== []) : ?><div class="mb-3"><?php foreach ($this->preview['conflicts'] as $conflict) : ?><div class="alert alert-<?php echo $conflict['severity'] === 'error' ? 'danger' : 'warning'; ?> py-2 mb-2"><?php echo $this->escape($conflict['message']); ?></div><?php endforeach; ?></div><?php else : ?><div class="alert alert-success"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_NO_CONFLICTS'); ?></div><?php endif; ?>
			<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ROUND_NUMBER_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_DATE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_MATCHES'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_BYE'); ?></th></tr></thead><tbody><?php foreach ($this->preview['rounds'] as $round) : ?><tr><th scope="row"><?php echo $this->escape($round['name']); ?></th><td><?php echo $this->escape($round['date']); ?></td><td><?php foreach ($round['matches'] as $match) : ?><div><?php echo $this->escape(implode(' - ', array_column($match['participants'], 'name'))); ?> <span class="text-body-secondary"><?php echo $this->escape($match['scheduled_local']); ?></span></div><?php endforeach; ?></td><td><?php echo $round['bye'] ? $this->escape($round['bye']['name']) : Text::_('JNONE'); ?></td></tr><?php endforeach; ?></tbody></table></div>
			<p class="small text-body-secondary mb-0"><?php echo Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_CHECKSUM', $this->escape($this->preview['checksum'])); ?></p>
		<?php endif; ?>
	</div>
	<input type="hidden" name="task" value=""><input type="hidden" name="stage_id" value="<?php echo $this->stageId; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
