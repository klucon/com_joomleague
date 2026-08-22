<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$formatDefault = static function (mixed $value): string {
	if (is_bool($value)) return Text::_($value ? 'JYES' : 'JNO');
	if (is_array($value)) return implode(', ', array_map(static fn ($item): string => (string) $item, $value));
	return (string) $value;
};
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=projectrules&project_id=' . (int) $this->project->id); ?>" method="post" name="adminForm" id="projectrules-form">
	<div class="alert alert-info" role="alert">
		<strong><?php echo $this->escape($this->project->sport_type_name); ?></strong>
		<?php echo Text::_($this->project->profile_name_key); ?> <?php echo $this->escape($this->project->profile_version); ?>.
		<?php echo Text::_('COM_JOOMLEAGUE_PROJECTRULES_INTRO'); ?>
	</div>
	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead><tr>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTRULES_COLUMN_RULE'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTRULES_COLUMN_PROFILE_DEFAULT'); ?></th>
				<th scope="col" class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTRULES_COLUMN_OVERRIDE'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTRULES_COLUMN_PROJECT_VALUE'); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ($this->ruleFields as $rule) : ?>
				<tr>
					<th scope="row"><div><?php echo $this->escape($rule['label']); ?></div><div class="small text-body-secondary"><?php echo $this->escape($rule['description']); ?></div></th>
					<td><?php echo $this->escape($formatDefault($rule['default'])); ?></td>
					<td class="text-center"><?php echo $rule['enabled_field']->input; ?><span class="visually-hidden"><?php echo $rule['enabled_field']->description; ?></span></td>
					<td><?php echo $rule['value_field']->input; ?><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTRULES_VALUE_HELP'); ?></div></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php echo $this->form->getInput('project_id'); ?>
	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
