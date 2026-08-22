<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$matchId = (int) $this->match->id;
$writable = array_filter($this->statistics, static fn (array $definition): bool => in_array($definition['source'], ['manual', 'manual_or_import'], true));
$managed = array_filter($this->statistics, static fn (array $definition): bool => !in_array($definition['source'], ['manual', 'manual_or_import'], true));
$participantNames = [];
foreach ($this->participants as $participant) $participantNames[(int) $participant->id] = (string) $participant->display_name;
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=matchstatistics&match_id=' . $matchId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->project->name); ?></strong><span class="mx-2">/</span><?php echo $this->escape($this->match->match_number ?: Text::_('COM_JOOMLEAGUE_MATCH_UNNUMBERED')); ?><div><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_DESC'); ?></div></div>
		<?php if ($this->canEdit && $writable !== []) : ?>
		<fieldset class="options-form mb-4"><legend><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_ENTER'); ?></legend>
			<div class="row g-3">
				<div class="col-lg-4"><label class="form-label" for="match-statistic-code"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_STATISTIC_LABEL'); ?></label><select class="form-select" id="match-statistic-code" name="statistic_code" required><option value=""><?php echo Text::_('COM_JOOMLEAGUE_OPTION_SELECT_STATISTIC'); ?></option><?php foreach ($writable as $definition) : ?><option value="<?php echo $this->escape($definition['code']); ?>"><?php echo Text::_($definition['name_key']); ?> (<?php echo $this->escape($definition['scope']); ?> / <?php echo $this->escape($definition['value_type'] ?? 'integer'); ?>)</option><?php endforeach; ?></select><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_STATISTIC_DESC'); ?></div></div>
				<div class="col-lg-4"><label class="form-label" for="match-statistic-target"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_TARGET_LABEL'); ?></label><select class="form-select" id="match-statistic-target" name="target" required><option value=""><?php echo Text::_('COM_JOOMLEAGUE_OPTION_SELECT_TARGET'); ?></option><optgroup label="<?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_PARTICIPANTS'); ?>"><?php foreach ($this->participants as $participant) : ?><option value="participant:<?php echo (int) $participant->id; ?>"><?php echo $this->escape($participant->display_name); ?> (<?php echo $this->escape($participant->entry_kind); ?>)</option><?php endforeach; ?></optgroup><optgroup label="<?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_LINEUP_PERSONS'); ?>"><?php foreach ($this->lineup as $member) : ?><option value="person:<?php echo (int) $member->id; ?>"><?php echo $this->escape(($participantNames[(int) $member->match_participant_id] ?? '') . ' - ' . $member->display_name); ?> (<?php echo $this->escape($member->role_code ?: $member->member_person_type); ?>)</option><?php endforeach; ?></optgroup></select><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_TARGET_DESC'); ?></div></div>
				<div class="col-lg-4"><label class="form-label" for="match-statistic-segment"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SCORE_SEGMENT_LABEL'); ?></label><select class="form-select" id="match-statistic-segment" name="score_segment_id"><option value=""><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_WHOLE_MATCH'); ?></option><?php foreach ($this->segments as $segment) : ?><option value="<?php echo (int) $segment->id; ?>"><?php echo $this->escape($segment->level_code); ?> <?php echo (int) $segment->sequence_number; ?></option><?php endforeach; ?></select><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_SEGMENT_DESC'); ?></div></div>
				<div class="col-lg-6"><label class="form-label" for="match-statistic-value"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_VALUE_LABEL'); ?></label><input class="form-control" id="match-statistic-value" name="value" required><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_VALUE_DESC'); ?></div></div>
				<div class="col-lg-6"><label class="form-label" for="match-statistic-notes"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NOTES_LABEL'); ?></label><textarea class="form-control" id="match-statistic-notes" name="notes" rows="2"></textarea><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_NOTES_DESC'); ?></div></div>
			</div>
			<button class="btn btn-success mt-3" type="submit" name="task" value="matchstatistics.saveValue"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button>
		</fieldset>
		<?php elseif ($writable === []) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_NO_MANUAL'); ?></div><?php endif; ?>

		<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_VALUES'); ?></h2>
		<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th class="w-1"><span class="visually-hidden"><?php echo Text::_('JGLOBAL_SELECTION'); ?></span></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_STATISTIC_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_TARGET_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SCOPE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SCORE_SEGMENT_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_VALUE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SOURCE_LABEL'); ?></th></tr></thead><tbody>
		<?php foreach ($this->values as $value) : ?><tr><td><input class="form-check-input" type="checkbox" name="cid[]" value="<?php echo (int) $value->id; ?>" aria-label="<?php echo Text::_('JGLOBAL_SELECTION'); ?>"></td><th scope="row"><?php echo Text::_($value->statistic_name_key); ?></th><td><?php echo $this->escape($value->target_name_snapshot); ?></td><td><?php echo $this->escape($value->scope_code); ?></td><td><?php echo $this->escape($value->segment_code_snapshot ? $value->segment_code_snapshot . ' ' . $value->segment_sequence_snapshot : Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_WHOLE_MATCH')); ?></td><td><?php echo $this->escape($this->getModel()->displayValue($value)); ?></td><td><span class="badge bg-info text-dark"><?php echo Text::_('COM_JOOMLEAGUE_STATISTIC_SOURCE_' . strtoupper($value->calculation_source)); ?></span></td></tr><?php endforeach; ?>
		<?php if ($this->values === []) : ?><tr><td colspan="7"><div class="alert alert-light mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_EMPTY'); ?></div></td></tr><?php endif; ?>
		</tbody></table></div>
		<?php if ($this->canEdit && $this->values !== []) : ?><button class="btn btn-danger" type="submit" name="task" value="matchstatistics.remove"><span class="icon-minus" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_REMOVE'); ?></button><?php endif; ?>

		<h2 class="h4 mt-4"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_MANAGED_TITLE'); ?></h2>
		<p class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_MANAGED_DESC'); ?></p>
		<div class="row g-3"><?php foreach ($managed as $definition) : ?><div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-body"><h3 class="h6"><?php echo Text::_($definition['name_key']); ?></h3><span class="badge bg-secondary me-1"><?php echo Text::_('COM_JOOMLEAGUE_STATISTIC_SOURCE_' . strtoupper($definition['source'])); ?></span><span class="badge bg-light text-dark"><?php echo $this->escape($definition['scope']); ?></span></div></div></div><?php endforeach; ?></div>
	</div>
	<input type="hidden" name="task" value=""><input type="hidden" name="match_id" value="<?php echo $matchId; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
