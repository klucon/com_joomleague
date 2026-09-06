<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\TemplateConfigResolver;
use Joomleague\Component\Joomleague\Administrator\Service\TemplateDefinitionRegistry;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;

final class TemplatesModel extends BaseDatabaseModel
{
	/** @return list<object> */
	public function getItems(): array
	{
		$database = $this->getDatabase();
		$query = $database->getQuery(true)
			->select(['p.id AS profile_id', 'p.code AS profile_code', 'p.name_key', 'p.description_key'])
			->select(['v.id AS profile_version_id', 'v.profile_version', 'v.schema_version AS profile_schema_version', 'v.payload_json'])
				->select(['c.template_code', 'c.schema_version AS config_schema_version', 'c.params_json', 'c.params_checksum', 'c.published AS config_published'])
			->from($database->quoteName('#__joomleague_sport_profile', 'p'))
			->innerJoin($database->quoteName('#__joomleague_sport_profile_version', 'v') . ' ON v.profile_id = p.id AND v.state = ' . $database->quote('active'))
			->leftJoin($database->quoteName('#__joomleague_profile_template_config', 'c') . ' ON c.profile_version_id = v.id AND c.published = 1')
			->where('p.published = 1')
			->order(['p.code ASC', 'v.profile_version DESC', 'c.template_code ASC']);

		$rows = $database->setQuery($query)->loadObjectList();
		$registry = new TemplateDefinitionRegistry();
		$resolver = new TemplateConfigResolver($registry);
		$profiles = [];

		foreach ($rows as $row) {
			$key = (string) $row->profile_version_id;

			if (!isset($profiles[$key])) {
				$payload = json_decode((string) $row->payload_json, true, 512, JSON_THROW_ON_ERROR);
				$profiles[$key] = (object) [
					'profile_version_id' => (int) $row->profile_version_id,
					'profile_code' => $row->profile_code,
					'name_key' => $row->name_key,
					'description_key' => $row->description_key,
					'profile_version' => $row->profile_version,
					'profile_schema_version' => $row->profile_schema_version,
					'payload' => $payload,
					'overrides' => [],
					'templates' => [],
				];
			}

				if ($row->template_code !== null) {
					if (!hash_equals(TemplateDefinitionRegistry::SCHEMA_VERSION, (string) $row->config_schema_version)) {
						throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_CONFIG_UNSUPPORTED');
					}

					$override = json_decode((string) $row->params_json, true, 512, JSON_THROW_ON_ERROR);

					if (!is_array($override) || (array_is_list($override) && $override !== [])) {
						throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_CONFIG_INVALID');
					}

					$registry->validateValues((string) $row->template_code, $override);

					if (!hash_equals(CanonicalJson::checksum($override), (string) $row->params_checksum)) {
						throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_CHECKSUM');
					}

					$profiles[$key]->overrides[$row->template_code] = $override;
				}
		}

		foreach ($profiles as $profile) {
			$profileDefaults = $profile->payload['template_defaults'] ?? [];

			if (!is_array($profileDefaults)) {
				throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_DEFAULTS_INVALID');
			}

			foreach ($profileDefaults as $templateCode => $bundled) {
				$definition = $registry->get((string) $templateCode);
				$override = $profile->overrides[$templateCode] ?? [];

				if (!is_array($bundled) || !is_array($override)) {
					throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_CONFIG_INVALID');
				}

				$profile->templates[] = (object) [
					'code' => $templateCode,
					'fields' => $definition['fields'],
					'name_key' => $definition['name_key'],
					'description_key' => $definition['description_key'],
					'bundled' => $bundled,
					'overrides' => $override,
					'effective' => $resolver->resolveProfileTemplate((string) $templateCode, $profile->payload, $override),
				];
			}

			unset($profile->payload, $profile->overrides);
		}

		return array_values($profiles);
	}

	/** @return array{profiles: int, templates: int, overrides: int, definitions: int} */
	public function getSummary(): array
	{
		$items = $this->getItems();
		$templates = 0;
		$overrides = 0;

		foreach ($items as $profile) {
			$templates += count($profile->templates);

			foreach ($profile->templates as $template) {
				$overrides += $template->overrides === [] ? 0 : 1;
			}
		}

		return [
			'profiles' => count($items),
			'templates' => $templates,
			'overrides' => $overrides,
			'definitions' => count((new TemplateDefinitionRegistry())->all()),
		];
	}
}
