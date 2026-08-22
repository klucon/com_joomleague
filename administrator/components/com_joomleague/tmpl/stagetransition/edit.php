<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');HTMLHelper::_('behavior.keepalive');
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id='.(int)$this->item->id); ?>" method="post" name="adminForm" id="stagetransition-form" class="form-validate"><div class="container-fluid"><div class="row"><div class="col-12"><?php echo HTMLHelper::_('uitab.startTabSet','transitionTabs',['active'=>'details','recall'=>true]); ?><?php echo HTMLHelper::_('uitab.addTab','transitionTabs','details',Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS')); ?><div class="row"><?php foreach($this->form->getFieldset('details') as $field):?><div class="<?php echo $field->fieldname === 'selector_config_json' ? 'col-12' : 'col-12 col-lg-6'; ?>"><?php echo $field->renderField(); ?></div><?php endforeach;?></div><?php echo HTMLHelper::_('uitab.endTab'); ?><?php echo HTMLHelper::_('uitab.addTab','transitionTabs','publishing',Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?><?php echo $this->form->renderFieldset('publishing'); ?><?php echo HTMLHelper::_('uitab.endTab'); ?><?php echo HTMLHelper::_('uitab.endTabSet'); ?></div></div></div><input type="hidden" name="task" value=""><input type="hidden" name="project_id" value="<?php echo (int)$this->form->getValue('project_id'); ?>"><?php echo HTMLHelper::_('form.token'); ?></form>
