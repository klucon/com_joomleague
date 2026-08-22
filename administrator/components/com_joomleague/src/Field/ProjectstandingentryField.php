<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ProjectstandingentryField extends ListField
{
	protected $type = 'Projectstandingentry';

	protected function getOptions(): array
	{
		$options = [];
		$projectId = (int) ($this->form->getValue('project_id') ?: Factory::getApplication()->getInput()->getInt('project_id'));
		if ($projectId < 1) return parent::getOptions();
		$stageId = (int) ($this->form->getValue('stage_id') ?: Factory::getApplication()->getInput()->getInt('stage_id'));
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)->select(['entry.id', 'entry.display_name', 'entry.entry_kind', 'team.name AS team_name', 'person.first_name', 'person.last_name'])
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')->where('entry.project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER)->order(['entry.ordering ASC', 'entry.id ASC']);
		if ($stageId > 0) {
			$stageQuery = $db->getQuery(true)->select('entry_selection_mode')->from($db->quoteName('#__joomleague_project_stage'))->where('id = :stage')->where('project_id = :project')->bind(':stage', $stageId, ParameterType::INTEGER)->bind(':project', $projectId, ParameterType::INTEGER);
			if ($db->setQuery($stageQuery)->loadResult() === 'explicit') $query->innerJoin($db->quoteName('#__joomleague_stage_entry', 'stage_entry') . ' ON stage_entry.entry_id = entry.id AND stage_entry.stage_id = ' . $stageId);
		}
		foreach ($db->setQuery($query)->loadObjectList() as $entry) { $text = match ((string) $entry->entry_kind) { 'team' => (string) $entry->team_name, 'person' => trim((string) $entry->first_name . ' ' . (string) $entry->last_name), default => (string) $entry->display_name }; $options[] = HTMLHelper::_('select.option', (int) $entry->id, $text); }
		return array_merge(parent::getOptions(), $options);
	}
}
