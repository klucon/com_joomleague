<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class EntrymemberController extends FormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_ENTRYMEMBER';
	protected $view_list = 'entrymembers';

	protected function allowAdd($data = []): bool
	{
		$entryId = (int) ($data['entry_id'] ?? $this->input->getInt('entry_id', 0));
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromEntry($entryId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		return $this->app->getIdentity()->authorise('core.create', $asset);
	}

	protected function allowEdit($data = [], $key = 'id'): bool
	{
		$entryId = (int) ($data['entry_id'] ?? 0);
		if ($entryId < 1) {
			$id = (int) ($data[$key] ?? 0);
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)->select($db->quoteName('entry_id'))->from($db->quoteName('#__joomleague_project_entry_member'))
				->where($db->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
			$entryId = (int) $db->setQuery($query)->loadResult();
		}
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromEntry($entryId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		return $this->app->getIdentity()->authorise('core.edit', $asset);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string
	{
		return parent::getRedirectToItemAppend($recordId, $urlVar) . $this->entryAppend();
	}

	protected function getRedirectToListAppend(): string
	{
		return parent::getRedirectToListAppend() . $this->entryAppend();
	}

	private function entryAppend(): string
	{
		$entryId = $this->input->getInt('entry_id');

		return $entryId > 0 ? '&entry_id=' . $entryId : '';
	}
}
