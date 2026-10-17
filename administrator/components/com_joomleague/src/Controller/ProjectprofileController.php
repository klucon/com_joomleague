<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Factory;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectProfileMigrationService;

final class ProjectprofileController extends BaseController
{
	public function migrate(): void
	{
		if (!Session::checkToken()) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
		$id = $this->input->post->getInt('project_id');
		$asset = 'com_joomleague.project.' . $id;
		$user = $this->app->getIdentity();
		if ($id < 1 || !$user->authorise('core.edit', $asset) || !$user->authorise('joomleague.project.edit.rules', $asset)) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
		$key = 'com_joomleague.profile.review.' . $id;
		$review = $this->app->getUserState($key);
		$this->app->setUserState($key, null);
		try {
			$checksum = $this->input->post->getString('review_checksum');
			$target = $this->input->post->getInt('target_version_id');
			if (!$this->input->post->getBool('acknowledge') || !is_array($review)
				|| (int) $review['target'] !== $target || !hash_equals((string) $review['checksum'], $checksum)) {
				throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_REVIEW_REQUIRED'));
			}
			$service = new ProjectProfileMigrationService(Factory::getContainer()->get(DatabaseInterface::class));
			if (($review['kind'] ?? 'base') === 'variant') $service->migrateVariant($id, $target, $checksum, (int) $user->id);
			else $service->migrate($id, $target, $checksum, (int) $user->id);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_SUCCESS'));
		} catch (\Throwable $error) {
			$this->app->enqueueMessage($error->getMessage(), 'error');
		}
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=project&layout=edit&id=' . $id . '&profile_check=1', false));
	}
}
