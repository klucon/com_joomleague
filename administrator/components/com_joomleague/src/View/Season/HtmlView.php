<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Season;

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
	public $state;

	public function display($tpl = null): void
	{
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->state = $this->get('State');
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$this->addToolbar();
		parent::display($tpl);
	}

	private function addToolbar(): void
	{
		$isNew = (int) $this->item->id === 0;
		$user = Factory::getApplication()->getIdentity();
		ToolbarHelper::title(Text::_($isNew ? 'COM_JOOMLEAGUE_SEASON_NEW' : 'COM_JOOMLEAGUE_SEASON_EDIT'), 'calendar');
		if ($user->authorise('core.edit', 'com_joomleague') || ($isNew && $user->authorise('core.create', 'com_joomleague'))) {
			ToolbarHelper::apply('season.apply');
			ToolbarHelper::save('season.save');
			if ($user->authorise('core.create', 'com_joomleague')) ToolbarHelper::save2new('season.save2new');
		}
		ToolbarHelper::cancel('season.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
	}
}
