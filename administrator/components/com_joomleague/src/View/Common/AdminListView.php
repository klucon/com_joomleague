<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Common;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

abstract class AdminListView extends HtmlView
{
	public array $activeFilters = [];
	public Form $filterForm;
	public array $items = [];
	public Pagination $pagination;
	public Registry $state;
	public array $entity = [];

	abstract protected function configure(): array;

	public function display($tpl = null): void
	{
		$this->entity = $this->configure();
		$model = $this->getModel();
		$model->setUseExceptions(true);
		$this->items = $model->getItems();
		$this->pagination = $model->getPagination();
		$this->state = $model->getState();
		$this->filterForm = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();
		$this->filterForm->addControlField('task')->addControlField('boxchecked', '0');

		if ($errors = $model->getErrors()) {
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		if (empty($this->entity['own_template'])) {
			$this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/entitylist');
		}
		$this->addToolbar();
		parent::display($tpl);
	}

	private function addToolbar(): void
	{
		$user = $this->getCurrentUser();
		ToolbarHelper::title(Text::_($this->entity['title']), $this->entity['icon']);
		if (($this->entity['can_create'] ?? true) && $user->authorise('core.create', 'com_joomleague')) { ToolbarHelper::addNew($this->entity['singular'] . '.add'); }
		if (($this->entity['can_edit'] ?? true) && $user->authorise('core.edit', 'com_joomleague')) { ToolbarHelper::editList($this->entity['singular'] . '.edit'); }
		if (!empty($this->entity['state']) && $user->authorise('core.edit.state', 'com_joomleague')) {
			ToolbarHelper::publish($this->entity['plural'] . '.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish($this->entity['plural'] . '.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		if (($this->entity['can_delete'] ?? true) && $user->authorise('core.delete', 'com_joomleague')) { ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', $this->entity['plural'] . '.delete'); }
		foreach ($this->entity['toolbar_links'] ?? [] as $link) { ToolbarHelper::link($link['url'], Text::_($link['label']), $link['icon'] ?? 'link'); }
		if ($user->authorise('core.admin', 'com_joomleague') || $user->authorise('core.options', 'com_joomleague')) { ToolbarHelper::preferences('com_joomleague'); }
	}
}
