<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Statistics;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public array $summary = [];
	public $pagination;
	public $state;
	public $filterForm;
	public array $activeFilters = [];

	public function display($tpl = null): void
	{
		$items = $this->get('Items');
		$this->summary = $this->get('Summary');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$this->items = is_array($items) ? $items : [];
		$user = Factory::getApplication()->getIdentity();
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_STATISTICS_TITLE'), 'chart');
		if ($user->authorise('core.create', 'com_joomleague')) ToolbarHelper::addNew('statistic.add');
		if ($this->items !== []) {
			if ($user->authorise('core.edit', 'com_joomleague')) ToolbarHelper::editList('statistic.edit');
			if ($user->authorise('core.edit.state', 'com_joomleague')) { ToolbarHelper::publish('statistics.publish', 'JTOOLBAR_PUBLISH', true); ToolbarHelper::unpublish('statistics.unpublish', 'JTOOLBAR_UNPUBLISH', true); ToolbarHelper::checkin('statistics.checkin'); }
			if ($user->authorise('core.delete', 'com_joomleague')) ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'statistics.delete');
		}
		parent::display($tpl);
	}
}
