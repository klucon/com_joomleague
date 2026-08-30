<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomleague\Component\Joomleague\Administrator\Service\ScheduleCsvExporter;

final class ProjectscheduleController extends BaseController
{
	public function exportCsv(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$projectId = $this->input->getInt('project_id', 0);
		if ($projectId < 1) {
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$this->setRedirect('index.php?option=com_joomleague&view=projects');
			return;
		}

		if (!$this->app->getIdentity()->authorise('joomleague.project.edit.schedule', 'com_joomleague.project.' . $projectId)) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/** @var \Joomleague\Component\Joomleague\Administrator\Model\ProjectscheduleModel $model */
		$model = $this->getModel('Projectschedule');
		$items = $model->getAllFilteredItems();
		$csv = (new ScheduleCsvExporter())->export($items);

		$filename = 'joomleague_schedule_' . $projectId . '_' . gmdate('Ymd-His') . '.csv';
		$this->app->setHeader('Content-Type', 'text/csv; charset=utf-8', true)
			->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true)
			->setHeader('Content-Length', (string) strlen($csv), true)
			->setHeader('Cache-Control', 'must-revalidate', true)
			->sendHeaders();
		echo $csv;
		$this->app->close();
	}
}
