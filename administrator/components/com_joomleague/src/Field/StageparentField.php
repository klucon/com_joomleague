<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

final class StageparentField extends ListField
{
	protected $type = 'Stageparent';

	protected function getOptions(): array
	{
		$options = [HTMLHelper::_('select.option', '', Text::_('JNONE'))];
		$projectId = (int) ($this->form->getValue('project_id') ?: Factory::getApplication()->getInput()->getInt('project_id'));
		$currentId = (int) ($this->form->getValue('id') ?: 0);

		if ($projectId < 1) return $options;

		$db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
		$query = $db->getQuery(true)->select([$db->quoteName('id', 'value'), $db->quoteName('name', 'text')])
			->from($db->quoteName('#__joomleague_project_stage'))
			->where($db->quoteName('project_id') . ' = :projectId')->bind(':projectId', $projectId, ParameterType::INTEGER)
			->order([$db->quoteName('ordering') . ' ASC', $db->quoteName('name') . ' ASC']);
		if ($currentId > 0) $query->where($db->quoteName('id') . ' <> :currentId')->bind(':currentId', $currentId, ParameterType::INTEGER);

		return array_merge($options, $db->setQuery($query)->loadObjectList());
	}
}
