<?php
declare(strict_types=1);
namespace Joomleague\Module\Eventranking\Site\Helper;
defined('_JEXEC') or die;

final class DisplayOptions
{
    public static function from(object $params): array
    {
        $options = [];
        foreach (['show_project_name' => 1, 'show_event_name' => 0, 'show_rank' => 1,
            'show_value' => 1, 'show_appearances' => 0, 'link_target' => 1,
            'show_all_link' => 1, 'show_empty' => 1, 'show_images' => 0, 'show_unit' => 0] as $name => $default) {
            $options[$name] = in_array($params->get($name, $default), [1, '1', true], true);
        }
        $options['orientation'] = $params->get('orientation', 'vertical') === 'horizontal' ? 'horizontal' : 'vertical';
        $options['density'] = $params->get('density', 'standard') === 'compact' ? 'compact' : 'standard';
        $size = $params->get('image_size', '48');
        $options['image_size'] = in_array($size, [32, '32', 48, '48', 64, '64'], true) ? (int) $size : 48;
        $highlight = $params->get('highlight_target', '');
        $options['highlight_target'] = is_string($highlight) && preg_match('/^(person|team|participant):[1-9][0-9]{0,18}$/D', $highlight) ? $highlight : '';
        return $options;
    }
}
