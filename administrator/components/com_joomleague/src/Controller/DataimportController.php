<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Utility\Utility;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;

final class DataimportController extends BaseController
{
	public function import(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		if (!$this->app->getIdentity()->authorise('joomleague.database.import', 'com_joomleague')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		$file = $this->input->files->get('sql_file', null, 'raw');
		$maxUploadSize = (int) Utility::getMaxUploadSize();
		try {
			if (is_array($file) && in_array((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
				throw new \RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_SIZE');
			}
			if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'sql') {
				throw new \RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_FILE');
			}
			if ((int) ($file['size'] ?? 0) > $maxUploadSize) throw new \RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_SIZE');
			if (!is_uploaded_file((string) $file['tmp_name'])) throw new \RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_FILE');
			$sql = file_get_contents((string) $file['tmp_name']);
			if ($sql === false) throw new \RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_FILE');
			$result = $this->getModel('Dataimport')->import($sql);
			$this->app->enqueueMessage(Text::sprintf('COM_JOOMLEAGUE_DATAIMPORT_SUCCESS', $result['executed'], $result['skipped']));
		} catch (\Throwable $error) {
			$knownErrors = ['COM_JOOMLEAGUE_DATAIMPORT_ERROR_FILE', 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_SIZE', 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT', 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_LOCK', 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_RECOVERED', 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_RECOVERY_INCOMPLETE', 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_PROFILE'];
			if (!in_array($error->getMessage(), $knownErrors, true)) {
				Log::add('SQL import failed (' . get_class($error) . ', code ' . $error->getCode() . ').', Log::ERROR, 'com_joomleague.import');
			}
			$message = $error->getMessage() === 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_SIZE'
				? Text::sprintf('COM_JOOMLEAGUE_DATAIMPORT_ERROR_SIZE', HTMLHelper::_('number.bytes', $maxUploadSize))
				: Text::_(in_array($error->getMessage(), $knownErrors, true) ? $error->getMessage() : 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_FAILED');
			$this->app->enqueueMessage($message, 'error');
		}
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=dataimport', false));
	}
}
