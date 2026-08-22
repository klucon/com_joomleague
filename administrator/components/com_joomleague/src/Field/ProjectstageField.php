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

final class ProjectstageField extends ListField
{
	protected $type = 'Projectstage';
	protected function getOptions(): array
	{
		$options = [HTMLHelper::_('select.option', '', Text::_('COM_JOOMLEAGUE_OPTION_SELECT_STAGE'))];
		$projectId = (int) ($this->form->getValue('project_id') ?: Factory::getApplication()->getInput()->getInt('project_id'));
		if ($projectId < 1) return $options;
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)->select([$db->quoteName('id', 'value'), $db->quoteName('name', 'text')])->from($db->quoteName('#__joomleague_project_stage'))->where('project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER)->order(['ordering ASC', 'sequence_number ASC', 'name ASC']);
		return array_merge($options, $db->setQuery($query)->loadObjectList());
	}
}
