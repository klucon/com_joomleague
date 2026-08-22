<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Venues;

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
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$this->addToolbar();
		parent::display($tpl);
	}

	private function addToolbar(): void
	{
		$user = Factory::getApplication()->getIdentity();
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_VENUES_TITLE'), 'location');
		if ($user->authorise('core.create', 'com_joomleague')) ToolbarHelper::addNew('venue.add');
		if ($user->authorise('core.edit', 'com_joomleague')) ToolbarHelper::editList('venue.edit');
		if ($user->authorise('core.edit.state', 'com_joomleague')) {
			ToolbarHelper::publish('venues.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('venues.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			ToolbarHelper::checkin('venues.checkin');
		}
		if ($user->authorise('core.delete', 'com_joomleague')) ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'venues.delete');
	}
}
