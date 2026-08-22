<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class ProjectrulesController extends BaseController
{
	public function save(): void
	{
		$this->persist(false);
	}

	public function apply(): void
	{
		$this->persist(true);
	}

	public function reset(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$projectId = $this->input->getInt('project_id');
		$this->assertEditPermission($projectId);
		try {
			$this->getModel('Projectrules')->saveRules($projectId, [], (int) $this->app->getIdentity()->id);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROJECTRULES_RESET_SUCCESS'));
		} catch (\Throwable $exception) {
			$this->app->enqueueMessage($exception->getMessage(), 'error');
		}
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=projectrules&project_id=' . $projectId, false));
	}

	public function cancel(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$data = $this->input->post->get('jform', [], 'array');
		$projectId = (int) ($data['project_id'] ?? $this->input->getInt('project_id'));
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId, false));
	}

	private function persist(bool $stay): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$data = $this->input->post->get('jform', [], 'array');
		$projectId = (int) ($data['project_id'] ?? 0);
		$this->assertEditPermission($projectId);
		try {
			$this->getModel('Projectrules')->saveSubmittedRules($projectId, (array) ($data['rules'] ?? []), (int) $this->app->getIdentity()->id);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROJECTRULES_SAVE_SUCCESS'));
			$url = $stay ? 'index.php?option=com_joomleague&view=projectrules&project_id=' . $projectId : 'index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId;
		} catch (\Throwable $exception) {
			$this->app->setUserState('com_joomleague.edit.projectrules.data', $data);
			$this->app->enqueueMessage($exception->getMessage(), 'error');
			$url = 'index.php?option=com_joomleague&view=projectrules&project_id=' . $projectId;
		}
		$this->setRedirect(Route::_($url, false));
	}

	private function assertEditPermission(int $projectId): void
	{
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('joomleague.project.edit.rules', $asset)) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}
}
