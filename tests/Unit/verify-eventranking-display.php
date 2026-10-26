<?php
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text {
        public static function _(string $key): string { return $key; }
        public static function sprintf(string $key, int $count): string { return $key . ': ' . $count; }
    }
}
namespace Joomla\CMS\Router {
    final class Route { public static function _(string $url): string { return $url; } }
}
namespace {
    define('_JEXEC', 1);
    $root = dirname(__DIR__, 2);
    require $root . '/modules/mod_joomleague_eventranking/src/Helper/DisplayOptions.php';
    $make = static fn (array $values): object => new class($values) {
        public function __construct(private array $values) {}
        public function get(string $key, mixed $default = null): mixed { return $this->values[$key] ?? $default; }
    };
    $assert = static function (bool $condition, string $message): void {
        if (!$condition) throw new \RuntimeException($message);
    };
    $ranking = ['project' => (object) ['id' => 3, 'name' => '<script>project</script>'],
        'definitions' => [(object) ['event_code' => 'goal', 'name_key' => 'EVENT_GOAL']],
        'selected_code' => 'goal', 'rows' => [(object) ['target_kind' => 'person',
            'target_id' => 7, 'display_name' => '<img src=x onerror=alert(1)>',
            'rank' => 1, 'total_value' => 12, 'appearances' => 4]]];
    $render = static function (array $options, array $ranking) use ($make, $root): string {
        $params = $make($options);
        ob_start();
        try { include $root . '/modules/mod_joomleague_eventranking/tmpl/default.php'; return ob_get_contents(); }
        finally { ob_end_clean(); }
    };
    if (($argv[1] ?? '') === '--fixture') {
        echo $render(['orientation' => 'horizontal', 'density' => 'compact', 'show_event_name' => 1, 'show_appearances' => 1], $ranking);
        exit;
    }
    $ranking['rows'][0]->image = '/images/test.png';
    $html = $render([], $ranking);
    $assert(!str_contains($html, '<img '), 'Images disabled by default');
    $image = $render(['show_images' => 1, 'image_size' => 64, 'highlight_target' => 'person:7', 'show_unit' => 1], $ranking);
    $assert(str_contains($image, 'width="64" height="64"') && str_contains($image, 'src="/images/test.png"'), 'Image size and source');
    $assert(str_contains($image, 'list-group-item-primary') && str_contains($image, 'jl-event-unit'), 'Highlight and count unit');
    $assert(!str_contains($render(['highlight_target' => 'team:7'], $ranking), 'list-group-item-primary'), 'Target kind isolates highlight');
    $assert(!str_contains($render(['show_unit' => 1, 'show_value' => 0], $ranking), 'jl-event-unit'), 'Unit follows value visibility');
    $assert(str_contains($render(['show_images' => 1, 'image_size' => '999;bad'], $ranking), 'width="48"'), 'Invalid size normalized');
    $assert(!str_contains($html, '<script>') && !str_contains($html, '<img '), 'Unescaped identity');
    $assert(str_contains($html, 'person_id=7') && str_contains($html, 'jl-event-rank'), 'Default links/rank');
    foreach (['show_rank' => 'jl-event-rank', 'show_value' => 'jl-event-value',
        'show_project_name' => '&lt;script&gt;project', 'show_all_link' => 'SHOW_ALL'] as $key => $marker) {
        $assert(!str_contains($render([$key => 0], $ranking), $marker), 'Ignored switch ' . $key);
    }
    $assert(!str_contains($render(['link_target' => 0, 'show_all_link' => 0], $ranking), 'href='), 'Disabled links');
    foreach (['show_event_name' => 'jl-event-heading', 'show_appearances' => 'jl-event-appearances'] as $key => $marker) {
        $assert(!str_contains($html, $marker), 'Default disabled ' . $key);
        $assert(str_contains($render([$key => 1], $ranking), $marker), 'Ignored enabled ' . $key);
    }
    $assert(str_contains($render(['orientation' => 'horizontal', 'density' => 'compact'], $ranking), 'list-group-horizontal flex-wrap'), 'Horizontal layout');
    $assert(str_contains($render(['density' => 'compact'], $ranking), 'py-1'), 'Compact spacing');
    $empty = ['definitions' => [], 'rows' => []];
    $assert($render(['show_empty' => 0], $empty) === '', 'Hidden empty state');
    $assert(str_contains($render([], $empty), 'EVENTRANKING_EMPTY'), 'Visible empty state');
    $assert(str_contains($render(['show_empty' => 0], ['error' => 'CONFIG_ERROR']), 'CONFIG_ERROR'), 'Errors must remain visible');
    $options = \Joomleague\Module\Eventranking\Site\Helper\DisplayOptions::from($make(['show_rank' => '<script>', 'density' => ['compact'], 'orientation' => 'bad']));
    $assert(!$options['show_rank'] && $options['density'] === 'standard' && $options['orientation'] === 'vertical', 'Invalid input normalization');
    foreach (['en-GB', 'cs-CZ'] as $tag) {
        $ini = parse_ini_file($root . '/modules/mod_joomleague_eventranking/language/' . $tag . '/mod_joomleague_eventranking.ini');
        $xml = file_get_contents($root . '/modules/mod_joomleague_eventranking/mod_joomleague_eventranking.xml');
        preg_match_all('/MOD_JOOMLEAGUE_EVENTRANKING_[A-Z_]+/', $xml, $matches);
        foreach (array_unique($matches[0]) as $key) $assert(isset($ini[$key]), 'Missing translation ' . $tag . ':' . $key);
    }
    echo "PASS: event ranking display switches, defaults, invalid values, escaped output and EN/CS labels.\n";
}
