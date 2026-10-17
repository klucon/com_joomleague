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
	public function categories(): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		if (!$this->app->getIdentity()->authorise('core.edit', 'com_joomleague')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		try {
			$this->getModel('Sportprofiles')->assignCategories($this->input->post->get('cid', [], 'array'), $this->input->post->get('category_ids', [], 'array'));
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_ASSIGN_SAVED'));
		} catch (\Throwable $error) {
			$this->app->enqueueMessage($error instanceof \InvalidArgumentException ? $error->getMessage() : Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_SAVE_FAILED'), 'error');
		}
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=sportprofiles', false));
	}

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
