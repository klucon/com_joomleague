<?php
declare(strict_types=1);
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\EventRankingReader;

class JFormRuleRankingselector extends FormRule
{
    public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
    {
        if (!is_string($value) && !is_int($value) && $value !== null) return false;
        if ($value === '' || $value === null) return true;
        if (($value === 0 || $value === '0') && (string) $element['name'] === 'stage_id') return true;
        $prefix = $group ? $group . '.' : '';
        $project = $input?->get($prefix . 'project_id');
        if (filter_var($project, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) return false;
        $app = Factory::getApplication();
        $app->bootComponent('com_joomleague');
        $reader = new EventRankingReader(Factory::getContainer()->get(DatabaseInterface::class));
        $levels = $app->getIdentity()->getAuthorisedViewLevels();
        try {
            switch ((string) $element['name']) {
                case 'stage_id':
                    $reader->forProject((int) $project, null, 1, $levels, ['stage_id' => $value]);
                    return true;
                case 'event_code':
                    $reader->forProject((int) $project, (string) $value, 1, $levels, ['strict_code' => true]);
                    return true;
                case 'highlight_target':
                    $code = $input?->get($prefix . 'event_code', '');
                    if (!is_string($code)) return false;
                    $ranking = $reader->forProject((int) $project, $code ?: null, 500, $levels, ['strict_code' => true]);
                    foreach ($ranking['rows'] as $row) {
                        if ($value === ($row->target_kind . ':' . (int) $row->target_id)) return true;
                    }
            }
        } catch (\InvalidArgumentException|\RuntimeException) {
            return false;
        }
        return false;
    }
}
