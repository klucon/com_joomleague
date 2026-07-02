<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Jlxmlexports\HtmlView $this */

$style = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.6';
$token = Session::getFormToken();
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
<div class="com-joomleague-dashboard com-joomleague-workflow">
	<div class="jl-section-panel mb-4">
		<span class="jl-section-panel__icon icon-download" aria-hidden="true"></span>
		<div class="jl-section-panel__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_TITLE'); ?></p>
			<h1 class="h3 mb-2"><?php echo Text::_('COM_JOOMLEAGUE_EXPORT_TITLE'); ?></h1>
			<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_EXPORT_DESC'); ?></p>
		</div>
	</div>
	<table class="table">
		<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_MENU_PROJECTS'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_MENU_LEAGUES'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_MENU_SEASONS'); ?></th><th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_EXPORT_ACTION'); ?></th></tr></thead>
		<tbody>
			<?php foreach ($this->projects as $project) : ?>
				<tr>
					<th scope="row"><?php echo $this->escape($project->name); ?></th>
					<td><?php echo $this->escape((string) $project->league); ?></td>
					<td><?php echo $this->escape((string) $project->season); ?></td>
					<td class="text-end">
						<a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomleague&task=jlxmlexport.download&project_id=' . (int) $project->id . '&' . $token . '=1'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_EXPORT_DOWNLOAD_JSON'); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
