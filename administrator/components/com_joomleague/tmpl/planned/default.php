<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<div class="container-fluid">
	<div class="row">
		<div class="col-12">
			<div class="card border-primary">
				<div class="card-header bg-primary text-white d-flex align-items-center gap-3"><span class="icon-<?php echo $this->domain['icon']; ?> fs-3" aria-hidden="true"></span><div><h2 class="h4 mb-0"><?php echo Text::_($this->domain['title']); ?></h2><small><?php echo Text::_($this->domain['phase']); ?></small></div></div>
				<div class="card-body p-4">
					<p class="lead"><?php echo Text::_($this->domain['description']); ?></p>
					<div class="alert alert-warning mb-4"><strong><?php echo Text::_('COM_JOOMLEAGUE_PLANNED_VIEW_TITLE'); ?></strong> <?php echo Text::_('COM_JOOMLEAGUE_PLANNED_VIEW_DESC'); ?></div>
					<div class="row g-3">
						<div class="col-md-4"><div class="border rounded p-3 h-100"><h3 class="h6 text-primary"><?php echo Text::_('COM_JOOMLEAGUE_PLANNED_SCOPE_TITLE'); ?></h3><p class="small mb-0"><?php echo Text::_('COM_JOOMLEAGUE_PLANNED_SCOPE_DESC'); ?></p></div></div>
						<div class="col-md-4"><div class="border rounded p-3 h-100"><h3 class="h6 text-success"><?php echo Text::_('COM_JOOMLEAGUE_PLANNED_MIGRATION_TITLE'); ?></h3><p class="small mb-0"><?php echo Text::_('COM_JOOMLEAGUE_PLANNED_MIGRATION_DESC'); ?></p></div></div>
						<div class="col-md-4"><div class="border rounded p-3 h-100"><h3 class="h6 text-danger"><?php echo Text::_('COM_JOOMLEAGUE_PLANNED_GATE_TITLE'); ?></h3><p class="small mb-0"><?php echo Text::_('COM_JOOMLEAGUE_PLANNED_GATE_DESC'); ?></p></div></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
