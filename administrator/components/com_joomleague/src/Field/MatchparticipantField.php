<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomleague\Component\Joomleague\Administrator\Service\StageEntryOptionsProvider;

final class MatchparticipantField extends ListField
{
	protected $type = 'Matchparticipant';

	protected function getOptions(): array
	{
		$options = [HTMLHelper::_('select.option', '', Text::_('COM_JOOMLEAGUE_OPTION_SELECT_PARTICIPANT'))];
		$projectId = (int) ($this->form->getValue('project_id') ?: 0);
		$stageId = (int) ($this->form->getValue('stage_id') ?: 0);

		if ($projectId < 1 || $stageId < 1) return $options;

		$database = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

		return array_merge($options, (new StageEntryOptionsProvider($database))->load($projectId, $stageId));
	}
}
