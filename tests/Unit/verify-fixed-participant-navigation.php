<?php
declare(strict_types=1);
namespace Joomla\CMS {
    class Factory { public static object $app; public static function getApplication(): object { return self::$app; } }
}
namespace Joomla\CMS\Language { class Text { public static function _(string $key): string { return $key; } } }
namespace Joomla\CMS\Router { class Route { public static function _(string $url, bool $escape = true): string { return $url; } } }
namespace Joomla\CMS\MVC\View {
    class GenericDataException extends \RuntimeException {}
    class HtmlView { public object $model; public function getModel(): object { return $this->model; } }
}
namespace {
    define('_JEXEC', 1);
    require __DIR__ . '/../../administrator/components/com_joomleague/src/View/Matchparticipants/HtmlView.php';
    $app = new class {
        public array $messages = []; public string $redirect = '';
        public function getInput(): object { return new class { public function getInt(string $key): int { return 42; } }; }
        public function enqueueMessage(string $text, string $type): void { $this->messages[] = [$text, $type]; }
        public function redirect(string $url): void { $this->redirect = $url; }
    };
    \Joomla\CMS\Factory::$app = $app;
    $view = new \Joomleague\Component\Joomleague\Administrator\View\Matchparticipants\HtmlView();
    $view->model = new class { public function getContext(int $id): array { throw new \UnexpectedValueException('COM_JOOMLEAGUE_ERROR_MATCH_PARTICIPANTS_FIXED'); } };
    $view->display();
    if ($app->redirect !== 'index.php?option=com_joomleague&view=match&layout=edit&id=42' || count($app->messages) !== 1) throw new \RuntimeException('Fixed-slot navigation failed.');
    $app->redirect = '';
    $view->model = new class { public function getContext(int $id): array { throw new \UnexpectedValueException('Unexpected failure'); } };
    try { $view->display(); throw new \LogicException('Unrelated failure suppressed.'); }
    catch (\Joomla\CMS\MVC\View\GenericDataException $error) { if ($error->getCode() !== 500 || $app->redirect !== '') throw new \RuntimeException('Unrelated failure handling changed.'); }
    echo "PASS: fixed-slot navigation selects the match editor without masking unrelated errors.\n";
}
