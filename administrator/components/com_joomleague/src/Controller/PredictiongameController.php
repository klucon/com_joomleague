<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use RuntimeException;
use Throwable;

final class PredictiongameController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_PREDICTIONGAME';
	protected $view_list = 'predictiongames';

	public function recalculate(): void
	{
		try {
			if (!Session::checkToken('get')) {
				throw new RuntimeException(Text::_('JINVALID_TOKEN'));
			}

			if (!$this->app->getIdentity()->authorise('core.edit', 'com_joomleague')) {
				throw new RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
			}

			$id = $this->input->getInt('id');

			if ($id < 1) {
				throw new RuntimeException(Text::_('COM_JOOMLEAGUE_PREDICTIONGAME_ERROR_REQUIRED'));
			}

			$this->getModel('Predictiongame')->recalculate($id);

			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=predictiongames', false), Text::_('COM_JOOMLEAGUE_PREDICTIONGAME_RECALCULATED'));
		} catch (Throwable $exception) {
			$message = $exception->getMessage();
			$this->setRedirect(
				Route::_('index.php?option=com_joomleague&view=predictiongames', false),
				str_starts_with($message, 'COM_') || str_starts_with($message, 'J') ? Text::_($message) : $message,
				'warning'
			);
		}
	}
}
