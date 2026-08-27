<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

?>
<div class="container-fluid">
	<div class="alert alert-info">
		<strong><?php echo $this->escape($this->project->name); ?></strong>
		<?php if ($this->stage !== null) : ?>
			<span class="mx-2">/</span><strong><?php echo $this->escape($this->stage->name); ?></strong>
		<?php endif; ?>
		<div><?php echo Text::_('COM_JOOMLEAGUE_STANDINGS_DESC'); ?></div>
	</div>
	<?php echo HTMLHelper::_('uitab.startTabSet', 'standingsScopes', ['active' => $this->scope, 'recall' => true, 'breakpoint' => 768]); ?>
	<?php foreach ($this->availableScopes as $scope) : ?>
		<?php $current = $this->standingsByScope[$scope] ?? ['snapshot' => null, 'rows' => []]; ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'standingsScopes', $scope, Text::_('COM_JOOMLEAGUE_STANDINGS_SCOPE_' . strtoupper($scope))); ?>
		<?php if ($current['snapshot'] === null) : ?>
			<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_STANDINGS_EMPTY'); ?></div>
		<?php else : ?>
			<p class="text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_STANDINGS_GENERATED', HTMLHelper::_('date', $current['snapshot']->generated_at, Text::_('DATE_FORMAT_LC6'))); ?></p>
			<div class="table-responsive">
				<table class="table table-striped align-middle">
					<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_RANK_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ENTRY_LABEL'); ?></th><?php foreach ($this->metrics as $metric) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_STANDING_METRIC_' . strtoupper($metric['code'])); ?></th><?php endforeach; ?></tr></thead>
					<tbody><?php foreach ($current['rows'] as $row) : ?><tr><td><?php echo (int) $row->rank_number; ?></td><th scope="row"><?php echo $this->escape($row->entry_name_snapshot); ?></th><?php foreach ($this->metrics as $metric) : $value = $row->metrics[$metric['code']] ?? null; ?><td><?php echo $value === null ? Text::_('JNONE') : $this->escape((string) $value); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php endforeach; ?>
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</div>
