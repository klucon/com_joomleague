<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Project;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

final class HtmlView extends BaseHtmlView
{
	public $form;
	public $item;
	public $state;
	public $canDo;
	public ?string $return = null;

	public function display($tpl = null): void
	{
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->state = $this->get('State');
		$this->canDo = ContentHelper::getActions('com_joomleague', 'project', (int) $this->item->id);
		$return = Factory::getApplication()->getInput()->get('return', null, 'base64');
		$decoded = is_string($return) ? base64_decode($return, true) : false;
		if ($decoded !== false && Uri::isInternal($decoded)) {
			$this->return = $return;
		}
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$this->addToolbar();
		parent::display($tpl);
	}

	private function addToolbar(): void
	{
		$isNew = (int) $this->item->id === 0;
		$user = Factory::getApplication()->getIdentity();
		ToolbarHelper::title(Text::_($isNew ? 'COM_JOOMLEAGUE_PROJECT_NEW' : 'COM_JOOMLEAGUE_PROJECT_EDIT'), 'folder-open');
		$canEdit = $this->canDo->get('core.edit') || ($this->canDo->get('core.edit.own') && (int) $this->item->created_by === (int) $user->id);
		if ($canEdit || ($isNew && $user->authorise('core.create', 'com_joomleague'))) {
			ToolbarHelper::apply('project.apply');
			ToolbarHelper::save('project.save');
			if ($user->authorise('core.create', 'com_joomleague')) ToolbarHelper::save2new('project.save2new');
		}
		ToolbarHelper::cancel('project.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
	}
}
