<?php
declare(strict_types=1);
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\EventRankingReader;

class JFormFieldRankingtarget extends ListField
{
    protected $type = 'Rankingtarget';
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $project = (int) $this->form->getValue('project_id', $this->group);
        if ($project < 1) return $options;
        $app = Factory::getApplication();
        $app->bootComponent('com_joomleague');
        $code = trim((string) $this->form->getValue('event_code', $this->group));
        try {
            $ranking = (new EventRankingReader(Factory::getContainer()->get(DatabaseInterface::class)))
                ->forProject($project, $code ?: null, 500, $app->getIdentity()->getAuthorisedViewLevels(), ['strict_code' => true]);
            foreach ($ranking['rows'] as $row) {
                $identity = $row->target_kind . ':' . (int) $row->target_id;
                $options[] = HTMLHelper::_('select.option', $identity, (string) $row->display_name . ' (' . $identity . ')');
            }
        } catch (\InvalidArgumentException|\RuntimeException) {
            // A stale project or event has no eligible highlight targets.
        }
        return $options;
    }
}
