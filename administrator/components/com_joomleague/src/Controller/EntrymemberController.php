<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomleague\Component\Joomleague\Administrator\Service\EntryMemberPersonOptions;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class EntrymemberController extends FormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_ENTRYMEMBER';
	protected $view_list = 'entrymembers';

	public function people(): void
	{
		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->setHeader('Cache-Control', 'no-store', true);
		$this->app->setHeader('X-Content-Type-Options', 'nosniff', true);
		try {
			if (!Session::checkToken('get')) throw new \RuntimeException('Invalid token');
			$lookup = new EntryMemberPersonOptions(Factory::getContainer()->get(DatabaseInterface::class));
			$entry = $lookup->context($this->input->getInt('entry_id', 0), $this->input->getInt('member_id', 0), $this->app->getIdentity());
			$type = $this->input->getCmd('member_person_type', '');
			if (!in_array($type, $entry->profile['entry_model']['member_person_types'] ?? [], true)) throw new \RuntimeException('Invalid type');
			$rows = $lookup->search($entry, $this->app->getIdentity(), $this->input->getString('term', ''), $this->input->getBool('all', false));
			$this->app->sendHeaders();
			echo json_encode($rows, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
		} catch (\Throwable) {
			$this->app->setHeader('status', '403', true);
			$this->app->sendHeaders();
			echo json_encode(['error' => Text::_('JERROR_ALERTNOAUTHOR')]);
		}
		$this->app->close();
	}

	protected function allowAdd($data = []): bool
	{
		$entryId = (int) ($data['entry_id'] ?? $this->input->getInt('entry_id', 0));
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromEntry($entryId);
		if ($projectId < 1) return false;
		$asset = 'com_joomleague.project.' . $projectId;
		return $this->app->getIdentity()->authorise('core.create', $asset);
	}

	protected function allowEdit($data = [], $key = 'id'): bool
	{
		$entryId = 0;
		if ((int) ($data[$key] ?? 0) > 0) {
			$id = (int) ($data[$key] ?? 0);
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)->select($db->quoteName('entry_id'))->from($db->quoteName('#__joomleague_project_entry_member'))
				->where($db->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
			$entryId = (int) $db->setQuery($query)->loadResult();
		}
		if ($entryId < 1) return false;
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromEntry($entryId);
		if ($projectId < 1) return false;
		$asset = 'com_joomleague.project.' . $projectId;
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
