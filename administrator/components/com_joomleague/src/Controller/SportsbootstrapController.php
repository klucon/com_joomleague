<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Administrator\Service\SportsBootstrapService;
use Throwable;

final class SportsbootstrapController extends BaseController
{
	private SportsBootstrapService $service;

	public function setSportsBootstrapService(SportsBootstrapService $service): void
	{
		$this->service = $service;
	}

	public function create(): void
	{
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		$redirect = Route::_('index.php?option=com_config&view=component&component=com_joomleague', false);

		try {
			if (!$this->app->getIdentity()->authorise('core.admin', 'com_joomleague') && !$this->app->getIdentity()->authorise('core.options', 'com_joomleague')) {
				throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
			}

			$params = ComponentHelper::getParams('com_joomleague');
			$profile = $this->getStringParam($params, 'sports_bootstrap_profile');

			if ($profile === '') {
				throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_SPORTS_BOOTSTRAP_ERROR_PROFILE_REQUIRED'));
			}

			$result = $this->service->create(
				$profile,
				$this->getBoolParam($params, 'sports_bootstrap_create_positions', true),
				$this->getBoolParam($params, 'sports_bootstrap_create_eventtypes', true),
				$this->getBoolParam($params, 'sports_bootstrap_create_statistics', true)
			);

			$this->setRedirect($redirect, Text::sprintf(
				'COM_JOOMLEAGUE_SPORTS_BOOTSTRAP_SUCCESS',
				$result['sports'],
				$result['positions'],
				$result['events'],
				$result['statistics'],
				$result['position_events'],
				$result['position_statistics']
			));
		} catch (Throwable $exception) {
			$message = $exception->getMessage();
			$this->setRedirect($redirect, str_starts_with($message, 'COM_') ? Text::_($message) : $message, 'error');
		}
	}

	private function getStringParam(Registry $params, string $key, string $default = ''): string
	{
		$value = $params->get($key, null);

		if ($value === null) {
			$value = $params->get('params.' . $key, $default);
		}

		return trim((string) $value);
	}

	private function getBoolParam(Registry $params, string $key, bool $default): bool
	{
		$value = $params->get($key, null);

		if ($value === null) {
			$value = $params->get('params.' . $key, $default ? 1 : 0);
		}

		return (bool) (int) $value;
	}
}
