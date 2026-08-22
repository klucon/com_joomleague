<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Database\DatabaseInterface;

final class ProjectController extends FormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_PROJECT';
	protected $view_list = 'projects';

	protected function allowAdd($data = []): bool
	{
		return $this->app->getIdentity()->authorise('core.create', 'com_joomleague');
	}

	protected function allowEdit($data = [], $key = 'id'): bool
	{
		$projectId = (int) ($data[$key] ?? 0);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		$user = $this->app->getIdentity();
		return $user->authorise('core.edit', $asset)
			|| ($projectId > 0 && $user->authorise('core.edit.own', $asset) && $this->isOwner($projectId, (int) $user->id));
	}

	private function isOwner(int $projectId, int $userId): bool
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select($db->quoteName('created_by'))
			->from($db->quoteName('#__joomleague_project'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $projectId, \Joomla\Database\ParameterType::INTEGER);

		return (int) $db->setQuery($query)->loadResult() === $userId;
	}
}
