<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class SportprofilesModel extends BaseDatabaseModel
{
	/** @return array{processed: int} */
	public function synchroniseBundledProfiles(): array
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/script.php';
		return (new \Com_JoomleagueInstallerScript())->synchroniseBundledProfiles();
	}

	/** @return list<object> */
	public function getItems(): array
	{
		$query = $this->getDatabase()->getQuery(true)
			->select(['p.id', 'p.code', 'p.name_key', 'p.description_key', 'p.published'])
			->select(['v.schema_version', 'v.profile_version', 'v.payload_json', 'v.payload_checksum', 'v.source', 'v.state'])
			->from($this->getDatabase()->quoteName('#__joomleague_sport_profile', 'p'))
			->leftJoin($this->getDatabase()->quoteName('#__joomleague_sport_profile_version', 'v') . ' ON v.profile_id = p.id')
			->order(['p.code ASC', 'v.profile_version DESC']);

		$items = $this->getDatabase()->setQuery($query)->loadObjectList();

		foreach ($items as $item) {
			$payload = json_decode((string) ($item->payload_json ?? ''), true);
			$item->project_rule_field_count = is_array($payload['project_rule_schema']['fields'] ?? null)
				? count($payload['project_rule_schema']['fields'])
				: 0;
			unset($item->payload_json);
		}

		return $items;
	}
}
