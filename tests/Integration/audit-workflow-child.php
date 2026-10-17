<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) {
    throw new RuntimeException('Disposable workflow process required.');
}
$script = $argv[1] ?? '';
if (!in_array($script, ['match-lineup.php', 'match-events.php', 'match-statistics.php', 'match-officials.php'], true)) {
    throw new InvalidArgumentException('Unknown workflow.');
}
try {
    require __DIR__ . '/' . $script;
} catch (Throwable $failure) {
    // Retain a useful source location without messages, arguments or SQL.
    fwrite(STDERR, 'WORKFLOW_FAILURE ' . json_encode([
        'type' => get_debug_type($failure),
        'file' => basename($failure->getFile()),
        'line' => $failure->getLine(),
    ], JSON_THROW_ON_ERROR) . "\n");
    exit(1);
}
