<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$matchId = (int) $this->match->id;
$availableName = static fn (object $row): string => $row->actor_kind === 'person' ? trim((string) $row->first_name . ' ' . (string) $row->last_name) : (string) $row->team_name;
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=matchofficials&match_id=' . $matchId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->project->name); ?></strong><span class="mx-2">/</span><?php echo $this->escape($this->match->match_number ?: Text::_('COM_JOOMLEAGUE_MATCH_UNNUMBERED')); ?><div><?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_DESC'); ?></div></div>
		<?php if ($this->canEdit) : ?><fieldset class="options-form mb-4"><legend><?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_ASSIGN'); ?></legend>
			<?php if ($this->available === []) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_AVAILABLE_EMPTY'); ?> <a class="alert-link" href="<?php echo Route::_('index.php?option=com_joomleague&view=projectofficials&project_id=' . (int) $this->project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_OPEN_PROJECT_POOL'); ?></a></div>
			<?php else : ?><div class="row g-3"><div class="col-lg-6"><label class="form-label" for="match-official-source"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ACTOR_LABEL'); ?></label><select class="form-select" id="match-official-source" name="project_actor_role_id" required><option value=""><?php echo Text::_('COM_JOOMLEAGUE_OPTION_SELECT_ACTOR'); ?></option><?php foreach ($this->available as $row) : ?><option value="<?php echo (int) $row->id; ?>"><?php echo $this->escape($availableName($row)); ?> - <?php echo Text::_($this->roles[$row->role_code]['name_key'] ?? $row->role_code); ?></option><?php endforeach; ?></select><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_ACTOR_DESC'); ?></div></div><div class="col-lg-6"><label class="form-label" for="match-official-notes"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NOTES_LABEL'); ?></label><textarea class="form-control" id="match-official-notes" name="notes" rows="2"></textarea><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_NOTES_DESC'); ?></div></div></div><button class="btn btn-success mt-3" type="submit" name="task" value="matchofficials.assign"><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_ASSIGN'); ?></button><?php endif; ?>
		</fieldset><?php endif; ?>
		<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_ASSIGNED'); ?></h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th class="w-1"><span class="visually-hidden"><?php echo Text::_('JGLOBAL_SELECTION'); ?></span></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ACTOR_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ROLE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NOTES_LABEL'); ?></th></tr></thead><tbody>
		<?php foreach ($this->assignments as $assignment) : ?><tr><td><input class="form-check-input" type="checkbox" name="cid[]" value="<?php echo (int) $assignment->id; ?>" aria-label="<?php echo Text::_('JGLOBAL_SELECTION'); ?>"></td><th scope="row"><?php echo $this->escape($assignment->display_name_snapshot); ?><div class="small text-body-secondary"><?php echo Text::_($assignment->actor_kind === 'person' ? 'COM_JOOMLEAGUE_ACTOR_KIND_PERSON' : 'COM_JOOMLEAGUE_ACTOR_KIND_TEAM'); ?></div></th><td><?php echo Text::_($this->roles[$assignment->role_code]['name_key'] ?? $assignment->role_code); ?></td><td><?php echo $this->escape($assignment->notes ?: Text::_('JNONE')); ?></td></tr><?php endforeach; ?>
		<?php if ($this->assignments === []) : ?><tr><td colspan="4"><div class="alert alert-light mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_EMPTY'); ?></div></td></tr><?php endif; ?>
		</tbody></table></div><?php if ($this->canEdit && $this->assignments !== []) : ?><button class="btn btn-danger" type="submit" name="task" value="matchofficials.remove"><span class="icon-minus" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_REMOVE'); ?></button><?php endif; ?>
	</div>
	<input type="hidden" name="task" value=""><input type="hidden" name="match_id" value="<?php echo $matchId; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
