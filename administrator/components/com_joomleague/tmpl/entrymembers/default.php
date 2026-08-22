<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$entryId = (int) $this->entry->id;
$roleLabels = [];
foreach ($this->entry->profile['positions'] ?? [] as $position) {
	$roleLabels[(string) $position['code']] = (string) $position['name_key'];
}
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=entrymembers&entry_id=' . $entryId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->entry->resolved_name); ?></strong><span class="ms-2"><?php echo $this->escape($this->entry->project_name); ?></span></div>
		<div class="table-responsive"><table class="table table-striped"><thead><tr>
			<td class="text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PERSON_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PERSON_TYPE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ROLE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SHIRT_NUMBER_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_VALID_FROM_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_VALID_UNTIL_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_LIFECYCLE_STATE_LABEL'); ?></th><th class="text-center"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
		</tr></thead><tbody>
		<?php foreach ($this->members as $index => $member) : $name = trim((string) $member->first_name . ' ' . (string) $member->last_name); ?>
			<tr><td class="text-center"><?php echo HTMLHelper::_('grid.id', $index, (int) $member->id); ?></td><th scope="row"><?php if ($this->canEdit) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&task=entrymember.edit&id=' . (int) $member->id . '&entry_id=' . $entryId); ?>"><?php echo $this->escape($name); ?></a><?php else : ?><?php echo $this->escape($name); ?><?php endif; ?></th><td><?php echo Text::_('COM_JOOMLEAGUE_PERSON_TYPE_' . strtoupper((string) $member->member_person_type)); ?></td><td><?php if (!$member->role_code) : ?><?php echo Text::_('JNONE'); ?><?php elseif (isset($roleLabels[(string) $member->role_code])) : ?><?php echo Text::_($roleLabels[(string) $member->role_code]); ?><?php else : ?><?php echo $this->escape((string) $member->role_code); ?><?php endif; ?></td><td><?php echo $this->escape((string) $member->shirt_number); ?></td><td><?php echo $this->escape((string) $member->valid_from); ?></td><td><?php echo $this->escape((string) $member->valid_until); ?></td><td><?php echo Text::_('COM_JOOMLEAGUE_LIFECYCLE_' . strtoupper((string) $member->lifecycle_state)); ?></td><td class="text-center"><?php echo (int) $member->id; ?></td></tr>
		<?php endforeach; ?>
		<?php if ($this->members === []) : ?><tr><td colspan="9"><div class="alert alert-warning mb-0"><?php echo Text::_('COM_JOOMLEAGUE_ENTRYMEMBERS_EMPTY'); ?></div></td></tr><?php endif; ?>
		</tbody></table></div>
	</div>
	<input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0"><input type="hidden" name="entry_id" value="<?php echo $entryId; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
