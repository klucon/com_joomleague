<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$user = Factory::getApplication()->getIdentity();
$draft = $this->draft;
$id = (int) ($draft['id'] ?? 0);
$canEdit = $user->authorise('core.edit', 'com_joomleague');
$canCreate = $user->authorise('core.create', 'com_joomleague');
$canChangeState = $user->authorise('core.edit.state', 'com_joomleague');
$canDelete = $user->authorise('core.delete', 'com_joomleague');
$url = Route::_('index.php?option=com_joomleague&view=sportcategories');
$value = fn (string $key, string $default = ''): string => $this->escape(is_scalar($draft[$key] ?? null) ? (string) $draft[$key] : $default);
?>
<div class="mb-3"><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=sportprofiles'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_BACK_PROFILES'); ?></a></div>
<div class="table-responsive">
	<table class="table table-striped align-middle">
		<thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_NAME'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_CODE'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_PROFILES'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_STATUS'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_ACTIONS'); ?></th></tr></thead>
		<tbody><?php foreach ($this->items as $item): ?><tr>
			<th scope="row"><?php if ($canEdit): ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=sportcategories&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->path); ?></a><?php else: ?><?php echo $this->escape($item->path); ?><?php endif; ?></th>
			<td><code><?php echo $this->escape($item->code); ?></code></td><td><?php echo (int) $item->profile_count; ?></td>
			<td><?php echo Text::_((int) $item->published === 1 ? 'COM_JOOMLEAGUE_SPORTCATEGORY_PUBLISHED' : 'COM_JOOMLEAGUE_SPORTCATEGORY_UNPUBLISHED'); ?></td>
			<td><?php if ($canDelete): ?><form action="<?php echo $url; ?>" method="post"><input type="hidden" name="category_id" value="<?php echo (int) $item->id; ?>"><button type="submit" class="btn btn-outline-danger btn-sm" name="task" value="sportcategories.delete"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_DELETE'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form><?php endif; ?></td>
		</tr><?php endforeach; ?></tbody>
	</table>
</div>
<?php if (($id === 0 && $canCreate) || ($id > 0 && $canEdit)): ?>
<h2 class="h4 mt-4"><?php echo Text::_($id > 0 ? 'COM_JOOMLEAGUE_SPORTCATEGORY_EDIT' : 'COM_JOOMLEAGUE_SPORTCATEGORY_NEW'); ?></h2>
<form action="<?php echo $url; ?>" method="post" class="row g-3">
	<input type="hidden" name="jform[id]" value="<?php echo $id; ?>">
	<div class="col-md-6"><label class="form-label" for="category-name"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_NAME'); ?></label><input class="form-control" id="category-name" name="jform[name]" required maxlength="255" value="<?php echo $value('name'); ?>"></div>
	<div class="col-md-6"><label class="form-label" for="category-code"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_CODE'); ?></label><input class="form-control" id="category-code" name="jform[code]" required pattern="[a-z][a-z0-9_-]{0,99}" maxlength="100" value="<?php echo $value('code'); ?>"><div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_CODE_DESC'); ?></div></div>
	<div class="col-md-6"><label class="form-label" for="category-parent"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_PARENT'); ?></label><select class="form-select" id="category-parent" name="jform[parent_id]"><option value="0"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_ROOT'); ?></option><?php foreach ($this->items as $item): if ((int) $item->id === $id) continue; ?><option value="<?php echo (int) $item->id; ?>"<?php echo (int) ($draft['parent_id'] ?? 0) === (int) $item->id ? ' selected' : ''; ?>><?php echo $this->escape($item->path); ?></option><?php endforeach; ?></select></div>
	<div class="col-md-3"><label class="form-label" for="category-status"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_STATUS'); ?></label><select class="form-select" id="category-status" name="jform[published]"<?php echo $canChangeState ? '' : ' disabled'; ?>><?php foreach ([1 => 'PUBLISHED', 0 => 'UNPUBLISHED'] as $state => $label): ?><option value="<?php echo $state; ?>"<?php echo (int) ($draft['published'] ?? ($canChangeState ? 1 : 0)) === $state ? ' selected' : ''; ?>><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_' . $label); ?></option><?php endforeach; ?></select></div>
	<div class="col-md-3"><label class="form-label" for="category-ordering"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_ORDERING'); ?></label><input type="number" class="form-control" id="category-ordering" name="jform[ordering]" min="0" max="2147483647" value="<?php echo $value('ordering', '0'); ?>"></div>
	<div class="col-12"><label class="form-label" for="category-description"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_DESCRIPTION'); ?></label><textarea class="form-control" id="category-description" name="jform[description]" rows="3" maxlength="10000"><?php echo $value('description'); ?></textarea></div>
	<?php if ($id > 0): ?><div class="col-12"><label class="form-label" for="category-uuid"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_UUID'); ?></label><input class="form-control" id="category-uuid" value="<?php echo $value('uuid'); ?>" readonly></div><?php endif; ?>
	<div class="col-12"><button class="btn btn-primary" type="submit" name="task" value="sportcategories.save"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_SAVE'); ?></button> <a class="btn btn-secondary" href="<?php echo $url; ?>"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_CANCEL'); ?></a></div>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<?php endif; ?>
