<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projectentry;

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
	public $project;
	public $state;

	public function display($tpl = null): void
	{
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->project = $this->get('Project');
		$this->state = $this->get('State');

		if ($errors = $this->get('Errors')) {
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	private function addToolbar(): void
	{
		$isNew = (int) $this->item->id === 0;
		$user = Factory::getApplication()->getIdentity();
		ToolbarHelper::title(Text::sprintf($isNew ? 'COM_JOOMLEAGUE_PROJECTENTRY_NEW' : 'COM_JOOMLEAGUE_PROJECTENTRY_EDIT', $this->project->name), 'users');

		$asset = 'com_joomleague.project.' . (int) $this->project->id;
		if ($user->authorise($isNew ? 'core.create' : 'core.edit', $asset)) {
			ToolbarHelper::apply('projectentry.apply');
			ToolbarHelper::save('projectentry.save');
			if ($user->authorise('core.create', $asset)) {
				ToolbarHelper::save2new('projectentry.save2new');
			}
		}

		ToolbarHelper::cancel('projectentry.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
	}
}
