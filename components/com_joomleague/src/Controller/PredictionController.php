<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use RuntimeException;
use Throwable;

final class PredictionController extends BaseController
{
	public function save(): void
	{
		$gameId = $this->input->getInt('game_id');
		$return = Route::_('index.php?option=com_joomleague&view=prediction&game_id=' . $gameId, false);

		try {
			if (!Session::checkToken()) {
				throw new RuntimeException(Text::_('JINVALID_TOKEN'));
			}

			$user = $this->app->getIdentity();

			if ((int) $user->id < 1) {
				throw new RuntimeException(Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_LOGIN_REQUIRED'));
			}

			$model = $this->getModel('Prediction');
			$saved = $model->savePredictionTips($gameId, (int) $user->id, $this->input->get('tips', [], 'array'));

			$this->setRedirect($return, Text::sprintf('COM_JOOMLEAGUE_SITE_PREDICTION_SAVED', $saved));
		} catch (Throwable $exception) {
			$message = $exception->getMessage();
			$this->setRedirect($return, str_starts_with($message, 'COM_') || str_starts_with($message, 'J') ? Text::_($message) : $message, 'warning');
		}
	}
}
