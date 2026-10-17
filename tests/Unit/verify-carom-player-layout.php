<?php
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text {
        public static function _(string $key): string { return $key; }
        public static function sprintf(string $key, mixed ...$args): string { return $key . ':' . implode(',', $args); }
    }
}
namespace Joomla\CMS\Router {
    final class Route { public static function _(string $url): string { return $url; } }
}
namespace Joomla\CMS\HTML {
    final class HTMLHelper { public static function _(string $type, mixed ...$args): string { return 'Date'; } }
}
namespace {
    define('_JEXEC', 1);
    $person = (object) ['id' => 7];
    $summary = ['project_id' => 1, 'project_name' => '<Project>', 'season_id' => 2, 'season_name' => '<Season>', 'competition_name' => '<League>', 'games' => 25, 'wins' => 20, 'draws' => 3, 'losses' => 2, 'caroms' => 900, 'innings' => 450, 'complete' => true, 'average' => 2.0, 'best_average' => 3.0, 'high_run' => 12];
    $game = ['scheduled_start' => null, 'timezone' => 'UTC', 'project_name' => '<Project>', 'sequence_number' => 1, 'opponent_id' => 8, 'opponent_name' => '<script>alert(1)</script>', 'match_id' => 9, 'score' => 40, 'opponent_score' => 30, 'outcome' => 'win', 'innings' => 20, 'average' => 2.0, 'high_run' => 12];
    $data = ['carom' => ['projects' => [$summary], 'history' => [$game], 'total' => 45, 'start' => 20, 'limit' => 20, 'filters' => ['project_id' => 1, 'season_id' => 2, 'projects' => [1 => '<Project>'], 'seasons' => [2 => '<Season>']]]];
    $render = static function () use (&$data, $person): string {
        ob_start(); require dirname(__DIR__, 2) . '/components/com_joomleague/tmpl/person/carom.php'; return ob_get_clean();
    };
    $html = $render();
    foreach (['&lt;Project&gt;', '&lt;script&gt;', 'carom_start=0', 'carom_start=40', 'carom_project_id=1', 'carom_season_id=2', '21,40,45', '2.000'] as $needle) if (!str_contains($html, $needle)) throw new \RuntimeException('Missing player layout output: ' . $needle);
    if (str_contains($html, '<script>')) throw new \RuntimeException('Player layout did not escape public data.');
    $data['carom']['history'][0]['opponent_id'] = 0;
    $data['carom']['projects'][0]['complete'] = false;
    $data['carom']['projects'][0]['average'] = null;
    $html = $render();
    if (str_contains($html, 'person_id=8') || str_contains($html, 'alert(1)') || !str_contains($html, 'COM_JOOMLEAGUE_PERSON_CAROM_PRIVATE_OPPONENT')) throw new \RuntimeException('Private opponent leaked.');
    $data['carom']['history'] = [];
    if (!str_contains($render(), 'COM_JOOMLEAGUE_PERSON_CAROM_EMPTY')) throw new \RuntimeException('No empty state.');
    echo "Carom player layout OK: escaped identities, private opponent, missing data, empty state and filtered pagination.\n";
}
