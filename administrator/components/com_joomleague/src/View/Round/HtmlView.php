<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Round;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public $form; public $item; public object $stage;
	public function display($tpl = null): void
	{
		$this->form = $this->get('Form'); $this->item = $this->get('Item'); $this->stage = $this->getModel()->getStage(); if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$isNew = (int) $this->item->id === 0; ToolbarHelper::title(Text::_($isNew ? 'COM_JOOMLEAGUE_ROUND_NEW_TITLE' : 'COM_JOOMLEAGUE_ROUND_EDIT_TITLE'), 'list');
		$asset = 'com_joomleague.project.' . (int) $this->stage->project_id;
		if (Factory::getApplication()->getIdentity()->authorise('joomleague.project.edit.schedule', $asset)) { ToolbarHelper::apply('round.apply'); ToolbarHelper::save('round.save'); ToolbarHelper::save2new('round.save2new'); }
		ToolbarHelper::cancel('round.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE'); parent::display($tpl);
	}
}
