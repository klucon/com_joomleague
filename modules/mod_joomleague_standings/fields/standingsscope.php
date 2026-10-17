<?php

declare(strict_types=1);
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class JFormFieldStandingsScope extends ListField
{
    protected $type = 'StandingsScope';

    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $projectId = (int) $this->form->getValue('project_id', $this->group);
        if ($projectId < 1) return $options;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        try {
            Factory::getApplication()->bootComponent('com_joomleague');
            $context = (new \Joomleague\Component\Joomleague\Domain\Service\StandingsReader($db))->describe($projectId, null);
            $language = Factory::getLanguage();
            $language->load('com_joomleague', JPATH_SITE) || $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');
            foreach ($context['available_scopes'] as $code) {
                $key = 'COM_JOOMLEAGUE_STANDINGS_SCOPE_' . strtoupper($code);
                $label = Text::_($key);
                $options[] = HTMLHelper::_('select.option', $code, $label === $key ? $code : $label);
            }
        } catch (\Throwable) {
            return parent::getOptions();
        }
        return $options;
    }
}
