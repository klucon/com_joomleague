<?php
declare(strict_types=1);
namespace Joomla\CMS\MVC\Controller {
    class BaseController {
        public static string $scenario = 'success';
        protected $app;
        public function getModel($name) {
            return new class {
                public function getCalendar(): array {
                    if (BaseController::$scenario === 'exception') throw new \RuntimeException('Database unavailable');
                    if (BaseController::$scenario === 'error') return ['error'=>'COM_JOOMLEAGUE_ICAL_EMPTY'];
                    return ['content' => "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n", 'filename' => 'audit.ics'];
                }
            };
        }
    }
}
namespace Joomla\CMS\Language {
    class Text { public static function _($key) { return $key; } }
}
namespace {
    define('_JEXEC', 1);
    require dirname(__DIR__, 2) . '/components/com_joomleague/src/Controller/IcalController.php';
    $app = new class {
        public array $headers = [];
        public function setHeader($name, $value, $replace) { $this->headers[$name] = $value; return $this; }
        public function sendHeaders() {}
        public function close() {}
    };
    $controller = new Joomleague\Component\Joomleague\Site\Controller\IcalController();
    (new ReflectionProperty(Joomla\CMS\MVC\Controller\BaseController::class, 'app'))->setValue($controller, $app);
    ob_start();
    try { $controller->download(); $body = ob_get_contents(); }
    finally { ob_end_clean(); }
    if (($app->headers['Cache-Control'] ?? '') !== 'private, no-store, max-age=0') throw new RuntimeException('Calendar is eligible for shared caching.');
    if (!str_contains($body, 'BEGIN:VCALENDAR') || ($app->headers['Content-Length'] ?? '') !== (string) strlen($body)) throw new RuntimeException('Calendar download changed unexpectedly.');
    echo "PASS: calendar download uses private no-store policy and preserves payload headers\n";
    foreach (['error', 'exception'] as $scenario) {
        Joomla\CMS\MVC\Controller\BaseController::$scenario = $scenario;
        $app->headers = [];
        try { $controller->download(); throw new \LogicException('Expected failure'); }
        catch (\RuntimeException $error) {
            if (($app->headers['Cache-Control'] ?? '') !== 'private, no-store, max-age=0') throw new \LogicException('Failure can enter shared cache');
            if (isset($app->headers['Content-Disposition'])) throw new \LogicException('Failure advertised as a download');
        }
        echo "PASS: no-store policy precedes $scenario response\n";
    }
}
