<?php
declare(strict_types=1);

namespace Joomla\CMS\MVC\Controller {
    class BaseController {
        protected $app;
        protected $input;
        public $model;
        public int $modelReads = 0;
        public string $redirect = '';
        public function inject($app, $input, $model): void { $this->app = $app; $this->input = $input; $this->model = $model; }
        public function getModel($name) { $this->modelReads++; return $this->model; }
        public function setRedirect($url): void { $this->redirect = $url; }
    }
}
namespace Joomla\CMS\Language {
    class Text { public static function _($key) { return $key; } public static function sprintf($key, ...$args) { return $key; } }
}
namespace Joomla\CMS\Router {
    class Route { public static function _($url, $xhtml = true) { return $url; } }
}
namespace Joomla\CMS\Session {
    class Session { public static bool $valid = true; public static function checkToken(): bool { return self::$valid; } }
}
namespace {
    define('_JEXEC', 1);
    function jexit($message): never { throw new RuntimeException('Token denied'); }
    require dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/RuntimeDataResetter.php';
    require dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Controller/DemodataController.php';
    $phrase = Joomleague\Component\Joomleague\Administrator\Service\RuntimeDataResetter::CONFIRMATION;
    $fixture = static function (mixed $confirmation, bool $allowed = true, bool $enabled = true): array {
        $post = new class($confirmation) {
            public int $reads = 0;
            public function __construct(private mixed $value) {}
            public function get($key, $default, $filter): mixed {
                if ($key !== 'confirmation' || $filter !== 'raw') throw new RuntimeException('Literal confirmation required');
                $this->reads++;
                return $this->value;
            }
            public function getString($key): mixed { return is_string($this->value) ? strip_tags($this->value) : $this->value; }
        };
        $user = new class($allowed) {
            public function __construct(private bool $allowed) {}
            public function authorise($action, $asset): bool {
                if ($action !== 'core.admin' || $asset !== 'com_joomleague') throw new RuntimeException('Wrong permission checked');
                return $this->allowed;
            }
        };
        $app = new class($user) {
            public array $messages = [];
            public function __construct(private object $user) {}
            public function getIdentity(): object { return $this->user; }
            public function enqueueMessage($message, $type = 'message'): void { $this->messages[] = [$message, $type]; }
        };
        $model = new class($enabled) {
            public int $resets = 0;
            public function __construct(private bool $enabled) {}
            public function isResetEnabled(): bool { return $this->enabled; }
            public function reset(): int { $this->resets++; return 52; }
        };
        $controller = new Joomleague\Component\Joomleague\Administrator\Controller\DemodataController();
        $controller->inject($app, (object) ['post' => $post], $model);
        return [$controller, $model, $app, $post];
    };
    $invalid = ['', strtolower($phrase), ' ' . $phrase, $phrase . ' ', $phrase . "\n", '<b>' . $phrase . '</b>', 'REMOVE&#32;AND CLEAN DEMO DATA', [$phrase], ['confirmation' => $phrase], null, false, true, 0, 1, new stdClass()];
    foreach ($invalid as $index => $value) {
        [$controller, $model, $app, $post] = $fixture($value);
        $controller->reset();
        if ($model->resets !== 0 || $post->reads !== 1 || $app->messages !== [['COM_JOOMLEAGUE_DEMODATA_ERROR_CONFIRMATION', 'error']] || $controller->redirect === '') {
            throw new RuntimeException('Invalid confirmation accepted: case ' . $index);
        }
    }
    [$controller, $model] = $fixture($phrase);
    $controller->reset();
    if ($model->resets !== 1) throw new RuntimeException('Exact confirmation did not reach reset');
    foreach ([[false, true, true], [true, false, true], [true, true, false]] as [$token, $allowed, $enabled]) {
        Joomla\CMS\Session\Session::$valid = $token;
        [$controller, $model, $app, $post] = $fixture($phrase, $allowed, $enabled);
        try {
            $controller->reset();
            throw new LogicException('A safety gate allowed reset');
        } catch (RuntimeException $error) {
            if (($token && $error->getCode() !== 403) || (!$token && $error->getMessage() !== 'Token denied')) throw $error;
        }
        if ($model->resets !== 0 || $post->reads !== 0 || ((!$token || !$allowed) && $controller->modelReads !== 0)) throw new RuntimeException('Reset touched model/input before safety gates');
    }
    echo 'PASS: ' . count($invalid) . " malformed confirmations rejected; exact text accepted; CSRF/ACL/disabled gates precede destructive work.\n";
}
