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
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Updates\HtmlView $this */

$style = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.9';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
<div class="com-joomleague-dashboard com-joomleague-workflow">
	<div class="jl-section-panel mb-4">
		<span class="jl-section-panel__icon icon-refresh" aria-hidden="true"></span>
		<div class="jl-section-panel__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_TITLE'); ?></p>
			<h1 class="h3 mb-2"><?php echo Text::_('COM_JOOMLEAGUE_UPDATES_TITLE'); ?></h1>
			<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_UPDATES_DESC'); ?></p>
		</div>
	</div>
	<table class="table">
		<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_UPDATES_FILE'); ?></th><th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_DBTOOLS_SIZE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_UPDATES_MODIFIED'); ?></th></tr></thead>
		<tbody>
			<?php foreach ($this->updates as $update) : ?>
				<tr><th scope="row"><?php echo $this->escape($update->name); ?></th><td class="text-end"><?php echo HTMLHelper::_('number.bytes', (int) $update->size); ?></td><td><?php echo $this->escape($update->modified); ?></td></tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
