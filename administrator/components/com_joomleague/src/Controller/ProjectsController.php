<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectCloner;

final class ProjectsController extends AdminController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_PROJECTS';

	public function getModel($name = 'Project', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function duplicate(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.create', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$ids = array_values(array_filter(array_map('intval', (array) $this->input->post->get('cid', [], 'array'))));
		if ($ids === []) {
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROJECTS_DUPLICATE_NONE_SELECTED'), 'warning');
			$this->setRedirect('index.php?option=com_joomleague&view=projects');
			return;
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$cloner = new ProjectCloner($db);
		$newIds = [];
		try {
			foreach ($ids as $sourceId) {
				$sourceName = (string) $db->setQuery(
					$db->getQuery(true)->select('name')->from($db->quoteName('#__joomleague_project'))
						->where($db->quoteName('id') . ' = ' . (int) $sourceId)
				)->loadResult();
				$newName = Text::sprintf('COM_JOOMLEAGUE_PROJECTS_DUPLICATE_NAME', $sourceName !== '' ? $sourceName : $sourceId);
				$newIds[] = $cloner->clone($sourceId, $newName);
			}
		} catch (\Throwable $exception) {
			$this->app->enqueueMessage(Text::sprintf('COM_JOOMLEAGUE_PROJECTS_DUPLICATE_FAILED', $exception->getMessage()), 'error');
			$this->setRedirect('index.php?option=com_joomleague&view=projects');
			return;
		}

		if (count($newIds) === 1) {
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROJECTS_DUPLICATE_SUCCESS_SINGLE'));
			$this->setRedirect('index.php?option=com_joomleague&task=project.edit&id=' . (int) $newIds[0]);
			return;
		}

		$this->app->enqueueMessage(Text::sprintf('COM_JOOMLEAGUE_PROJECTS_DUPLICATE_SUCCESS_MULTIPLE', count($newIds)));
		$this->setRedirect('index.php?option=com_joomleague&view=projects');
	}
}
