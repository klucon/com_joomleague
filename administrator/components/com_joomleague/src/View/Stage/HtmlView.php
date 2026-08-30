<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Stage;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public $form;
	public $item;
	public object $project;

	public function display($tpl = null): void
	{
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->project = $this->getModel()->getProject();
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);

		$isNew = (int) $this->item->id === 0;
		ToolbarHelper::title(Text::_($isNew ? 'COM_JOOMLEAGUE_STAGE_NEW_TITLE' : 'COM_JOOMLEAGUE_STAGE_EDIT_TITLE'), 'tree');
		$asset = 'com_joomleague.project.' . (int) $this->project->id;
		if (Factory::getApplication()->getIdentity()->authorise('joomleague.project.edit.schedule', $asset)) {
			ToolbarHelper::apply('stage.apply'); ToolbarHelper::save('stage.save'); ToolbarHelper::save2new('stage.save2new');
		}
		ToolbarHelper::cancel('stage.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
		parent::display($tpl);
	}
}
