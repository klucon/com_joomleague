<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class ProjectofficialsController extends BaseController
{
	public function add(): void
	{
		$this->authorize(); $projectId = $this->input->getInt('project_id');
		try { $this->getModel('Projectofficials')->add($projectId, $this->input->get('assignment', [], 'array')); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_ADD_SUCCESS'), 'success'); }
		catch (\Throwable $error) { Log::add($error->getMessage(), Log::ERROR, 'com_joomleague.projectofficials'); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_ADD_FAILED'), 'error'); }
		$this->redirectBack($projectId);
	}

	public function remove(): void
	{
		$this->authorize(); $projectId = $this->input->getInt('project_id'); $ids = array_map('intval', $this->input->get('cid', [], 'array'));
		if ($ids === []) { $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_SELECT_REMOVE'), 'warning'); $this->redirectBack($projectId); return; }
		try { $this->getModel('Projectofficials')->remove($projectId, $ids); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_REMOVE_SUCCESS'), 'success'); }
		catch (\Throwable $error) { Log::add($error->getMessage(), Log::ERROR, 'com_joomleague.projectofficials'); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROJECTOFFICIALS_REMOVE_FAILED'), 'error'); }
		$this->redirectBack($projectId);
	}

	private function authorize(): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		$projectId = $this->input->getInt('project_id', 0);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('joomleague.project.manage.officials', $asset)) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}
	private function redirectBack(int $projectId): void { $this->setRedirect(Route::_('index.php?option=com_joomleague&view=projectofficials&project_id=' . $projectId, false)); }
}
