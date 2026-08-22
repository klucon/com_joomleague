<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projecttemplates;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public $form;
	public $project;
	public array $templateGroups = [];

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$projectId = $app->getInput()->getInt('project_id');
		if ($projectId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}

		try {
			$this->project = $this->getModel()->getProject($projectId);
			$this->form = $this->getModel()->getForm($projectId);
			$this->templateGroups = $this->getModel()->getTemplateGroups();
		} catch (\Throwable $exception) {
			throw new GenericDataException($exception->getMessage(), 500);
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	private function addToolbar(): void
	{
		$user = Factory::getApplication()->getIdentity();
		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_PROJECTTEMPLATES_TITLE', $this->project->name), 'palette');

		if ($user->authorise('core.edit', 'com_joomleague')) {
			ToolbarHelper::apply('projecttemplates.apply');
			ToolbarHelper::save('projecttemplates.save');
		}

		ToolbarHelper::cancel('projecttemplates.cancel', 'JTOOLBAR_CLOSE');
	}
}
