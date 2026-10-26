<?php
declare(strict_types=1);
namespace Joomla\CMS\Language { final class Text { public static function _(string $key): string { return $key; } } }
namespace Joomla\CMS\Router { final class Route { public static function _(string $url): string { return $url; } } }
namespace Joomla\CMS\HTML { final class HTMLHelper { public static function _(string $key): string { return ''; } } }
namespace {
    define('_JEXEC', 1);
    $root = dirname(__DIR__, 2);
    require $root . '/administrator/components/com_joomleague/src/Service/DecimalDisplayFormatter.php';
    $view = new class {
        public object $match;
        public object $project;
        public array $participants = [];
        public array $profile = [];
        public bool $canEdit = false;
        public array $events = [];
        public function escape(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
        public function render(string $file): string {
            ob_start();
            try { include $file; return ob_get_contents(); } finally { ob_end_clean(); }
        }
    };
    $view->match = (object) ['id' => 1, 'match_number' => 'Fixture'];
    $view->project = (object) ['name' => 'Fixture'];
    foreach (['1.000000000', '0.000000000', '3.125000000', null] as $index => $value) {
        $view->events[] = (object) ['id' => $index + 1, 'sequence_number' => $index + 1, 'event_name_key' => 'Goal',
            'match_participant_id' => 1, 'primary_name_snapshot' => '<script>name</script>', 'actor_name_snapshot' => '',
            'secondary_name_snapshot' => '', 'phase_code' => '', 'phase_sequence' => null,
            'clock_value' => $index === 0 ? '51.000000000' : null, 'clock_unit' => 'minute',
            'numeric_value' => $value, 'text_value' => '<b>note</b>'];
    }
    $html = $view->render($root . '/administrator/components/com_joomleague/tmpl/matchevents/default.php');
    foreach (['>1</td>', '>0</td>', '>3.125</td>', '51 minute', '&lt;b&gt;note&lt;/b&gt;', '&lt;script&gt;name&lt;/script&gt;'] as $expected) {
        if (!str_contains($html, $expected)) throw new \RuntimeException('Missing rendered event value: ' . $expected);
    }
    if (str_contains($html, '.000000000') || str_contains($html, '<script>')) throw new \RuntimeException('Unsafe/raw event output');
    echo "PASS: actual event timeline formats counts, zero, fractions and clock without losing text escaping.\n";
}
