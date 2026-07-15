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
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Databasetools\HtmlView $this */

$style = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.9';
$token = Session::getFormToken();
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
<div class="com-joomleague-dashboard com-joomleague-workflow">
	<div class="jl-section-panel mb-4">
		<span class="jl-section-panel__icon icon-database" aria-hidden="true"></span>
		<div class="jl-section-panel__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_TITLE'); ?></p>
			<h1 class="h3 mb-2"><?php echo Text::_('COM_JOOMLEAGUE_DBTOOLS_TITLE'); ?></h1>
			<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_DBTOOLS_DESC'); ?></p>
		</div>
		<div class="jl-workflow-round-nav">
			<a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomleague&task=databasetool.optimize&' . $token . '=1'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_DBTOOLS_OPTIMIZE'); ?></a>
			<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&task=databasetool.repair&' . $token . '=1'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_DBTOOLS_REPAIR'); ?></a>
		</div>
	</div>

	<table class="table">
		<caption class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_DBTOOLS_TABLES'); ?></caption>
		<thead>
			<tr>
				<th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NAME'); ?></th>
				<th><?php echo Text::_('COM_JOOMLEAGUE_DBTOOLS_ENGINE'); ?></th>
				<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_DBTOOLS_ROWS'); ?></th>
				<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_DBTOOLS_SIZE'); ?></th>
				<th><?php echo Text::_('COM_JOOMLEAGUE_DBTOOLS_COLLATION'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($this->tables as $table) : ?>
				<tr>
					<th scope="row"><?php echo $this->escape($table->name); ?></th>
					<td><?php echo $this->escape($table->engine); ?></td>
					<td class="text-end"><?php echo number_format((int) $table->rows, 0, ',', ' '); ?></td>
					<td class="text-end"><?php echo HTMLHelper::_('number.bytes', (int) $table->data_length + (int) $table->index_length); ?></td>
					<td><?php echo $this->escape($table->collation); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
