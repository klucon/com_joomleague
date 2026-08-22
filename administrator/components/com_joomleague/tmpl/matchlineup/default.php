<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');
$matchId = (int) $this->match->id;
$participantId = (int) ($this->participant->id ?? 0);
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=matchlineup&match_id=' . $matchId . '&participant_id=' . $participantId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<div class="alert alert-info" role="status">
			<strong><?php echo $this->escape($this->match->project_name); ?></strong>
			<span class="mx-2">/</span><?php echo $this->escape($this->match->match_number ?: Text::_('COM_JOOMLEAGUE_MATCH_UNNUMBERED')); ?>
		</div>
		<div class="d-flex flex-wrap gap-2 mb-4" role="navigation" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_PARTICIPANTS'); ?>">
			<?php foreach ($this->participants as $participant) : ?>
				<a class="btn <?php echo (int) $participant->id === $participantId ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchlineup&match_id=' . $matchId . '&participant_id=' . (int) $participant->id); ?>">
					<?php echo $this->escape($participant->resolved_name); ?>
					<span class="badge text-bg-light ms-1"><?php echo (int) $participant->available_member_count; ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<?php if (!$this->participant) : ?>
			<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SELECT_PARTICIPANT'); ?></div>
		<?php else : ?>
			<?php echo HTMLHelper::_('uitab.startTabSet', 'lineupTabs', ['active' => 'players', 'recall' => true, 'breakpoint' => 768]); ?>
			<?php foreach (['player' => 'COM_JOOMLEAGUE_MATCHLINEUP_PLAYERS', 'staff' => 'COM_JOOMLEAGUE_MATCHLINEUP_STAFF'] as $personType => $tabLabel) : ?>
				<?php echo HTMLHelper::_('uitab.addTab', 'lineupTabs', $personType . 's', Text::_($tabLabel)); ?>
				<h2 class="h4"><?php echo Text::sprintf('COM_JOOMLEAGUE_MATCHLINEUP_FOR_PARTICIPANT', Text::_($tabLabel), $this->escape($this->participant->resolved_name)); ?></h2>
				<h3 class="h5 mt-4"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_AVAILABLE'); ?></h3>
				<div class="table-responsive"><table class="table table-striped align-middle">
					<thead><tr><th class="w-1"><span class="visually-hidden"><?php echo Text::_('JGLOBAL_SELECTION'); ?></span></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PERSON_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ROLE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SHIRT_NUMBER_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_STATUS'); ?></th><?php if ($personType === 'player') : ?><th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_CAPTAIN_LABEL'); ?></th><?php endif; ?></tr></thead>
					<tbody>
					<?php $availableCount = 0; foreach ($this->availableMembers as $member) : if ($member->member_person_type !== $personType || $member->lineup_id !== null) continue; $availableCount++; $memberId = (int) $member->id; ?>
						<tr><td><input class="form-check-input" type="checkbox" name="cid[]" value="<?php echo $memberId; ?>" aria-label="<?php echo Text::_('JGLOBAL_SELECTION'); ?>"></td><th scope="row"><?php echo $this->escape(trim($member->first_name . ' ' . $member->last_name)); ?></th><td><?php echo $member->role_code ? $this->escape($member->role_code) : Text::_('JNONE'); ?></td><td><?php echo $member->shirt_number ? $this->escape($member->shirt_number) : Text::_('JNONE'); ?></td><td><select class="form-select form-select-sm" name="lineup_status[<?php echo $memberId; ?>]"><?php if ($personType === 'player') : ?><option value="starter"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_STATUS_STARTER'); ?></option><option value="substitute"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_STATUS_SUBSTITUTE'); ?></option><?php endif; ?><option value="<?php echo $personType === 'player' ? 'available' : 'active'; ?>"><?php echo Text::_($personType === 'player' ? 'COM_JOOMLEAGUE_MATCHLINEUP_STATUS_AVAILABLE' : 'COM_JOOMLEAGUE_MATCHLINEUP_STATUS_ACTIVE'); ?></option></select></td><?php if ($personType === 'player') : ?><td class="text-center"><input class="form-check-input" type="checkbox" name="captain[<?php echo $memberId; ?>]" value="1" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_FIELD_CAPTAIN_LABEL'); ?>"></td><?php endif; ?></tr>
					<?php endforeach; ?>
					<?php if ($availableCount === 0) : ?><tr><td colspan="<?php echo $personType === 'player' ? 6 : 5; ?>"><div class="alert alert-light mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_AVAILABLE_EMPTY'); ?></div></td></tr><?php endif; ?>
					</tbody>
				</table></div>

				<h3 class="h5 mt-4"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_ASSIGNED'); ?></h3>
				<div class="table-responsive"><table class="table table-striped align-middle">
					<thead><tr><th class="w-1"><span class="visually-hidden"><?php echo Text::_('JGLOBAL_SELECTION'); ?></span></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PERSON_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ROLE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SHIRT_NUMBER_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_STATUS'); ?></th><?php if ($personType === 'player') : ?><th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_CAPTAIN_LABEL'); ?></th><?php endif; ?></tr></thead>
					<tbody>
					<?php $assignedCount = 0; foreach ($this->assignedMembers as $member) : if ($member->member_person_type !== $personType) continue; $assignedCount++; ?>
						<tr><td><input class="form-check-input" type="checkbox" name="rid[]" value="<?php echo (int) $member->id; ?>" aria-label="<?php echo Text::_('JGLOBAL_SELECTION'); ?>"></td><th scope="row"><?php echo $this->escape(trim($member->first_name . ' ' . $member->last_name)); ?></th><td><?php echo $member->role_code ? $this->escape($member->role_code) : Text::_('JNONE'); ?></td><td><?php echo $member->shirt_number ? $this->escape($member->shirt_number) : Text::_('JNONE'); ?></td><td><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_STATUS_' . strtoupper($member->lineup_status)); ?></td><?php if ($personType === 'player') : ?><td class="text-center"><?php echo Text::_((int) $member->is_captain === 1 ? 'JYES' : 'JNO'); ?></td><?php endif; ?></tr>
					<?php endforeach; ?>
					<?php if ($assignedCount === 0) : ?><tr><td colspan="<?php echo $personType === 'player' ? 6 : 5; ?>"><div class="alert alert-light mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_ASSIGNED_EMPTY'); ?></div></td></tr><?php endif; ?>
					</tbody>
				</table></div>
				<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php endforeach; ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'lineupTabs', 'substitutions', Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTIONS')); ?>
			<?php if (!$this->substitutionsSupported) : ?>
				<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTIONS_UNSUPPORTED'); ?></div>
			<?php else : ?>
				<?php $playerMembers = array_values(array_filter($this->assignedMembers, static fn (object $member): bool => $member->member_person_type === 'player')); ?>
				<fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_ADD'); ?></legend>
					<div class="row g-3">
						<div class="col-md-6"><label class="form-label" for="substitution-outgoing"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_OUTGOING'); ?></label><select class="form-select" id="substitution-outgoing" name="substitution[outgoing_id]" required><option value=""><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SELECT_PLAYER'); ?></option><?php foreach ($playerMembers as $member) : ?><option value="<?php echo (int) $member->id; ?>"><?php echo $this->escape(trim($member->first_name . ' ' . $member->last_name)); ?></option><?php endforeach; ?></select><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_OUTGOING_DESC'); ?></div></div>
						<div class="col-md-6"><label class="form-label" for="substitution-incoming"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_INCOMING'); ?></label><select class="form-select" id="substitution-incoming" name="substitution[incoming_id]" required><option value=""><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SELECT_PLAYER'); ?></option><?php foreach ($playerMembers as $member) : ?><option value="<?php echo (int) $member->id; ?>"><?php echo $this->escape(trim($member->first_name . ' ' . $member->last_name)); ?></option><?php endforeach; ?></select><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_INCOMING_DESC'); ?></div></div>
						<div class="col-md-3"><label class="form-label" for="substitution-phase"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_PHASE'); ?></label><select class="form-select" id="substitution-phase" name="substitution[phase_code]"><option value=""><?php echo Text::_('JNONE'); ?></option><?php foreach ($this->substitutionPhases as $code => $nameKey) : ?><option value="<?php echo $this->escape($code); ?>"><?php echo Text::_($nameKey); ?></option><?php endforeach; ?></select><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_PHASE_DESC'); ?></div></div>
						<div class="col-md-3"><label class="form-label" for="substitution-phase-sequence"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_PHASE_SEQUENCE'); ?></label><input class="form-control" id="substitution-phase-sequence" name="substitution[phase_sequence]" type="number" min="1"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_PHASE_SEQUENCE_DESC'); ?></div></div>
						<div class="col-md-3"><label class="form-label" for="substitution-clock"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_CLOCK_VALUE'); ?></label><input class="form-control" id="substitution-clock" name="substitution[clock_value]" type="text" inputmode="decimal" maxlength="31"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_CLOCK_VALUE_DESC'); ?></div></div>
						<div class="col-md-3"><label class="form-label" for="substitution-clock-unit"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_CLOCK_UNIT'); ?></label><input class="form-control" id="substitution-clock-unit" name="substitution[clock_unit]" type="text" maxlength="50"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_CLOCK_UNIT_DESC'); ?></div></div>
						<div class="col-12"><label class="form-label" for="substitution-notes"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NOTES_LABEL'); ?></label><textarea class="form-control" id="substitution-notes" name="substitution[notes]" rows="3"></textarea><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_NOTES_DESC'); ?></div></div>
					</div>
					<button class="btn btn-success mt-3" type="submit" name="task" value="matchlineup.addSubstitution"><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_ADD'); ?></button>
				</fieldset>
				<h3 class="h5 mt-4"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_HISTORY'); ?></h3>
				<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th class="w-1"><span class="visually-hidden"><?php echo Text::_('JGLOBAL_SELECTION'); ?></span></th><th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SEQUENCE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_OUTGOING'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_INCOMING'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_PHASE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_CLOCK'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NOTES_LABEL'); ?></th></tr></thead><tbody>
				<?php foreach ($this->substitutions as $change) : ?><tr><td><input class="form-check-input" type="checkbox" name="sid[]" value="<?php echo (int) $change->id; ?>" aria-label="<?php echo Text::_('JGLOBAL_SELECTION'); ?>"></td><td class="text-center"><?php echo (int) $change->sequence_number; ?></td><td><?php echo $this->escape(trim($change->outgoing_first_name . ' ' . $change->outgoing_last_name)); ?></td><td><?php echo $this->escape(trim($change->incoming_first_name . ' ' . $change->incoming_last_name)); ?></td><td><?php echo $change->phase_code ? $this->escape(Text::_($this->substitutionPhases[$change->phase_code] ?? $change->phase_code) . ($change->phase_sequence ? ' ' . (int) $change->phase_sequence : '')) : Text::_('JNONE'); ?></td><td><?php echo $change->clock_value !== null ? $this->escape(rtrim(rtrim((string) $change->clock_value, '0'), '.') . ($change->clock_unit ? ' ' . $change->clock_unit : '')) : Text::_('JNONE'); ?></td><td><?php echo $change->notes ? $this->escape($change->notes) : Text::_('JNONE'); ?></td></tr><?php endforeach; ?>
				<?php if ($this->substitutions === []) : ?><tr><td colspan="7"><div class="alert alert-light mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_EMPTY'); ?></div></td></tr><?php endif; ?>
				</tbody></table></div>
				<button class="btn btn-danger" type="submit" name="task" value="matchlineup.removeSubstitution"><span class="icon-minus" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_REMOVE'); ?></button>
			<?php endif; ?>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
		<?php endif; ?>
	</div>
	<input type="hidden" name="task" value=""><input type="hidden" name="match_id" value="<?php echo $matchId; ?>"><input type="hidden" name="participant_id" value="<?php echo $participantId; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
