<?php
declare(strict_types=1);
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class JFormFieldRankingstage extends ListField
{
    protected $type = 'Rankingstage';
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $project = (int) $this->form->getValue('project_id', $this->group);
        if ($project < 1) return $options;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)->select(['id', 'name'])
            ->from($db->quoteName('#__joomleague_project_stage'))
            ->where('project_id=:project')->where('published=1')->order(['ordering', 'id'])
            ->bind(':project', $project, ParameterType::INTEGER);
        foreach ($db->setQuery($query)->loadObjectList() as $stage) {
            $options[] = HTMLHelper::_('select.option', (int) $stage->id, (string) $stage->name);
        }
        return $options;
    }
}
