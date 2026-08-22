<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Stageentries;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public object $stage;
	public array $entries = [];
	public bool $canEdit = false;

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$stageId = $app->getInput()->getInt('stage_id');
		if ($stageId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_STAGE_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		$model = $this->getModel();
		$this->stage = $model->getStage($stageId);
		$this->entries = $model->getEntries($stageId);
		$this->canEdit = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_joomleague.project.' . (int) $this->stage->project_id);

		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_STAGE_ENTRIES_TITLE', $this->stage->name), 'users');
		if ($this->canEdit) ToolbarHelper::save('stageentries.save');
		ToolbarHelper::link('index.php?option=com_joomleague&view=stages&project_id=' . (int) $this->stage->project_id, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
