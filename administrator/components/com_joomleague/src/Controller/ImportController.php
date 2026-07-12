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

final class ImportController extends BaseController
{
	public function csv(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.create', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$file = $this->input->files->get('csv_file', [], 'array');
		$tmp = (string) ($file['tmp_name'] ?? '');

		if ($tmp === '' || !is_uploaded_file($tmp)) {
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=import', false), Text::_('COM_JOOMLEAGUE_IMPORT_ERROR_FILE'), 'error');

			return;
		}

		$result = $this->getModel('Import')->importCsv(
			$this->input->post->getCmd('target'),
			$tmp,
			$this->input->post->getString('delimiter', ';'),
			(bool) $this->input->post->getInt('replace', 0)
		);

		$message = Text::sprintf('COM_JOOMLEAGUE_IMPORT_RESULT', $result->inserted, $result->updated, $result->skipped);
		$type = $result->errors === [] ? 'message' : 'warning';

		if ($result->errors !== []) {
			$message .= ' ' . Text::sprintf('COM_JOOMLEAGUE_IMPORT_ERRORS', count($result->errors));
		}

		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=import', false), $message, $type);
	}
}
