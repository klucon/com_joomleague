<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;

final class ContesttypeField extends ListField
{
	protected $type = 'Contesttype';

	protected function getOptions(): array
	{
		$projectId = (int) ($this->form->getValue('project_id') ?: 0);
		$contestType = 'head_to_head';

		if ($projectId > 0) {
			$database = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
			$project = (new ProjectContextRepository($database))->get($projectId);
			$contestType = (string) ($project->profile['contest']['type'] ?? $contestType);
		}

		$labelKey = 'COM_JOOMLEAGUE_CONTEST_TYPE_' . strtoupper($contestType);
		$label = Text::_($labelKey);

		if ($label === $labelKey) $label = $contestType;

		return [HTMLHelper::_('select.option', $contestType, $label)];
	}
}
