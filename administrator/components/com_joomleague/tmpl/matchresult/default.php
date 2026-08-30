<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$context = $this->context;
$state = $context['form_state'];
$participantNames = [];

foreach ($context['participants'] as $participant) $participantNames[$participant['id']] = $participant['name'];
$codeLabel = static fn (string $code): string => Text::_('COM_JOOMLEAGUE_RESULT_CODE_' . strtoupper($code));
$segmentTypes = [];

foreach ($context['editor_schema']['segment_types'] as $segmentType) {
	$segmentTypes[(string) $segmentType['code']] = $segmentType;
}

$renderSegment = function (array $segment, string $path, string $locator) use (&$renderSegment, $participantNames, $context, $codeLabel, $segmentTypes): string {
	ob_start();
	$label = $segment['level_code'] === 'result' ? Text::_('COM_JOOMLEAGUE_MATCHRESULT_FINAL_SCORE') : Text::_((string) $segment['name_key']);
	if ($segment['level_code'] !== 'result' && ((int) $segment['sequence_number'] > 1 || (bool) $segment['required'])) $label .= ' ' . (int) $segment['sequence_number'];
	$id = str_replace(['[', ']'], ['_', ''], $path);
	$editorControl = (string) ($segment['editor_control'] ?? 'number');
	?>
	<section class="mb-4" aria-labelledby="<?php echo $this->escape($id . '_heading'); ?>">
		<h3 class="h5" id="<?php echo $this->escape($id . '_heading'); ?>"><?php echo $this->escape($label); ?></h3>
		<input type="hidden" name="<?php echo $this->escape($path); ?>[level_code]" value="<?php echo $this->escape($segment['level_code']); ?>">
		<input type="hidden" name="<?php echo $this->escape($path); ?>[sequence_number]" value="<?php echo (int) $segment['sequence_number']; ?>">
		<input type="hidden" name="<?php echo $this->escape($path); ?>[required]" value="<?php echo $segment['required'] ? 1 : 0; ?>">
		<?php if ($segment['level_code'] !== 'result' && !$segment['required'] && $segment['condition_code'] === null) : ?>
		<div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" value="1" id="<?php echo $this->escape($id . '_included'); ?>" name="<?php echo $this->escape($path); ?>[included]"<?php echo $segment['included'] ? ' checked' : ''; ?>><label class="form-check-label" for="<?php echo $this->escape($id . '_included'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_INCLUDE_SEGMENT'); ?></label></div>
		<?php elseif ($segment['required']) : ?><input type="hidden" name="<?php echo $this->escape($path); ?>[included]" value="1"><?php endif; ?>
		<?php if ($editorControl !== 'none') : ?>
		<div class="table-responsive"><table class="table align-middle"><thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_PARTICIPANT'); ?></th><?php if ($editorControl !== 'status_rank') : ?><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_VALUE'); ?></th><?php endif; ?><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_RANK'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_PARTICIPANT_STATUS'); ?></th></tr></thead><tbody>
		<?php foreach ($segment['values'] as $value) : $participantId = (int) $value['participant_id']; $valuePath = $path . '[values][' . $participantId . ']'; ?>
		<tr><th scope="row"><?php echo $this->escape($participantNames[$participantId] ?? Text::sprintf('COM_JOOMLEAGUE_MATCHRESULT_UNKNOWN_PARTICIPANT', $participantId)); ?></th>
		<?php if ($editorControl !== 'status_rank') : ?><td>
		<?php if ($editorControl === 'text') : ?><input class="form-control" type="text" maxlength="255" name="<?php echo $this->escape($valuePath); ?>[text_value]" value="<?php echo $this->escape((string) ($value['text_value'] ?? '')); ?>" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_VALUE'); ?>">
		<?php elseif ($editorControl === 'duration') : ?><input class="form-control" type="text" inputmode="decimal" maxlength="20" placeholder="<?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_DURATION_PLACEHOLDER'); ?>" name="<?php echo $this->escape($valuePath); ?>[duration_value]" value="<?php echo $this->escape((string) ($value['duration_value'] ?? '')); ?>" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_VALUE'); ?>"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_DURATION_DESC'); ?></div>
		<?php else : ?><input class="form-control" type="number" step="<?php echo $segment['value_type'] === 'integer' ? '1' : '0.001'; ?>" name="<?php echo $this->escape($valuePath); ?>[numeric_value]" value="<?php echo $this->escape((string) ($value['numeric_value'] ?? '')); ?>" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_VALUE'); ?>"><?php endif; ?>
		</td><?php endif; ?><td><input class="form-control" type="number" min="1" step="1" name="<?php echo $this->escape($valuePath); ?>[result_rank]" value="<?php echo $this->escape((string) ($value['result_rank'] ?? '')); ?>" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_RANK'); ?>"></td><td><select class="form-select" name="<?php echo $this->escape($valuePath); ?>[status_code]" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_PARTICIPANT_STATUS'); ?>"><option value=""><?php echo Text::_('JNONE'); ?></option><?php foreach ($context['editor_schema']['participant_statuses'] as $status) : ?><option value="<?php echo $this->escape($status); ?>"<?php echo ($value['status_code'] ?? null) === $status ? ' selected' : ''; ?>><?php echo $this->escape($codeLabel($status)); ?></option><?php endforeach; ?></select></td></tr>
		<?php endforeach; ?></tbody></table></div>
		<?php endif; ?>
		<?php foreach ($segment['children'] as $index => $child) echo $renderSegment($child, $path . '[children][' . $index . ']', $locator . '/' . $child['level_code'] . ':' . (int) $child['sequence_number']); ?>
		<?php
		$childCounts = array_count_values(array_column($segment['children'], 'level_code'));
		foreach ($context['editor_schema']['segment_types'] as $childType) :
			$parentCode = (string) (($childType['parent_code'] ?? null) ?: 'result');
			$count = $childCounts[$childType['code']] ?? 0;
			$canAdd = $parentCode === $segment['level_code']
				&& ($segment['level_code'] === 'result' || $segment['included'] || $segment['required'])
				&& ($childType['repeatable'] ?? false) === true
				&& !isset($childType['expected_count'])
				&& (!isset($childType['maximum_count']) || $count < (int) $childType['maximum_count']);
			if (!$canAdd) continue;
			$addUrl = 'index.php?option=com_joomleague&segment_locator=' . rawurlencode($locator) . '&segment_code=' . rawurlencode((string) $childType['code']);
			?>
			<button type="submit" class="btn btn-success btn-sm me-2 mb-2" name="task" value="matchresult.addSegment" formaction="<?php echo $this->escape(Route::_($addUrl, false)); ?>"><span class="icon-plus" aria-hidden="true"></span> <?php echo $this->escape(Text::sprintf('COM_JOOMLEAGUE_MATCHRESULT_ADD_SEGMENT', Text::_((string) $childType['name_key']))); ?></button>
		<?php endforeach; ?>
		<?php
		$currentType = $segmentTypes[$segment['level_code']] ?? null;
		if ($segment['level_code'] !== 'result' && is_array($currentType) && ($currentType['repeatable'] ?? false) === true && !isset($currentType['expected_count']) && $segment['included']) :
			$removeUrl = 'index.php?option=com_joomleague&segment_locator=' . rawurlencode($locator);
			?>
			<button type="submit" class="btn btn-danger btn-sm mb-2" name="task" value="matchresult.removeSegment" formaction="<?php echo $this->escape(Route::_($removeUrl, false)); ?>"><span class="icon-minus" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_REMOVE_SEGMENT'); ?></button>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
};
?>
<form action="index.php?option=com_joomleague" method="post" name="adminForm" id="adminForm"><div class="main-card">
	<div class="alert alert-info" role="status">
		<strong><?php echo $this->escape($context['project']['name']); ?></strong><span class="mx-2">/</span><?php echo $this->escape($context['round']['name']); ?><span class="mx-2">/</span><?php echo $this->escape($context['match']['match_number'] ?? Text::_('COM_JOOMLEAGUE_MATCH_UNNUMBERED')); ?>
	</div>
	<?php echo HTMLHelper::_('uitab.startTabSet', 'matchResultTabs', ['active' => 'result', 'recall' => true, 'breakpoint' => 768]); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'matchResultTabs', 'result', Text::_('COM_JOOMLEAGUE_MATCHRESULT_TAB_RESULT')); ?>
	<div class="row g-3 mb-3"><div class="col-lg-6"><label class="form-label" for="jform_status_code"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_STATUS'); ?></label><select class="form-select" id="jform_status_code" name="jform[status_code]"><?php foreach ($context['editor_schema']['statuses'] as $status) : ?><option value="<?php echo $this->escape($status); ?>"<?php echo $state['status_code'] === $status ? ' selected' : ''; ?>><?php echo $this->escape($codeLabel($status)); ?></option><?php endforeach; ?></select></div><div class="col-lg-6"><label class="form-label" for="jform_outcome_code"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_OUTCOME'); ?></label><select class="form-select" id="jform_outcome_code" name="jform[outcome_code]"><option value=""><?php echo Text::_('JNONE'); ?></option><?php foreach ($context['editor_schema']['outcomes'] as $outcome) : ?><option value="<?php echo $this->escape($outcome); ?>"<?php echo ($state['outcome_code'] ?? null) === $outcome ? ' selected' : ''; ?>><?php echo $this->escape($codeLabel($outcome)); ?></option><?php endforeach; ?></select></div></div>
	<?php if ($state['conditions'] !== []) : ?>
	<fieldset class="options-form mb-4"><legend><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_PHASES'); ?></legend>
		<?php foreach ($state['conditions'] as $condition => $active) : $conditionId = 'jform_condition_' . $condition; ?>
		<div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" value="1" id="<?php echo $this->escape($conditionId); ?>" name="jform[conditions][<?php echo $this->escape($condition); ?>]"<?php echo $active ? ' checked' : ''; ?>><label class="form-check-label" for="<?php echo $this->escape($conditionId); ?>"><?php echo $this->escape(Text::_('COM_JOOMLEAGUE_RESULT_CONDITION_' . strtoupper($condition))); ?></label></div>
		<?php endforeach; ?>
	</fieldset>
	<?php endif; ?>
	<input type="hidden" name="jform[result_type]" value="<?php echo $this->escape($state['result_type']); ?>">
	<input type="hidden" name="jform[finalized_at]" value="<?php echo $this->escape((string) ($state['finalized_at'] ?? '')); ?>">
	<?php echo $renderSegment($state['segments'][0], 'jform[segments][0]', 'result:1'); ?>
	<div class="mb-3"><label class="form-label" for="jform_notes"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NOTES_LABEL'); ?></label><textarea class="form-control" id="jform_notes" name="jform[notes]" rows="4"><?php echo $this->escape((string) ($state['notes'] ?? '')); ?></textarea></div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'matchResultTabs', 'overview', Text::_('COM_JOOMLEAGUE_MATCHRESULT_TAB_OVERVIEW')); ?>
	<div class="row g-3">
		<div class="col-xl-6"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_MATCH_LEGEND'); ?></legend><dl class="row mb-0"><dt class="col-sm-5"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_PROFILE_LABEL'); ?></dt><dd class="col-sm-7"><?php echo $this->escape($context['profile']['code'] . ' ' . $context['profile']['version']); ?></dd><dt class="col-sm-5"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_TYPE_LABEL'); ?></dt><dd class="col-sm-7"><?php echo $this->escape($context['editor_schema']['result_type']); ?></dd><dt class="col-sm-5"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_UNIT_LABEL'); ?></dt><dd class="col-sm-7"><?php echo $this->escape($context['editor_schema']['unit']); ?></dd></dl></fieldset></div>
		<div class="col-xl-6"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_PARTICIPANTS_LEGEND'); ?></legend><ol class="mb-0"><?php foreach ($context['participants'] as $participant) : ?><li value="<?php echo (int) $participant['slot_number']; ?>"><?php echo $this->escape($participant['name']); ?></li><?php endforeach; ?></ol></fieldset></div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</div><input type="hidden" name="task" value=""><input type="hidden" name="match_id" value="<?php echo (int) $context['match']['id']; ?>"><input type="hidden" name="round_id" value="<?php echo (int) $context['round']['id']; ?>"><?php echo HTMLHelper::_('form.token'); ?></form>
