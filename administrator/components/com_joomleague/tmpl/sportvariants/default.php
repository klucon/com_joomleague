<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$user = Factory::getApplication()->getIdentity();
$url = Route::_('index.php?option=com_joomleague&view=sportvariants');
$id = (int) ($this->selected['id'] ?? 0);
$canCreate = $user->authorise('core.create', 'com_joomleague');
$canEdit = $user->authorise('core.edit', 'com_joomleague');
$canState = $user->authorise('core.edit.state', 'com_joomleague');
$safe = fn ($value): string => $this->escape(is_scalar($value) ? (string) $value : '');
?>
<div class="mb-3"><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=sportprofiles'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_BACK'); ?></a></div>
<div class="table-responsive"><table class="table table-striped align-middle">
<thead><tr><?php foreach (['NAME', 'SPORT', 'CODE', 'STATUS'] as $column): ?><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_' . $column); ?></th><?php endforeach; ?></tr></thead>
<tbody><?php foreach ($this->items as $item): ?><tr>
<th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=sportvariants&id=' . (int) $item['id']); ?>"><?php echo $safe($item['name']); ?></a></th>
<td><?php echo $safe(Text::_($item['name_key'])); ?></td><td><code><?php echo $safe($item['code']); ?></code></td>
<td><?php echo Text::_((int) $item['published'] === 1 ? 'COM_JOOMLEAGUE_SPORTVARIANT_PUBLISHED' : 'COM_JOOMLEAGUE_SPORTVARIANT_UNPUBLISHED'); ?></td>
</tr><?php endforeach; ?></tbody></table></div>
<?php if ($id === 0 && $canCreate): ?>
<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_NEW'); ?></h2>
<form action="<?php echo $url; ?>" method="post" class="row g-3">
<div class="col-md-4"><label class="form-label" for="variant-sport"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_SPORT'); ?></label><select class="form-select" id="variant-sport" name="jform[profile_id]" required><option value=""><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_SELECT_SPORT'); ?></option><?php foreach ($this->profiles as $profileId => $nameKey): ?><option value="<?php echo $profileId; ?>"<?php echo (int) ($this->createDraft['profile_id'] ?? 0) === $profileId ? ' selected' : ''; ?>><?php echo $safe(Text::_($nameKey)); ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label" for="variant-name"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_NAME'); ?></label><input class="form-control" id="variant-name" name="jform[name]" maxlength="255" required value="<?php echo $safe($this->createDraft['name'] ?? ''); ?>"></div>
<div class="col-md-4"><label class="form-label" for="variant-code"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_CODE'); ?></label><input class="form-control" id="variant-code" name="jform[code]" maxlength="100" required pattern="[a-z][a-z0-9_-]{0,99}" value="<?php echo $safe($this->createDraft['code'] ?? ''); ?>"></div>
<div class="col-12"><button class="btn btn-primary" type="submit" name="task" value="sportvariants.create"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_CREATE'); ?></button></div>
<?php echo HTMLHelper::_('form.token'); ?></form>
<?php endif; ?>
<?php if ($id > 0): ?>
<h2 class="h4"><?php echo $safe($this->selected['name']); ?></h2>
<div class="mb-3"><label class="form-label" for="variant-uuid"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_UUID'); ?></label><input class="form-control" id="variant-uuid" readonly value="<?php echo $safe($this->selected['uuid']); ?>"></div>
<?php if ($canState): ?><form action="<?php echo $url; ?>" method="post" class="mb-3"><input type="hidden" name="variant_id" value="<?php echo $id; ?>"><input type="hidden" name="published" value="<?php echo (int) $this->selected['published'] === 1 ? 0 : 1; ?>"><button class="btn btn-secondary" name="task" value="sportvariants.publish"<?php echo $this->revisions === [] ? ' disabled' : ''; ?>><?php echo Text::_((int) $this->selected['published'] === 1 ? 'COM_JOOMLEAGUE_SPORTVARIANT_UNPUBLISH' : 'COM_JOOMLEAGUE_SPORTVARIANT_PUBLISH'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form><?php endif; ?>
<h3 class="h5"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_REVISIONS'); ?></h3>
<div class="table-responsive"><table class="table table-striped"><thead><tr><?php foreach (['REVISION', 'BASE', 'CHECKSUM', 'CREATED_DATE'] as $column): ?><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_' . $column); ?></th><?php endforeach; ?></tr></thead><tbody>
<?php foreach ($this->revisions as $revision): ?><tr><th scope="row"><?php echo $safe($revision['revision']); ?></th><td><?php echo $safe($revision['profile_version']); ?></td><td class="text-break"><code><?php echo $safe($revision['payload_checksum']); ?></code></td><td><?php echo $safe($revision['created']); ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php if ($canEdit): ?>
<h3 class="h5"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_NEW_REVISION'); ?></h3>
<p><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_IMMUTABLE_DESC'); ?></p>
<form action="index.php" method="get" class="row g-3 mb-4">
<input type="hidden" name="option" value="com_joomleague"><input type="hidden" name="view" value="sportvariants"><input type="hidden" name="id" value="<?php echo $id; ?>">
<div class="col-md-8"><label class="form-label" for="variant-base"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_BASE'); ?></label><select class="form-select" id="variant-base" name="base_id"><?php foreach ($this->bases as $base): ?><option value="<?php echo (int) $base['id']; ?>"<?php echo (int) $base['id'] === $this->baseId ? ' selected' : ''; ?>><?php echo $safe(Text::_($base['name_key']) . ' ' . $base['profile_version']); ?></option><?php endforeach; ?></select></div><div class="col-md-4 align-self-end"><button class="btn btn-secondary"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_LOAD_BASE'); ?></button></div></form>
<form action="<?php echo $url; ?>" method="post">
<input type="hidden" name="jform[variant_id]" value="<?php echo $id; ?>"><input type="hidden" name="jform[base_id]" value="<?php echo $this->baseId; ?>">
<div class="mb-3"><label class="form-label" for="variant-revision"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_REVISION'); ?></label><input class="form-control" id="variant-revision" name="jform[revision]" required maxlength="50" pattern="[0-9]+\.[0-9]+\.[0-9]+" value="<?php echo $safe($this->draft['revision'] ?? ($this->revisions === [] ? '1.0.0' : '')); ?>"></div>
<?php foreach ($this->fields as $key => $field): $definition = $field['definition']; $submitted = $this->draft['rules'][$key] ?? []; $value = $submitted['value'] ?? $field['default']; ?>
<div class="row g-3 mb-3"><div class="col-md-4"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" id="<?php echo $key; ?>-enabled" name="jform[rules][<?php echo $key; ?>][enabled]" value="1"<?php echo ($submitted['enabled'] ?? '') === '1' ? ' checked' : ''; ?>><label class="form-check-label" for="<?php echo $key; ?>-enabled"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_OVERRIDE'); ?></label></div></div>
<div class="col-md-8"><label class="form-label" for="<?php echo $key; ?>"><?php echo Text::_($field['label']); ?></label>
<?php if ($definition['type'] === 'boolean'): ?><input type="hidden" name="jform[rules][<?php echo $key; ?>][value]" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="<?php echo $key; ?>" name="jform[rules][<?php echo $key; ?>][value]" value="1"<?php echo (string) (int) $value === '1' ? ' checked' : ''; ?>></div>
<?php else: ?><input class="form-control" id="<?php echo $key; ?>" name="jform[rules][<?php echo $key; ?>][value]" type="<?php echo $definition['type'] === 'integer' ? 'number' : 'text'; ?>" value="<?php echo $safe($value); ?>"<?php echo isset($definition['minimum']) ? ' min="' . (int) $definition['minimum'] . '"' : ''; ?><?php echo isset($definition['maximum']) ? ' max="' . (int) $definition['maximum'] . '"' : ''; ?><?php echo $definition['type'] === 'integer' ? ' step="1"' : ' maxlength="255"'; ?>><?php endif; ?>
</div></div><?php endforeach; ?>
<button class="btn btn-primary" name="task" value="sportvariants.revision"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANT_SAVE_REVISION'); ?></button>
<?php echo HTMLHelper::_('form.token'); ?></form>
<?php endif; endif; ?>
