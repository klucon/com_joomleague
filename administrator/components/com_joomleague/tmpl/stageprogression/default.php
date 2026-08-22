<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$transition = $this->preview['transition'];
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=stageprogression&transition_id=' . (int) $transition->id); ?>" method="post" name="adminForm" id="adminForm">
	<div class="main-card">
		<div class="alert alert-info"><strong><?php echo $this->escape($transition->project_name); ?></strong><div><?php echo $this->escape($transition->source_name); ?> <span aria-hidden="true">&rarr;</span> <?php echo $this->escape($transition->target_name); ?></div></div>
		<?php if (!$this->preview['executable']) : ?>
			<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_PROGRESSION_MANUAL'); ?></div>
		<?php else : ?>
			<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h4 mb-0"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_PROGRESSION_RESOLVED'); ?></h2><span class="badge bg-info text-dark"><?php echo count($this->preview['entries']); ?></span></div>
			<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th class="w-1">#</th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NAME_LABEL'); ?></th><th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_PROGRESSION_SOURCE_RANK'); ?></th><th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_PROGRESSION_TARGET_SEED'); ?></th></tr></thead><tbody>
			<?php foreach ($this->preview['entries'] as $index => $entry) : $targetSeed = $transition->target_seed_start === null ? null : (int) $transition->target_seed_start + $index; ?>
				<tr><td><?php echo $index + 1; ?></td><th scope="row"><?php echo $this->escape($entry['name']); ?></th><td class="text-center"><?php echo $entry['rank'] ?? Text::_('JNONE'); ?></td><td class="text-center"><?php echo $targetSeed ?? Text::_('JNONE'); ?></td></tr>
			<?php endforeach; ?>
			<?php if ($this->preview['entries'] === []) : ?><tr><td colspan="4"><div class="alert alert-warning mb-0"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_PROGRESSION_EMPTY'); ?></div></td></tr><?php endif; ?>
			</tbody></table></div>
			<p class="small text-body-secondary mb-0"><?php echo Text::sprintf('COM_JOOMLEAGUE_STAGE_PROGRESSION_CHECKSUM', $this->escape($this->preview['checksum'])); ?></p>
			<?php if ($this->preview['last_run']) : ?><p class="small text-body-secondary mb-0"><?php echo Text::sprintf('COM_JOOMLEAGUE_STAGE_PROGRESSION_LAST_RUN', $this->escape($this->preview['last_run']->created), (int) $this->preview['last_run']->resolved_count); ?></p><?php endif; ?>
		<?php endif; ?>
	</div>
	<input type="hidden" name="task" value=""><input type="hidden" name="transition_id" value="<?php echo (int) $transition->id; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
