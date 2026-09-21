<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$canGenerate = !$this->status['has_runtime_data'];
?>
<div class="container-fluid">
	<div class="row g-4">
		<div class="col-12 col-xl-7">
			<div class="card">
				<div class="card-header"><h2 class="h5 mb-0"><?php echo Text::_('COM_JOOMLEAGUE_DEMODATA_GENERATE_TITLE'); ?></h2></div>
				<div class="card-body">
					<p><?php echo Text::_('COM_JOOMLEAGUE_DEMODATA_GENERATE_DESC'); ?></p>
					<?php if (!$canGenerate) : ?>
						<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_DEMODATA_NOT_EMPTY'); ?></div>
					<?php endif; ?>
					<form action="<?php echo Route::_('index.php?option=com_joomleague&task=demodata.generate'); ?>" method="post">
						<fieldset<?php echo $canGenerate ? '' : ' disabled'; ?>>
							<legend class="h6"><?php echo Text::_('COM_JOOMLEAGUE_DEMODATA_PROFILES'); ?></legend>
							<div class="row row-cols-1 row-cols-md-2 g-2 mb-3">
								<?php foreach ($this->profiles as $code => $name) : ?>
									<div class="col">
										<div class="form-check">
											<input class="form-check-input" type="checkbox" name="profiles[]" value="<?php echo $this->escape($code); ?>" id="demo-profile-<?php echo $this->escape($code); ?>"<?php echo $code === 'football' ? ' checked' : ''; ?>>
							<label class="form-check-label" for="demo-profile-<?php echo $this->escape($code); ?>"><?php echo Text::_($name); ?></label>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
							<button type="button" class="btn btn-outline-secondary me-2" data-jl-select-all><?php echo Text::_('COM_JOOMLEAGUE_DEMODATA_SELECT_ALL'); ?></button>
							<button type="submit" class="btn btn-primary"><span class="icon-database me-1" aria-hidden="true"></span><?php echo Text::_('COM_JOOMLEAGUE_DEMODATA_GENERATE'); ?></button>
						</fieldset>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				</div>
			</div>
		</div>
		<div class="col-12 col-xl-5">
			<div class="card border-danger">
				<div class="card-header bg-danger text-white"><h2 class="h5 mb-0"><?php echo Text::_('COM_JOOMLEAGUE_DEMODATA_RESET_TITLE'); ?></h2></div>
				<div class="card-body">
					<p><?php echo Text::_('COM_JOOMLEAGUE_DEMODATA_RESET_DESC'); ?></p>
					<?php if (!$this->status['enabled']) : ?>
						<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_DEMODATA_RESET_DISABLED'); ?></div>
					<?php endif; ?>
					<form action="<?php echo Route::_('index.php?option=com_joomleague&task=demodata.reset'); ?>" method="post">
						<fieldset<?php echo $this->status['enabled'] ? '' : ' disabled'; ?>>
							<label class="form-label" for="demo-reset-confirmation"><?php echo Text::sprintf('COM_JOOMLEAGUE_DEMODATA_CONFIRM_LABEL', '<code>' . $this->escape($this->status['confirmation']) . '</code>'); ?></label>
							<input class="form-control mb-3" type="text" id="demo-reset-confirmation" name="confirmation" autocomplete="off" spellcheck="false" required>
							<button type="submit" class="btn btn-danger"><span class="icon-trash me-1" aria-hidden="true"></span><?php echo Text::_('COM_JOOMLEAGUE_DEMODATA_RESET'); ?></button>
						</fieldset>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
document.querySelector('[data-jl-select-all]')?.addEventListener('click', () => {
	document.querySelectorAll('input[name="profiles[]"]').forEach((input) => { input.checked = true; });
});
</script>
