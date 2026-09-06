<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$formatValue = static function (mixed $value): string {
	if (is_bool($value)) {
		return Text::_($value ? 'JYES' : 'JNO');
	}

	if (is_array($value)) {
		return implode(', ', array_map(static fn ($item): string => (string) $item, $value));
	}

	$key = 'COM_JOOMLEAGUE_TEMPLATE_OPTION_' . strtoupper((string) $value);
	$translated = Text::_($key);

	return $translated === $key ? (string) $value : $translated;
};
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=projecttemplates&project_id=' . (int) $this->project->id); ?>" method="post" name="adminForm" id="adminForm">
	<div class="alert alert-info" role="alert">
		<strong><?php echo $this->escape($this->project->sport_type_name); ?></strong>
		<?php echo Text::_($this->project->profile_name_key); ?> <?php echo $this->escape($this->project->profile_version); ?>.
		<?php echo Text::_('COM_JOOMLEAGUE_PROJECTTEMPLATES_INTRO'); ?>
	</div>

	<?php echo HTMLHelper::_('uitab.startTabSet', 'projectTemplateTabs', ['active' => $this->templateGroups[0]['code'] ?? '', 'recall' => true, 'breakpoint' => 768]); ?>
	<?php foreach ($this->templateGroups as $group) : ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'projectTemplateTabs', $group['code'], Text::_($group['name_key'])); ?>
		<fieldset class="options-form">
			<legend class="visually-hidden"><?php echo Text::_($group['name_key']); ?></legend>
			<p class="text-body-secondary"><?php echo Text::_($group['description_key']); ?></p>
			<div class="table-responsive">
				<table class="table table-striped align-middle">
					<thead>
						<tr>
							<th scope="col" class="w-50"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTTEMPLATES_COLUMN_FIELD'); ?></th>
							<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTTEMPLATES_COLUMN_INHERITED'); ?></th>
							<th scope="col" class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTTEMPLATES_COLUMN_OVERRIDE'); ?></th>
							<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTTEMPLATES_COLUMN_PROJECT_VALUE'); ?></th>
							<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTTEMPLATES_COLUMN_EFFECTIVE'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($group['fields'] as $field) : ?>
						<tr>
							<th scope="row">
								<div><?php echo Text::_($field['label_key']); ?></div>
								<div class="small text-body-secondary"><?php echo Text::_($field['description_key']); ?></div>
							</th>
							<td><?php echo $this->escape($formatValue($field['inherited'])); ?></td>
							<td class="text-center"><?php echo $field['enabled_field']->input; ?><span class="visually-hidden"><?php echo Text::_($field['enabled_field']->description); ?></span></td>
							<td><?php echo $field['value_field']->input; ?></td>
							<td><?php echo $this->escape($formatValue($field['effective'])); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</fieldset>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php endforeach; ?>
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

	<?php echo $this->form->getInput('project_id'); ?>
	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
