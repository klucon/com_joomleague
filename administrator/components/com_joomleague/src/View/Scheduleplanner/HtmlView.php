<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Scheduleplanner;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class HtmlView extends BaseHtmlView
{
	public int $stageId = 0;
	public array $options = [];
	public array $templates = [];
	public ?array $preview = null;
	public ?string $previewError = null;

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$this->stageId = $app->getInput()->getInt('stage_id', 0);
		if ($this->stageId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_STAGE_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		$this->templates = $this->getModel()->templates();
		$this->options = (array) $app->getUserState('com_joomleague.scheduleplanner.' . $this->stageId, []);
		if ($this->options === []) {
			$this->options = $this->getModel()->defaults($this->stageId);
		}

		if ($app->getInput()->getBool('preview')) {
			try {
				$this->preview = $this->getModel()->preview($this->stageId, $this->options);
			} catch (\Throwable $error) {
				$this->previewError = $error->getMessage();
			}
		}

		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_SCHEDULE_TITLE'), 'calendar');
		ToolbarHelper::custom('scheduleplanner.preview', 'eye', 'eye', 'COM_JOOMLEAGUE_SCHEDULE_PREVIEW', false);
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromStage($this->stageId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if ($this->preview !== null && $app->getIdentity()->authorise('joomleague.project.edit.schedule', $asset)) {
			ToolbarHelper::save('scheduleplanner.apply', 'COM_JOOMLEAGUE_SCHEDULE_APPLY');
		}
		ToolbarHelper::link('index.php?option=com_joomleague&view=rounds&stage_id=' . $this->stageId, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
