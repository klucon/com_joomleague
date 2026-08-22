<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$projectId = (int) $this->project->id;
$actorName = static function (object $assignment): string {
	return $assignment->actor_kind === 'person' ? trim((string) $assignment->first_name . ' ' . (string) $assignment->last_name) : (string) $assignment->team_name;
};
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=projectofficials&project_id=' . $projectId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->project->name); ?></strong><div><?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_DESC'); ?></div></div>
		<?php if ($this->roles === []) : ?>
			<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_NO_ROLES'); ?></div>
		<?php elseif ($this->canEdit) : ?>
			<fieldset class="options-form mb-4"><legend><?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_ADD'); ?></legend><div class="row g-3">
				<div class="col-lg-6"><label class="form-label" for="official-actor"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ACTOR_LABEL'); ?></label><select class="form-select" id="official-actor" name="assignment[actor]" required><option value=""><?php echo Text::_('COM_JOOMLEAGUE_OPTION_SELECT_ACTOR'); ?></option><optgroup label="<?php echo Text::_('COM_JOOMLEAGUE_PERSONS_TITLE'); ?>"><?php foreach ($this->persons as $person) : ?><option value="person:<?php echo (int) $person->id; ?>"><?php echo $this->escape(trim($person->first_name . ' ' . $person->last_name)); ?></option><?php endforeach; ?></optgroup><optgroup label="<?php echo Text::_('COM_JOOMLEAGUE_TEAMS_TITLE'); ?>"><?php foreach ($this->teams as $team) : ?><option value="team:<?php echo (int) $team->id; ?>"><?php echo $this->escape($team->name); ?></option><?php endforeach; ?></optgroup></select><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ACTOR_DESC'); ?></div></div>
				<div class="col-lg-6"><label class="form-label" for="official-role"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ROLE_LABEL'); ?></label><select class="form-select" id="official-role" name="assignment[role_code]" required><option value=""><?php echo Text::_('COM_JOOMLEAGUE_OPTION_SELECT_ROLE'); ?></option><?php foreach ($this->roles as $code => $role) : ?><option value="<?php echo $this->escape($code); ?>"><?php echo Text::_($role['name_key']); ?></option><?php endforeach; ?></select><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_ROLE_DESC'); ?></div></div>
				<div class="col-md-6"><label class="form-label" for="official-valid-from"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_VALID_FROM_LABEL'); ?></label><input class="form-control" type="date" id="official-valid-from" name="assignment[valid_from]"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_VALID_FROM_DESC'); ?></div></div>
				<div class="col-md-6"><label class="form-label" for="official-valid-until"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_VALID_UNTIL_LABEL'); ?></label><input class="form-control" type="date" id="official-valid-until" name="assignment[valid_until]"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_VALID_UNTIL_DESC'); ?></div></div>
				<div class="col-12"><label class="form-label" for="official-notes"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NOTES_LABEL'); ?></label><textarea class="form-control" id="official-notes" name="assignment[notes]" rows="3"></textarea><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_NOTES_DESC'); ?></div></div>
			</div><button class="btn btn-success mt-3" type="submit" name="task" value="projectofficials.add"><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_ADD'); ?></button></fieldset>
		<?php endif; ?>
		<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_ASSIGNED'); ?></h2>
		<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th class="w-1"><span class="visually-hidden"><?php echo Text::_('JGLOBAL_SELECTION'); ?></span></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ACTOR_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ROLE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_VALID_FROM_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_VALID_UNTIL_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NOTES_LABEL'); ?></th><th class="text-center"><?php echo Text::_('JSTATUS'); ?></th></tr></thead><tbody>
		<?php foreach ($this->assignments as $assignment) : ?><tr><td><input class="form-check-input" type="checkbox" name="cid[]" value="<?php echo (int) $assignment->id; ?>" aria-label="<?php echo Text::_('JGLOBAL_SELECTION'); ?>"></td><th scope="row"><?php echo $this->escape($actorName($assignment)); ?><div class="small text-body-secondary"><?php echo Text::_($assignment->actor_kind === 'person' ? 'COM_JOOMLEAGUE_ACTOR_KIND_PERSON' : 'COM_JOOMLEAGUE_ACTOR_KIND_TEAM'); ?></div></th><td><?php echo Text::_($this->roles[$assignment->role_code]['name_key'] ?? $assignment->role_code); ?></td><td><?php echo $this->escape($assignment->valid_from ?: Text::_('JNONE')); ?></td><td><?php echo $this->escape($assignment->valid_until ?: Text::_('JNONE')); ?></td><td><?php echo $this->escape($assignment->notes ?: Text::_('JNONE')); ?></td><td class="text-center"><?php echo Text::_((int) $assignment->published === 1 ? 'JPUBLISHED' : 'JUNPUBLISHED'); ?></td></tr><?php endforeach; ?>
		<?php if ($this->assignments === []) : ?><tr><td colspan="7"><div class="alert alert-light mb-0"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_EMPTY'); ?></div></td></tr><?php endif; ?>
		</tbody></table></div>
		<?php if ($this->canEdit && $this->assignments !== []) : ?><button class="btn btn-danger" type="submit" name="task" value="projectofficials.remove"><span class="icon-minus" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_REMOVE'); ?></button><?php endif; ?>
	</div>
	<input type="hidden" name="task" value=""><input type="hidden" name="project_id" value="<?php echo $projectId; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
