<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projects;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public $pagination;
	public $state;
	public $filterForm;
	public array $activeFilters = [];

	public function display($tpl = null): void
	{
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		if ($errors = $this->get('Errors')) {
			throw new GenericDataException(implode("\n", $errors), 500);
		}
		$this->addToolbar();
		parent::display($tpl);
	}

	private function addToolbar(): void
	{
		$user = Factory::getApplication()->getIdentity();
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_PROJECTS_TITLE'), 'folder-open');
		if ($user->authorise('core.create', 'com_joomleague')) ToolbarHelper::addNew('project.add');
		if ($this->items !== [] && $user->authorise('core.edit', 'com_joomleague')) ToolbarHelper::editList('project.edit');
		if ($this->items !== [] && $user->authorise('core.create', 'com_joomleague')) ToolbarHelper::custom('projects.duplicate', 'copy', 'copy', 'COM_JOOMLEAGUE_PROJECTS_DUPLICATE', true);
		if ($this->items !== [] && $user->authorise('core.edit.state', 'com_joomleague')) {
			ToolbarHelper::publish('projects.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('projects.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			ToolbarHelper::checkin('projects.checkin');
		}
		if ($this->items !== [] && $user->authorise('core.delete', 'com_joomleague')) ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'projects.delete');
	}
}
