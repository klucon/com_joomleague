<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
// Test-only namespace registration for standalone repository scripts.
spl_autoload_register(static function (string $class): void {
    foreach ([
        'Joomleague\\Component\\Joomleague\\Administrator\\' => '/var/www/html/administrator/components/com_joomleague/src/',
        'Joomleague\\Component\\Joomleague\\Domain\\' => '/var/www/html/administrator/components/com_joomleague/src/',
        'Joomleague\\Component\\Joomleague\\Site\\' => '/var/www/html/components/com_joomleague/src/',
    ] as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) continue;
        $file = $directory . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) require_once $file;
        return;
    }
});
