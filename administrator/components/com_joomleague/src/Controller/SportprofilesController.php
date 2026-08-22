<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class SportprofilesController extends BaseController
{
	public function synchronise(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		if (!$this->app->getIdentity()->authorise('core.manage', 'com_joomleague')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		try {
			$result = $this->getModel('Sportprofiles')->synchroniseBundledProfiles();
			$this->app->enqueueMessage(Text::sprintf('COM_JOOMLEAGUE_SPORTPROFILES_SYNC_SUCCESS', $result['processed']));
		} catch (\Throwable $error) {
			$this->app->enqueueMessage($error->getMessage(), 'error');
		}
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=sportprofiles', false));
	}
}
