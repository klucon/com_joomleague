<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

final class DatabasetoolsController extends BaseController
{
	public function export(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		if (!$this->app->getIdentity()->authorise('core.manage', 'com_joomleague')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		$tables = array_values(array_filter((array) $this->input->post->get('tables', [], 'array'), 'is_string'));
		if ($this->input->post->getBool('export_all')) $tables = array_column($this->getModel('Databasetools')->getTables(), 'name');
		$sql = $this->getModel('Databasetools')->export($tables);
		$filename = 'com_joomleague_tables_' . gmdate('Ymd-His') . '.sql';
		$this->app->setHeader('Content-Type', 'application/sql; charset=utf-8', true)
			->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true)
			->setHeader('Content-Length', (string) strlen($sql), true)
			->setHeader('Cache-Control', 'must-revalidate', true)
			->sendHeaders();
		echo $sql;
		$this->app->close();
	}

	public function rebuildProjectAssets(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		if (!$this->app->getIdentity()->authorise('core.admin', 'com_joomleague')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		$report = $this->getModel('Databasetools')->rebuildProjectAssets();
		$this->app->enqueueMessage(Text::sprintf('COM_JOOMLEAGUE_DATABASETOOLS_ASSETS_REBUILT', $report['orphans_removed'], $report['projects_linked']));
		$this->setRedirect('index.php?option=com_joomleague&view=databasetools');
	}
}
