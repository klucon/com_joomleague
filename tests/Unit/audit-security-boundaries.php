<?php
declare(strict_types=1);

namespace Joomla\CMS\MVC\Controller {
    class FormController { protected $app; protected $input; public function inject($app, $input): void { $this->app=$app; $this->input=$input; } }
}
namespace {
    define('_JEXEC', 1);
    $root = dirname(__DIR__, 2);
    foreach (['verify-form-controller-ownership.php', 'verify-postgresql-statement-guard.php', 'verify-schedule-participant-lock.php', 'verify-club-history-bounds.php', 'verify-module-error-logging.php'] as $test) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/' . $test), $status);
        if ($status !== 0) exit($status);
    }
    require $root . '/administrator/components/com_joomleague/src/Service/SqlInsertDataParser.php';
    $parser = new Joomleague\Component\Joomleague\Administrator\Service\SqlInsertDataParser();
    foreach ([
        'cross-component SELECT' => 'INSERT INTO `#__joomleague_club` (`name`) SELECT password FROM `#__users`',
        'database function' => 'INSERT INTO `#__joomleague_club` (`name`) VALUES (SLEEP(30))',
    ] as $label=>$sql) {
        try { $parser->parse($sql); throw new LogicException('Unsafe insert accepted.'); }
        catch (RuntimeException) { echo 'Import ' . $label . " accepted = NO\n"; }
    }
    echo "Read-only probes only: no SQL executed and no records modified.\n";
}
