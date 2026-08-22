<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

?>
<form action="index.php?option=com_joomleague&view=sportprofiles" method="post" name="adminForm" id="adminForm"><div class="container-fluid">
	<div class="alert alert-info"><strong><?php echo Text::_('COM_JOOMLEAGUE_READ_ONLY_REVIEW'); ?></strong> <?php echo Text::_('COM_JOOMLEAGUE_SPORTPROFILES_INTRO'); ?></div>
	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROFILE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_CODE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SCHEMA_VERSION'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_VERSION'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROJECT_RULE_FIELDS'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SOURCE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_CHECKSUM'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th><?php echo Text::_('JGRID_HEADING_ID'); ?></th></tr></thead>
			<tbody>
			<?php foreach ($this->items as $item) : ?>
				<tr><td><strong><?php echo Text::_($item->name_key); ?></strong><div class="small text-body-secondary"><?php echo Text::_($item->description_key); ?></div></td><td><code class="text-break"><?php echo htmlspecialchars($item->code, ENT_QUOTES, 'UTF-8'); ?></code></td><td><span class="badge text-bg-warning"><?php echo htmlspecialchars((string) $item->schema_version, ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars((string) $item->profile_version, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $item->project_rule_field_count; ?></td><td><?php echo htmlspecialchars((string) $item->source, ENT_QUOTES, 'UTF-8'); ?></td><td><code class="text-break" title="<?php echo htmlspecialchars((string) $item->payload_checksum, ENT_QUOTES, 'UTF-8'); ?>"><?php echo substr((string) $item->payload_checksum, 0, 12); ?>&hellip;</code></td><td><span class="badge text-bg-<?php echo $item->state === 'active' ? 'success' : 'secondary'; ?>"><?php echo Text::_($item->state === 'active' ? 'COM_JOOMLEAGUE_STATE_ACTIVE' : 'COM_JOOMLEAGUE_STATE_SUPERSEDED'); ?></span></td><td><?php echo (int) $item->id; ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div><input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token'); ?></form>
