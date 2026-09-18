<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
$identity = Factory::getApplication()->getIdentity();
$items = [
	['databasetools', 'database', 'COM_JOOMLEAGUE_DATABASETOOLS_TITLE', 'COM_JOOMLEAGUE_DATABASETOOLS_DESC', 'joomleague.database.export'],
	['dataimport', 'upload', 'COM_JOOMLEAGUE_DATAIMPORT_TITLE', 'COM_JOOMLEAGUE_DATAIMPORT_DESC', 'joomleague.database.import'],
	['sportprofiles', 'puzzle-piece', 'COM_JOOMLEAGUE_SPORTPROFILES_TITLE', 'COM_JOOMLEAGUE_SPORTPROFILES_SYNC_DESC'],
	['templates', 'palette', 'COM_JOOMLEAGUE_TEMPLATES_TITLE', 'COM_JOOMLEAGUE_TEMPLATES_DESC'],
	['diagnostics', 'health', 'COM_JOOMLEAGUE_DIAGNOSTICS_TITLE', 'COM_JOOMLEAGUE_DIAGNOSTICS_DESC'],
];
?>
<div class="container-fluid">
	<div class="alert alert-info"><strong><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_INFO_TITLE'); ?></strong> <?php echo Text::_('COM_JOOMLEAGUE_TOOLS_INFO_DESC'); ?></div>
	<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
	<?php foreach ($items as $item) : ?>
		<?php [$view, $icon, $title, $description, $action] = array_pad($item, 5, null); ?>
		<?php if ($action !== null && !$identity->authorise($action, 'com_joomleague')) continue; ?>
		<div class="col"><div class="card h-100"><div class="card-body"><h2 class="h5"><span class="icon-<?php echo $icon; ?> me-2" aria-hidden="true"></span><?php echo Text::_($title); ?></h2><p><?php echo Text::_($description); ?></p><a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=' . $view); ?>"><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_OPEN'); ?></a></div></div></div>
	<?php endforeach; ?>
	</div>
</div>
