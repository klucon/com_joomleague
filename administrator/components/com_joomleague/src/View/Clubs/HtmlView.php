<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Clubs;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

final class HtmlView extends BaseHtmlView
{
	public array $activeFilters = [];
	public Form $filterForm;
	public array $items = [];
	public Pagination $pagination;
	public Registry $state;

	public function display($tpl = null): void
	{
		$model = $this->getModel(); $model->setUseExceptions(true);
		$this->items = $model->getItems(); $this->pagination = $model->getPagination(); $this->state = $model->getState();
		$this->filterForm = $model->getFilterForm(); $this->activeFilters = $model->getActiveFilters();
		$this->filterForm->addControlField('task')->addControlField('boxchecked', '0');
		if ($errors = $model->getErrors()) { throw new GenericDataException(implode("\n", $errors), 500); }
		$this->addToolbar(); parent::display($tpl);
	}

	private function addToolbar(): void
	{
		$user = $this->getCurrentUser(); ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_CLUBS_TITLE'), 'home');
		if ($user->authorise('core.create', 'com_joomleague')) { ToolbarHelper::addNew('club.add'); }
		if ($user->authorise('core.edit', 'com_joomleague')) { ToolbarHelper::editList('club.edit'); }
		if ($user->authorise('core.delete', 'com_joomleague')) { ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'clubs.delete'); }
		ToolbarHelper::link('index.php?option=com_fields&context=com_joomleague.club', Text::_('COM_JOOMLEAGUE_CUSTOM_FIELDS'), 'icon-list');
	}
}
