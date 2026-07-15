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
use Joomla\CMS\Router\Route;
use RuntimeException;
use Throwable;

final class RoundController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_ROUND';
	protected $view_list = 'rounds';

	/**
	 * Přesune kolo na nový termín (a posune i zápasy kola). Volá se ze seznamu kol.
	 */
	public function move(): void
	{
		$this->checkToken();

		$id = $this->input->getInt('move_id', 0);
		$dates = $this->input->get('move_date', [], 'array');
		$newDate = isset($dates[$id]) ? trim((string) $dates[$id]) : '';

		try {
			if (!$this->app->getIdentity()->authorise('core.edit', 'com_joomleague')) {
				throw new RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
			}

			if ($id < 1 || $newDate === '') {
				throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ROUND_MOVE_INVALID_DATE'));
			}

			$projectId = $this->getModel('Round')->moveDate($id, $newDate);

			$this->setRedirect(
				Route::_('index.php?option=com_joomleague&view=rounds&project_id=' . $projectId, false),
				Text::_('COM_JOOMLEAGUE_ROUND_MOVE_SUCCESS')
			);
		} catch (Throwable $exception) {
			$message = $exception->getMessage();
			$this->setRedirect(
				Route::_('index.php?option=com_joomleague&view=rounds', false),
				str_starts_with($message, 'COM_') || str_starts_with($message, 'JLIB_') ? Text::_($message) : $message,
				'error'
			);
		}
	}
}
