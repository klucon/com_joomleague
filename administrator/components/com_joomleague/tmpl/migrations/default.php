<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$sourceVersion = $this->sourceInventory['source_version'];

?>
<div class="container-fluid">
	<div class="alert alert-danger"><strong><?php echo Text::_('COM_JOOMLEAGUE_READ_ONLY_REVIEW'); ?></strong> <?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_INTRO'); ?></div>
	<div class="card mb-4">
		<div class="card-header"><h2 class="h5 mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_SCHEMA_INVENTORY_TITLE'); ?></h2></div>
		<div class="card-body">
			<dl class="row mb-0">
				<dt class="col-sm-3"><?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_SCHEMA_CLASSIFICATION'); ?></dt><dd class="col-sm-9"><span class="badge bg-info text-dark"><?php echo htmlspecialchars(Text::_('COM_JOOMLEAGUE_MIGRATIONS_CLASS_' . strtoupper((string) $this->sourceInventory['classification'])), ENT_QUOTES, 'UTF-8'); ?></span></dd>
				<dt class="col-sm-3"><?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_SCHEMA_CONFIDENCE'); ?></dt><dd class="col-sm-9"><?php echo htmlspecialchars(Text::_('COM_JOOMLEAGUE_MIGRATIONS_CONFIDENCE_' . strtoupper((string) $this->sourceInventory['confidence'])), ENT_QUOTES, 'UTF-8'); ?></dd>
				<dt class="col-sm-3"><?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_SCHEMA_TABLES'); ?></dt><dd class="col-sm-9"><?php echo (int) $this->sourceInventory['table_count']; ?></dd>
				<dt class="col-sm-3"><?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_SOURCE_VERSION'); ?></dt>
				<dd class="col-sm-9">
					<?php echo htmlspecialchars(
						$sourceVersion['version'] ?? Text::_('COM_JOOMLEAGUE_MIGRATIONS_VERSION_STATUS_' . strtoupper((string) $sourceVersion['status'])),
						ENT_QUOTES,
						'UTF-8'
					); ?>
				</dd>
				<dt class="col-sm-3"><?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_SOURCE_FAMILY'); ?></dt>
				<dd class="col-sm-9"><?php echo htmlspecialchars($sourceVersion['family'] ?? Text::_('JNONE'), ENT_QUOTES, 'UTF-8'); ?></dd>
				<dt class="col-sm-3"><?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_SCHEMA_EVIDENCE'); ?></dt><dd class="col-sm-9"><code class="text-break"><?php echo htmlspecialchars(implode(', ', $this->sourceInventory['evidence']), ENT_QUOTES, 'UTF-8'); ?></code></dd>
			</dl>
			<p class="text-body-secondary mt-3 mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_SCHEMA_INVENTORY_DESC'); ?></p>
		</div>
	</div>
	<?php if ($this->items === []) : ?>
		<div class="card"><div class="card-body text-center py-5"><span class="icon-refresh fs-1 text-danger" aria-hidden="true"></span><h2 class="h4 mt-3"><?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_EMPTY_TITLE'); ?></h2><p class="text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MIGRATIONS_EMPTY_DESC'); ?></p></div></div>
	<?php else : ?>
		<div class="table-responsive"><table class="table table-striped"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SOURCE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SOURCE_VERSION'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROGRESS'); ?></th><th><?php echo Text::_('JDATE'); ?></th></tr></thead><tbody>
		<?php foreach ($this->items as $item) : ?><tr><td><?php echo htmlspecialchars($item->source_product, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($item->source_version, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($item->state, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $item->processed_records; ?> / <?php echo (int) $item->total_records; ?></td><td><?php echo htmlspecialchars($item->created, ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?>
		</tbody></table></div>
	<?php endif; ?>
</div>
