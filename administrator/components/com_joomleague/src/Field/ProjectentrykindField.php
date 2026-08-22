<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ProjectentrykindField extends ListField
{
	protected $type = 'Projectentrykind';

	protected function getOptions(): array
	{
		$projectId = Factory::getApplication()->getInput()->getInt('project_id');

		if ($projectId < 1 && is_object($this->form->getData())) {
			$projectId = (int) $this->form->getData()->get('project_id');
		}

		if ($projectId < 1) {
			return [];
		}

		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $database->getQuery(true)
			->select($database->quoteName('version.payload_json'))
			->from($database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where($database->quoteName('project.id') . ' = :projectId')
			->bind(':projectId', $projectId, ParameterType::INTEGER);
		$payload = $database->setQuery($query)->loadResult();
		$profile = is_string($payload) ? json_decode($payload, true) : null;
		$allowedKinds = is_array($profile) ? ($profile['entry_model']['allowed_kinds'] ?? []) : [];
		$labels = [
			'team' => 'COM_JOOMLEAGUE_ENTRY_KIND_TEAM',
			'person' => 'COM_JOOMLEAGUE_ENTRY_KIND_PERSON',
			'group' => 'COM_JOOMLEAGUE_ENTRY_KIND_GROUP',
		];
		$options = [];

		foreach ($allowedKinds as $kind) {
			if (isset($labels[$kind])) {
				$options[] = HTMLHelper::_('select.option', $kind, Text::_($labels[$kind]));
			}
		}

		return array_merge(parent::getOptions(), $options);
	}
}
