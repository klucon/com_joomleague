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
use Joomleague\Component\Joomleague\Administrator\Helper\LanguageStatusHelper;

final class LanguagesController extends BaseController
{
	public function remove(): void
	{
		Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.manage', 'com_joomleague')) {
			$this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=languages', false));

			return;
		}

		$tag = $this->input->getCmd('tag', '');

		if ($tag === 'en-GB') {
			$this->setMessage(Text::_('COM_JOOMLEAGUE_LANGUAGES_REMOVE_SOURCE_DENIED'), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=languages', false));

			return;
		}

		if (!in_array($tag, LanguageStatusHelper::getAvailableLanguageTags(), true)) {
			$this->setMessage(Text::_('COM_JOOMLEAGUE_LANGUAGES_REMOVE_INVALID'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=languages', false));

			return;
		}

		$model = $this->getModel('Languages', 'Administrator', ['ignore_request' => true]);
		$removed = $model->removeLanguage($tag);

		if ($removed > 0) {
			$this->setMessage(Text::sprintf('COM_JOOMLEAGUE_LANGUAGES_REMOVE_SUCCESS', $tag, $removed));
		} else {
			$this->setMessage(Text::sprintf('COM_JOOMLEAGUE_LANGUAGES_REMOVE_NONE', $tag), 'warning');
		}

		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=languages', false));
	}

	public function download(): void
	{
		Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.manage', 'com_joomleague')) {
			$this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=languages', false));

			return;
		}

		$tag = $this->input->getCmd('tag', '');

		if ($tag === 'en-GB') {
			$this->setMessage(Text::_('COM_JOOMLEAGUE_LANGUAGES_DOWNLOAD_SOURCE_DENIED'), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=languages', false));

			return;
		}

		if (!in_array($tag, LanguageStatusHelper::getAvailableLanguageTags(), true)) {
			$this->setMessage(Text::_('COM_JOOMLEAGUE_LANGUAGES_DOWNLOAD_INVALID'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=languages', false));

			return;
		}

		$model = $this->getModel('Languages', 'Administrator', ['ignore_request' => true]);
		$result = $model->downloadLanguage($tag);
		$written = (int) ($result['written'] ?? 0);
		$failed = (int) ($result['failed'] ?? 0);

		if ($written > 0 && $failed === 0) {
			$this->setMessage(Text::sprintf('COM_JOOMLEAGUE_LANGUAGES_DOWNLOAD_SUCCESS', $tag, $written));
		} elseif ($written > 0) {
			$this->setMessage(Text::sprintf('COM_JOOMLEAGUE_LANGUAGES_DOWNLOAD_PARTIAL', $tag, $written, $failed), 'warning');
		} else {
			$this->setMessage(Text::sprintf('COM_JOOMLEAGUE_LANGUAGES_DOWNLOAD_FAILED', $tag), 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=languages', false));
	}
}
