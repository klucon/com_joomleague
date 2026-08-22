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

final class SourceroundField extends ListField
{
	protected $type = 'Sourceround';
	protected function getOptions(): array
	{
		$options = [HTMLHelper::_('select.option', '', Text::_('JOPTION_ALL'))];
		$projectId = (int) ($this->form->getValue('project_id') ?: Factory::getApplication()->getInput()->getInt('project_id'));
		if ($projectId < 1) return $options;
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)->select(['round.id','round.name','stage.name AS stage_name'])->from($db->quoteName('#__joomleague_project_round','round'))->innerJoin($db->quoteName('#__joomleague_project_stage','stage').' ON stage.id = round.stage_id')->where('round.project_id = :project')->bind(':project',$projectId,ParameterType::INTEGER)->order(['stage.ordering ASC','round.sequence_number ASC','round.ordering ASC']);
		foreach ($db->setQuery($query)->loadObjectList() as $round) $options[] = HTMLHelper::_('select.option',(int)$round->id,(string)$round->stage_name.' / '.(string)$round->name);
		return $options;
	}
}
