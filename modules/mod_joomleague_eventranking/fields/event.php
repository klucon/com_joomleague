<?php
declare(strict_types=1);
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\EventRankingReader;

class JFormFieldEvent extends ListField
{
    protected $type = 'Event';
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $project = (int) $this->form->getValue('project_id', $this->group);
        if ($project < 1) return $options;
        $app = Factory::getApplication();
        $app->bootComponent('com_joomleague');
        Factory::getLanguage()->load('com_joomleague', JPATH_SITE);
        try {
            $ranking = (new EventRankingReader(Factory::getContainer()->get(DatabaseInterface::class)))
                ->forProject($project, null, 1, $app->getIdentity()->getAuthorisedViewLevels());
            foreach ($ranking['definitions'] as $definition) {
                $options[] = HTMLHelper::_('select.option', (string) $definition->event_code, Text::_((string) $definition->name_key));
            }
        } catch (\InvalidArgumentException|\RuntimeException $error) {
            \Joomla\CMS\Log\Log::add('Event ranking selector failure: ' . get_debug_type($error), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');
        }
        return $options;
    }
}
