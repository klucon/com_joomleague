<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomleague\Component\Joomleague\Administrator\Service\RuntimeDataResetter;
use Throwable;

final class DemodataController extends BaseController
{
	public function generate(): void
	{
		$this->assertRequestAllowed();
		$profiles = array_values(array_filter(
			(array) $this->input->post->get('profiles', ['football'], 'array'),
			'is_string'
		));

		try {
			$summary = $this->getModel('Demodata')->generate($profiles, (int) $this->app->getIdentity()->id);
			$this->app->enqueueMessage(Text::sprintf('COM_JOOMLEAGUE_DEMODATA_GENERATE_SUCCESS', count($summary)));
		} catch (Throwable $exception) {
			$this->enqueueError($exception);
		}

		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=demodata', false));
	}

	public function reset(): void
	{
		$this->assertRequestAllowed();

		if (!$this->getModel('Demodata')->isResetEnabled()) {
			throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_DEMODATA_ERROR_DISABLED'), 403);
		}

		$confirmation = $this->input->post->getString('confirmation');

		if (!hash_equals(RuntimeDataResetter::CONFIRMATION, $confirmation)) {
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_DEMODATA_ERROR_CONFIRMATION'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=demodata', false));

			return;
		}

		try {
			$count = $this->getModel('Demodata')->reset();
			$this->app->enqueueMessage(Text::sprintf('COM_JOOMLEAGUE_DEMODATA_RESET_SUCCESS', $count));
		} catch (Throwable $exception) {
			$this->enqueueError($exception);
		}

		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=demodata', false));
	}

	private function assertRequestAllowed(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.admin', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	private function enqueueError(Throwable $exception): void
	{
		$message = str_starts_with($exception->getMessage(), 'COM_JOOMLEAGUE_')
			? Text::_($exception->getMessage())
			: $exception->getMessage();
		$this->app->enqueueMessage($message, 'error');
	}
}
