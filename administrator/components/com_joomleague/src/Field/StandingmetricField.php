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

final class StandingmetricField extends ListField
{
	protected $type = 'Standingmetric';

	protected function getOptions(): array
	{
		$projectId = (int) ($this->form->getValue('project_id') ?: Factory::getApplication()->getInput()->getInt('project_id'));
		if ($projectId < 1) return parent::getOptions();
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)->select(['version.profile_id', 'version.payload_json'])->from($db->quoteName('#__joomleague_project', 'project'))->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')->where('project.id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
		$version = $db->setQuery($query)->loadObject(); $profile = $version ? json_decode((string) $version->payload_json, true) : null;
		if (!is_array($profile['standings']['calculation'] ?? null) && $version) { $profileId = (int) $version->profile_id; $query = $db->getQuery(true)->select('payload_json')->from($db->quoteName('#__joomleague_sport_profile_version'))->where('profile_id = :profile')->where('state = ' . $db->quote('active'))->bind(':profile', $profileId, ParameterType::INTEGER)->order('id DESC'); $profile = json_decode((string) $db->setQuery($query, 0, 1)->loadResult(), true); }
		$options = [];
		foreach ($profile['standings']['calculation']['metrics'] ?? [] as $metric) if (is_array($metric) && is_string($metric['code'] ?? null) && !in_array($metric['operation'] ?? null, ['difference', 'ratio'], true)) $options[] = HTMLHelper::_('select.option', $metric['code'], Text::_('COM_JOOMLEAGUE_STANDING_METRIC_' . strtoupper($metric['code'])));
		return array_merge(parent::getOptions(), $options);
	}
}
