<?php

declare(strict_types=1);
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class JFormFieldStandingsStage extends ListField
{
    protected $type = 'StandingsStage';

    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $projectId = (int) $this->form->getValue('project_id', $this->group);
        if ($projectId < 1) return $options;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        try {
            $query = $db->getQuery(true)->select(['id AS value', 'name AS text'])
                ->from($db->quoteName('#__joomleague_project_stage'))
                ->where('project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER)->order('ordering ASC, id ASC');
            foreach ($db->setQuery($query)->loadObjectList() as $row) {
                $options[] = HTMLHelper::_('select.option', (string) $row->value, (string) $row->text);
            }
        } catch (\Throwable) {
            return parent::getOptions();
        }
        return $options;
    }
}
