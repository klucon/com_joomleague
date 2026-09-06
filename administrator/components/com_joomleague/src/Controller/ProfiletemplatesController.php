<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class ProfiletemplatesController extends BaseController
{
	public function save(): void
	{
		$this->persist(false);
	}

	public function apply(): void
	{
		$this->persist(true);
	}

	public function cancel(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=templates', false));
	}

	private function persist(bool $stay): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.options', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$data = $this->input->post->get('jform', [], 'array');
		$profileVersionId = (int) ($data['profile_version_id'] ?? 0);

		try {
			$this->getModel('Profiletemplates')->saveSubmittedTemplates($profileVersionId, (array) ($data['templates'] ?? []), (int) $this->app->getIdentity()->id);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROFILETEMPLATES_SAVE_SUCCESS'));
			$url = $stay
				? 'index.php?option=com_joomleague&view=profiletemplates&profile_version_id=' . $profileVersionId
				: 'index.php?option=com_joomleague&view=templates';
		} catch (\InvalidArgumentException|\UnexpectedValueException $exception) {
			$this->app->setUserState('com_joomleague.edit.profiletemplates.data', $data);
			$this->app->enqueueMessage(Text::_($exception->getMessage()), 'error');
			$url = 'index.php?option=com_joomleague&view=profiletemplates&profile_version_id=' . $profileVersionId;
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague.templates');
			$this->app->setUserState('com_joomleague.edit.profiletemplates.data', $data);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROFILETEMPLATES_SAVE_FAILED'), 'error');
			$url = 'index.php?option=com_joomleague&view=profiletemplates&profile_version_id=' . $profileVersionId;
		}

		$this->setRedirect(Route::_($url, false));
	}
}
