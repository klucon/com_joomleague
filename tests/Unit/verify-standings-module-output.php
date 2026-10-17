<?php
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text { public static function _(string $key): string { return $key; } }
}
namespace Joomla\CMS\Router {
    final class Route { public static function _(string $url): string { return $url; } }
}
namespace {
    define('_JEXEC', 1);
    $root = getenv('STANDINGS_MODULE_PATH') ?: __DIR__ . '/../../modules/mod_joomleague_standings';
    function renderStandings(string $root, array $options, array $standings): string {
        $params = new class($options) {
            public function __construct(private array $values) {}
            public function get(string $key, mixed $default = null): mixed { return $this->values[$key] ?? $default; }
        };
        ob_start(); require $root . '/tmpl/default.php'; return ob_get_clean();
    }
    $data = ['project' => (object) ['id' => 1, 'name' => 'Project heading'], 'stage' => (object) ['name' => 'Stage heading'],
        'columns' => [['type' => 'single', 'code' => 'points']],
        'rows' => [(object) ['rank_number' => 1, 'project_entry_id' => 2, 'entry_name_snapshot' => '<script>attack</script>', 'metrics' => ['points' => '0'], 'image' => '/images/logo.png']],
    ];
    $html = renderStandings($root, ['show_images' => 1, 'show_legend' => 1, 'show_stage_name' => 1, 'image_size' => 48], $data);
    foreach (['width="48"', '__legend', 'Stage heading', '>0</td>', '&lt;script&gt;', 'view=participant'] as $expected) {
        if (!str_contains($html, $expected)) throw new \RuntimeException('Missing output: ' . $expected);
    }
    if (str_contains($html, '<script>')) throw new \RuntimeException('Unescaped participant name.');
    $html = renderStandings($root, ['show_images' => 0, 'show_legend' => 0, 'show_stage_name' => 0, 'show_project_name' => 0, 'link_entry' => 0, 'density' => 'standard'], $data);
    foreach (['<img', '__legend', 'Stage heading', 'Project heading', 'href=', 'table-sm'] as $hidden) {
        if (str_contains($html, $hidden)) throw new \RuntimeException('Disabled output: ' . $hidden);
    }
    if (str_contains(renderStandings($root, ['show_empty' => 0], ['error' => 'EMPTY']), 'EMPTY')) throw new \RuntimeException('Empty-state control.');
    $data['columns'] = [['type' => 'single', 'code' => 'elapsed']];
    $data['rows'][0]->metrics = ['elapsed' => '3661'];
    if (!str_contains(renderStandings($root, [], $data), '1:01:01')) throw new \RuntimeException('Elapsed result format.');
    if (class_exists(\SimpleXMLElement::class)) {
        $xml = simplexml_load_file($root . '/mod_joomleague_standings.xml');
        foreach (['en-GB', 'cs-CZ'] as $language) {
            $ini = parse_ini_file($root . '/language/' . $language . '/mod_joomleague_standings.ini');
            foreach ($xml->xpath('//field/@label | //field/@description | //fieldset/@label | //option') as $node) {
                $key = (string) $node;
                if (str_starts_with($key, 'MOD_JOOMLEAGUE_STANDINGS_') && !isset($ini[$key])) throw new \RuntimeException('Missing language label: ' . $language . ' ' . $key);
            }
        }
    }
    echo "Standings output OK: switches, image size, zero values and escaping\n";
}
