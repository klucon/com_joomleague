<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('com_joomleague.admin-lists');

HTMLHelper::_('behavior.multiselect');
$canEdit = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_joomleague');
$categoryNames = [];
foreach ($this->categories as $category) $categoryNames[(int) $category->id] = $category->path;
$selectedCategory = Factory::getApplication()->getInput()->getInt('filter_category');
$seen = [];

?>
<form action="index.php?option=com_joomleague&view=sportprofiles" method="post" name="adminForm" id="adminForm"><div class="container-fluid">
	<div class="alert alert-info"><strong><?php echo Text::_('COM_JOOMLEAGUE_READ_ONLY_REVIEW'); ?></strong> <?php echo Text::_('COM_JOOMLEAGUE_SPORTPROFILES_INTRO'); ?></div>
	<div class="d-flex gap-2 mb-3"><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=sportcategories'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORIES_TITLE'); ?></a><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=sportvariants'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SPORTVARIANTS_TITLE'); ?></a></div>
	<div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label" for="filter-category"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_FILTER'); ?></label><select class="form-select" name="filter_category" id="filter-category"><option value="0"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_ALL'); ?></option><?php foreach ($this->categories as $category): ?><option value="<?php echo (int) $category->id; ?>"<?php echo $selectedCategory === (int) $category->id ? ' selected' : ''; ?>><?php echo $this->escape($category->path); ?></option><?php endforeach; ?></select></div><div class="col-md-6 align-self-end"><button class="btn btn-secondary" type="submit"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_FILTER_APPLY'); ?></button></div></div>
	<?php if ($canEdit): ?><fieldset class="mb-3"><legend class="h6"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_ASSIGN_TITLE'); ?></legend><div class="form-text mb-2"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_ASSIGN_DESC'); ?></div><div class="d-flex gap-3 flex-wrap"><?php foreach ($this->categories as $category): ?><label class="form-check"><input class="form-check-input" type="checkbox" name="category_ids[]" value="<?php echo (int) $category->id; ?>"> <span class="form-check-label"><?php echo $this->escape($category->path); ?></span></label><?php endforeach; ?></div><button class="btn btn-primary mt-2" type="button" onclick="Joomla.submitbutton('sportprofiles.categories')"><?php echo Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_ASSIGN_ACTION'); ?></button></fieldset><?php endif; ?>
	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROFILE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_CODE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SCHEMA_VERSION'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROFILE_VERSION_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROJECT_RULE_FIELDS'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SOURCE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_CHECKSUM'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th><?php echo Text::_('JGRID_HEADING_ID'); ?></th></tr></thead>
			<tbody>
			<?php foreach ($this->items as $item) :
				$assigned = $this->assignments[(int) $item->id] ?? [];
				if ($selectedCategory > 0 && !in_array($selectedCategory, $assigned, true)) continue;
				$isFirst = !isset($seen[(int) $item->id]);
				$seen[(int) $item->id] = true;
			?>
				<tr><td><?php if ($canEdit && $isFirst): ?><input class="form-check-input me-2" type="checkbox" name="cid[]" value="<?php echo (int) $item->id; ?>" aria-label="<?php echo $this->escape(Text::_($item->name_key)); ?>"><?php endif; ?><strong><?php echo Text::_($item->name_key); ?></strong><div class="small text-body-secondary"><?php echo Text::_($item->description_key); ?></div><div class="small"><?php echo $this->escape(implode(', ', array_map(static fn (int $id): string => $categoryNames[$id] ?? '', $assigned))); ?></div></td><td><code class="text-break"><?php echo htmlspecialchars($item->code, ENT_QUOTES, 'UTF-8'); ?></code></td><td><span class="badge text-bg-warning"><?php echo htmlspecialchars((string) $item->schema_version, ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars((string) $item->profile_version, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $item->project_rule_field_count; ?></td><td><?php echo htmlspecialchars((string) $item->source, ENT_QUOTES, 'UTF-8'); ?></td><td><code class="text-break" title="<?php echo htmlspecialchars((string) $item->payload_checksum, ENT_QUOTES, 'UTF-8'); ?>"><?php echo substr((string) $item->payload_checksum, 0, 12); ?>&hellip;</code></td><td><span class="badge text-bg-<?php echo $item->state === 'active' ? 'success' : 'secondary'; ?>"><?php echo Text::_($item->state === 'active' ? 'COM_JOOMLEAGUE_STATE_ACTIVE' : 'COM_JOOMLEAGUE_STATE_SUPERSEDED'); ?></span></td><td><?php echo (int) $item->id; ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div><input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token'); ?></form>
