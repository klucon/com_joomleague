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
use Throwable;

final class MatchdataController extends BaseController
{
	private const ALLOWED_SECTIONS = ['events', 'players', 'statistics', 'referees', 'staff'];

	public function save(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$matchId = $this->input->getInt('match_id', 0);
		$section = $this->input->getCmd('section', 'events');

		if (!in_array($section, self::ALLOWED_SECTIONS, true)) {
			$section = 'events';
		}

		$redirect = Route::_(
			'index.php?option=com_joomleague&view=matchdata&match_id=' . $matchId . '&section=' . $section,
			false
		);

		try {
			if ($matchId < 1) {
				throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_MATCH_NOT_FOUND'));
			}

			$user = $this->app->getIdentity();

			if (
				!$user->authorise('core.edit', 'com_joomleague.match.' . $matchId)
				&& !$user->authorise('core.edit', 'com_joomleague')
				&& !$user->authorise('core.admin', 'com_joomleague')
			) {
				throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
			}

			$this->getModel('Matchdata')->replace(
				$matchId,
				$section,
				$this->input->get('rows', [], 'array')
			);

			$this->setRedirect($redirect, Text::_('COM_JOOMLEAGUE_MATCH_DATA_SAVED'));
		} catch (Throwable $exception) {
			$message = $exception->getMessage();
			$this->setRedirect($redirect, str_starts_with($message, 'COM_') ? Text::_($message) : $message, 'error');
		}
	}
}
