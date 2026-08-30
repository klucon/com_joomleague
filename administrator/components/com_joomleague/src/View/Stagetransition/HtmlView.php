<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Stagetransition;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public $form; public $item;
	public function display($tpl=null):void { $this->form=$this->get('Form');$this->item=$this->get('Item');if($errors=$this->get('Errors'))throw new GenericDataException(implode("\n",$errors),500);$new=(int)$this->item->id===0;ToolbarHelper::title(Text::_($new?'COM_JOOMLEAGUE_STAGE_TRANSITION_NEW':'COM_JOOMLEAGUE_STAGE_TRANSITION_EDIT'),'shuffle');$user=Factory::getApplication()->getIdentity();$projectId=(int)($this->item->project_id??Factory::getApplication()->getInput()->getInt('project_id'));$asset=$projectId>0?'com_joomleague.project.'.$projectId:'com_joomleague';if($user->authorise('joomleague.project.run.transitions',$asset)){ToolbarHelper::apply('stagetransition.apply');ToolbarHelper::save('stagetransition.save');ToolbarHelper::save2new('stagetransition.save2new');}ToolbarHelper::cancel('stagetransition.cancel',$new?'JTOOLBAR_CANCEL':'JTOOLBAR_CLOSE');parent::display($tpl); }
}
