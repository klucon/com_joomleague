<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Common;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

abstract class AdminFormView extends HtmlView
{
	public Form $form;
	public object $item;
	public array $entity = [];

	abstract protected function configure(): array;

	public function display($tpl = null): void
	{
		$this->entity = $this->configure();
		$model = $this->getModel();
		$model->setUseExceptions(true);
		$this->form = $model->getForm();
		$this->item = $model->getItem();
		$this->form->addControlField('task');

		if ($errors = $model->getErrors()) { throw new GenericDataException(implode("\n", $errors), 500); }

		$this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/entityform');
		// Vlastní šablona pro konkrétní entitu (tmpl/<singular>/edit.php) má přednost
		// před generickou entityform, pokud existuje.
		$this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/' . $this->entity['singular']);
		$this->addToolbar();
		parent::display($tpl);
	}

	private function addToolbar(): void
	{
		$isNew = (int) $this->item->id === 0;
		$user = $this->getCurrentUser();
		ToolbarHelper::title(Text::_($isNew ? $this->entity['new'] : $this->entity['edit']), $this->entity['icon']);
		if ($user->authorise($isNew ? 'core.create' : 'core.edit', 'com_joomleague')) {
			ToolbarHelper::apply($this->entity['singular'] . '.apply');
			ToolbarHelper::save($this->entity['singular'] . '.save');
		}
		if ($user->authorise('core.admin', 'com_joomleague') || $user->authorise('core.options', 'com_joomleague')) {
			ToolbarHelper::preferences('com_joomleague');
		}
		ToolbarHelper::cancel($this->entity['singular'] . '.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
	}
}
