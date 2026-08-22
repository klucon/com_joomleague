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

final class StandingscopeField extends ListField
{
	protected $type = 'Standingscope';

	protected function getOptions(): array
	{
		$options = [];
		if (!isset($this->element['include_all']) || (string) $this->element['include_all'] !== 'false') $options[] = HTMLHelper::_('select.option', 'all', Text::_('COM_JOOMLEAGUE_STANDINGS_SCOPE_ALL'));
		foreach ($this->contract()['scopes'] ?? [] as $scope) if (is_array($scope) && is_string($scope['code'] ?? null)) $options[] = HTMLHelper::_('select.option', $scope['code'], Text::_('COM_JOOMLEAGUE_STANDINGS_SCOPE_' . strtoupper($scope['code'])));
		return array_merge(parent::getOptions(), $options);
	}

	private function contract(): array
	{
		$projectId = (int) ($this->form->getValue('project_id') ?: Factory::getApplication()->getInput()->getInt('project_id'));
		if ($projectId < 1) return [];
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)->select(['version.profile_id', 'version.payload_json'])->from($db->quoteName('#__joomleague_project', 'project'))->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')->where('project.id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
		$version = $db->setQuery($query)->loadObject(); $profile = $version ? json_decode((string) $version->payload_json, true) : null;
		if (is_array($profile['standings']['calculation'] ?? null)) return $profile['standings']['calculation'];
		if (!$version) return [];
		$profileId = (int) $version->profile_id; $query = $db->getQuery(true)->select('payload_json')->from($db->quoteName('#__joomleague_sport_profile_version'))->where('profile_id = :profile')->where('state = ' . $db->quote('active'))->bind(':profile', $profileId, ParameterType::INTEGER)->order('id DESC');
		$current = json_decode((string) $db->setQuery($query, 0, 1)->loadResult(), true); return is_array($current['standings']['calculation'] ?? null) ? $current['standings']['calculation'] : [];
	}
}
