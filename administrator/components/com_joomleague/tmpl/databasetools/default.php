<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
?>
<form action="index.php?option=com_joomleague&view=databasetools" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<div class="alert alert-info"><strong><?php echo Text::_('COM_JOOMLEAGUE_DATABASETOOLS_INFO_TITLE'); ?></strong> <?php echo Text::_('COM_JOOMLEAGUE_DATABASETOOLS_INFO_DESC'); ?></div>
		<div class="mb-3 form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="export_all" value="1" id="export_all"><label class="form-check-label" for="export_all"><?php echo Text::_('COM_JOOMLEAGUE_DATABASETOOLS_EXPORT_ALL'); ?></label></div>
		<div class="table-responsive"><table class="table table-striped"><thead><tr><th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_DATABASETOOLS_TABLE'); ?></th><th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_DATABASETOOLS_ROWS'); ?></th></tr></thead><tbody>
		<?php foreach ($this->items as $index => $item) : ?><tr><td class="text-center"><input class="form-check-input" type="checkbox" id="cb<?php echo $index; ?>" name="tables[]" value="<?php echo $this->escape($item['name']); ?>"></td><td><code><?php echo $this->escape($item['name']); ?></code></td><td class="text-end"><?php echo $item['rows']; ?></td></tr><?php endforeach; ?>
		</tbody></table></div>
	</div>
	<input type="hidden" name="task" value="databasetools.export"><?php echo HTMLHelper::_('form.token'); ?>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var btn = document.getElementById('toolbar-refresh');
	if (!btn) return;
	btn.addEventListener('click', function (event) {
		if (!window.confirm(<?php echo json_encode(Text::_('COM_JOOMLEAGUE_DATABASETOOLS_REBUILD_ASSETS_CONFIRM')); ?>)) {
			event.stopImmediatePropagation();
			event.preventDefault();
		}
	}, true);
});
</script>
