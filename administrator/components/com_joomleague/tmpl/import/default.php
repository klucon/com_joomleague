<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Import\HtmlView $this */

$style = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.9';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
<div class="com-joomleague-dashboard com-joomleague-workflow">
	<div class="jl-section-panel mb-4">
		<span class="jl-section-panel__icon icon-upload" aria-hidden="true"></span>
		<div class="jl-section-panel__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_TITLE'); ?></p>
			<h1 class="h3 mb-2"><?php echo Text::_('COM_JOOMLEAGUE_IMPORT_TITLE'); ?></h1>
			<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_IMPORT_DESC'); ?></p>
		</div>
	</div>

	<form action="<?php echo Route::_('index.php?option=com_joomleague&task=import.csv'); ?>" method="post" enctype="multipart/form-data" class="main-card p-4">
		<div class="row g-3">
			<div class="col-md-4">
				<label class="form-label" for="target"><?php echo Text::_('COM_JOOMLEAGUE_IMPORT_TARGET'); ?></label>
				<select class="form-select" name="target" id="target">
					<?php foreach ($this->targets as $key => $target) : ?>
						<option value="<?php echo $this->escape($key); ?>"><?php echo Text::_($target['label']); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-4">
				<label class="form-label" for="csv_file"><?php echo Text::_('COM_JOOMLEAGUE_IMPORT_FILE'); ?></label>
				<input class="form-control" type="file" name="csv_file" id="csv_file" accept=".csv,text/csv,text/plain" required>
			</div>
			<div class="col-md-2">
				<label class="form-label" for="delimiter"><?php echo Text::_('COM_JOOMLEAGUE_IMPORT_DELIMITER'); ?></label>
				<input class="form-control" type="text" name="delimiter" id="delimiter" value=";" maxlength="1">
			</div>
			<div class="col-md-2 d-flex align-items-end">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" value="1" name="replace" id="replace">
					<label class="form-check-label" for="replace"><?php echo Text::_('COM_JOOMLEAGUE_IMPORT_REPLACE'); ?></label>
				</div>
			</div>
		</div>
		<div class="mt-4">
			<button type="submit" class="btn btn-primary"><?php echo Text::_('COM_JOOMLEAGUE_IMPORT_START'); ?></button>
		</div>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>

	<div class="mt-4">
		<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_IMPORT_COLUMNS_TITLE'); ?></h2>
		<div class="accordion" id="jl-import-columns">
			<?php foreach ($this->targets as $key => $target) : ?>
				<div class="accordion-item">
					<h3 class="accordion-header">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#jl-import-<?php echo $this->escape($key); ?>">
							<?php echo Text::_($target['label']); ?>
						</button>
					</h3>
					<div id="jl-import-<?php echo $this->escape($key); ?>" class="accordion-collapse collapse" data-bs-parent="#jl-import-columns">
						<div class="accordion-body"><code><?php echo $this->escape(implode(', ', $this->columns[$key] ?? [])); ?></code></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
