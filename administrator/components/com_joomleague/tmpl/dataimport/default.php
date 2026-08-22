<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
?>
<form action="index.php?option=com_joomleague&task=dataimport.import" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<div class="alert alert-warning"><strong><?php echo Text::_('COM_JOOMLEAGUE_DATAIMPORT_INFO_TITLE'); ?></strong> <?php echo Text::_('COM_JOOMLEAGUE_DATAIMPORT_INFO_DESC'); ?></div>
		<div class="control-group"><div class="control-label"><label for="sql_file"><?php echo Text::_('COM_JOOMLEAGUE_DATAIMPORT_FILE_LABEL'); ?></label></div><div class="controls"><input class="form-control" type="file" id="sql_file" name="sql_file" accept=".sql,application/sql,text/plain" required><div class="form-text"><?php echo Text::sprintf('COM_JOOMLEAGUE_DATAIMPORT_FILE_DESC', $this->maxUploadSize); ?></div></div></div>
	</div>
	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
