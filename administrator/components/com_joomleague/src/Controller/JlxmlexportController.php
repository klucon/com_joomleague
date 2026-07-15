<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class JlxmlexportController extends BaseController
{
	public function download(): void
	{
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.manage', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$projectId = $this->input->getInt('project_id', 0);

		if ($projectId < 1) {
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=jlxmlexports', false), Text::_('COM_JOOMLEAGUE_EXPORT_ERROR_PROJECT'), 'error');

			return;
		}

		$data = $this->getModel('Jlxmlexports')->buildProjectExport($projectId);
		$file = 'joomleague-project-' . $projectId . '-' . gmdate('Ymd-His') . '.json';

		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->setHeader('Content-Disposition', 'attachment; filename="' . $file . '"', true);
		$this->app->sendHeaders();
		echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$this->app->close();
	}
}
