<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projectrules;

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
	public array $ruleFields = [];

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
			$this->ruleFields = $this->getModel()->getRuleFields();
		} catch (\Throwable $exception) {
			throw new GenericDataException($exception->getMessage(), 500);
		}
		$this->addToolbar();
		parent::display($tpl);
	}

	private function addToolbar(): void
	{
		$user = Factory::getApplication()->getIdentity();
		$asset = 'com_joomleague.project.' . (int) ($this->project->id ?? 0);
		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_PROJECTRULES_TITLE', $this->project->name), 'sliders-h');
		if ($user->authorise('joomleague.project.edit.rules', $asset)) {
			ToolbarHelper::apply('projectrules.apply');
			ToolbarHelper::save('projectrules.save');
		}
		ToolbarHelper::cancel('projectrules.cancel', 'JTOOLBAR_CLOSE');
	}
}
