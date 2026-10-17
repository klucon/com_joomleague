<?php
declare(strict_types=1);
namespace Joomla\CMS {
    final class Factory { public static function getLanguage(): object { return new class { public function getTag(): string { return 'en-GB'; } }; } }
}
namespace Joomla\CMS\Language {
    final class Text { public static function _(string $key): string { return $key; } public static function plural(string $key, int $count): string { return $count . ' events'; } }
}
namespace Joomla\CMS\Router {
    final class Route { public static function _(string $url): string { return $url; } }
}
namespace {
    define('_JEXEC', 1);
    $root = getenv('CALENDAR_MODULE_PATH') ?: __DIR__ . '/../../modules/mod_joomleague_calendar';
    function renderCalendar(string $root, array $options, array $calendar): string {
        $params = new class($options) {
            public function __construct(private array $values) {}
            public function get(string $key, mixed $default = null): mixed { return $this->values[$key] ?? $default; }
        };
        ob_start(); require $root . '/tmpl/default.php'; return ob_get_clean();
    }
    $timestamp = strtotime('2026-10-02 22:30:00 UTC');
    $data = ['timezone' => 'Europe/Prague', 'groups' => [['date' => '2026-10-03', 'items' => [[
        'id' => 9, 'timestamp' => $timestamp, 'scheduled_start' => '2026-10-02 22:30:00',
        'show_detail' => true, 'project_name' => 'Project visible', 'stage_name' => 'Stage visible', 'venue_name' => 'Venue visible',
        'participants' => [['name' => '<script>attack</script>', 'score' => '0']],
    ]]]]];
    $html = renderCalendar($root, ['show_stage' => 1], $data);
    if (str_contains($html, '<script>') || !str_contains($html, '&lt;script&gt;')) throw new \RuntimeException('HTML escaping');
    if (!str_contains($html, '>0</span>')) throw new \RuntimeException('Zero score disappeared');
    if (!str_contains($html, '00:30') || !str_contains($html, 'Saturday')) throw new \RuntimeException('Local date and time mismatch');
    if (!str_contains($html, '2026-10-02T22:30:00Z')) throw new \RuntimeException('Ambiguous machine-readable time');
    $data['groups'][0]['items'][0]['show_detail'] = false;
    $html = renderCalendar($root, ['show_project' => 0, 'show_stage' => 0, 'show_time' => 0, 'show_venue' => 0, 'show_result' => 0, 'group_by_date' => 0, 'show_date' => 0], $data);
    foreach (['Project visible', 'Stage visible', 'Venue visible', '<time', '<h3', 'href=', 'badge'] as $hidden) {
        if (str_contains($html, $hidden)) throw new \RuntimeException('Disabled output still visible: ' . $hidden);
    }
    if (str_contains(renderCalendar($root, ['show_empty' => 0], ['error' => 'EMPTY']), 'EMPTY')) throw new \RuntimeException('Empty state toggle');
    require_once $root . '/src/Helper/CalendarOptions.php';
    $month = new \DateTimeImmutable('2026-10-01', new \DateTimeZone('Europe/Prague'));
    $data += ['presentation' => 'month', 'module_id' => 99, 'month' => '2026-10-01', 'grid' => \Joomleague\Module\Calendar\Site\Helper\CalendarOptions::monthGrid($month, 1), 'previous_url' => '/?month=2026-09', 'next_url' => '/?month=2026-11', 'today_url' => '/'];
    $html = renderCalendar($root, [], $data);
    if (!str_contains($html, '<table') || !str_contains($html, 'href="#jl-calendar-99-2026-10-03"')) throw new \RuntimeException('Monthly agenda links');
    if (!str_contains($html, 'October 2026')) throw new \RuntimeException('Month title');
    if (getenv('CALENDAR_RENDER_FILE')) file_put_contents(getenv('CALENDAR_RENDER_FILE'), '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body><main class="container py-3">' . $html . '</main></body></html>');
    echo "Calendar template OK: visibility, zero score, escaping and localized date/time\n";
}
