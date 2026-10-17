<?php
declare(strict_types=1);
namespace Joomla\CMS\MVC\Controller {
    class FormController {
        protected $app;
        protected $input;
        public function inject($app, $input): void { $this->app = $app; $this->input = $input; }
    }
}
namespace Joomla\CMS {
    class Factory {
        public static $container;
        public static function getContainer() { return self::$container; }
    }
}
namespace Joomla\CMS\MVC\Model {
    class AdminModel {
        public function getDatabase() { return \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class); }
    }
}
namespace Joomla\CMS\User {
    interface CurrentUserInterface {}
    trait CurrentUserTrait {
        private $currentUser;
        public function setCurrentUser($user): void { $this->currentUser = $user; }
        public function getCurrentUser() { return $this->currentUser; }
    }
}
namespace Joomla\Database {
    interface DatabaseInterface {}
    class ParameterType { public const INTEGER = 'integer'; }
}
namespace Joomleague\Component\Joomleague\Administrator\Service {
    class ProjectEntryContextRepository {
        public function __construct($db) {}
        public function get($id): object { return (object) ['project_id' => $id === 10 ? 1 : ($id === 99 ? 2 : 0)]; }
    }
    class MatchProjectResolver {
        public function __construct($db) {}
        public function __call($method, $args): int { return $args[0] === 99 ? 2 : ($args[0] === 10 ? 1 : 0); }
    }
}
namespace {
    define('_JEXEC', 1);
    $db = new class {
        public int $id = 0;
        public function getQuery($new) { return $this; }
        public function quoteName($name) { return $name; }
        public function select($v) { return $this; }
        public function from($v) { return $this; }
        public function where($v) { return $this; }
        public function bind($key, &$value, $type) { $this->id = $value; return $this; }
        public function setQuery($query) { return $this; }
        public function loadResult(): int { return in_array($this->id, [10, 99], true) ? $this->id : 0; }
    };
    Joomla\CMS\Factory::$container = new class($db) {
        public function __construct(private $db) {}
        public function get($class) { return $this->db; }
    };
    $user = new class {
        public function authorise($action, $asset): bool { return in_array($asset, ['com_joomleague.project.1', 'com_joomleague'], true); }
    };
    $app = new class($user) {
        public function __construct(private $user) {}
        public function getIdentity() { return $this->user; }
    };
    $input = new class {
        public int $projectId = 1;
        public function getInt($key, $default = 0): int { return $this->projectId; }
        public function get($key, $default, $filter) { return $default; }
    };
    foreach (['Match', 'Round', 'Stage', 'Projectentry', 'Standingadjustment', 'Entrymember', 'Stagetransition'] as $name) {
        require dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Controller/' . $name . 'Controller.php';
        $class = 'Joomleague\\Component\\Joomleague\\Administrator\\Controller\\' . $name . 'Controller';
        $controller = new $class();
        $controller->inject($app, $input);
        $method = new ReflectionMethod($controller, 'allowEdit');
        foreach ([[99, false], [10, true], [777, false], [0, false]] as [$id, $expected]) {
            if ($method->invoke($controller, ['id' => $id, 'project_id' => 1, 'entry_id' => 10]) !== $expected) {
                throw new RuntimeException($name . ': stored-owner check failed for ID ' . $id);
            }
        }
        if (!$method->invoke($controller, ['record_id' => 10, 'project_id' => 2, 'entry_id' => 99], 'record_id')) {
            throw new RuntimeException($name . ': custom record key or permitted owner failed.');
        }
        echo 'PASS: ' . $name . ' uses stored owner and denies missing records' . "\n";
        if (in_array($name, ['Match', 'Round', 'Entrymember'], true)) {
            $parentKey = match ($name) { 'Match' => 'round_id', 'Round' => 'stage_id', default => 'entry_id' };
            $add = new ReflectionMethod($controller, 'allowAdd');
            foreach ([[99, false], [10, true], [777, false], [0, false]] as [$parentId, $expected]) {
                if ($add->invoke($controller, [$parentKey => $parentId, 'project_id' => 1]) !== $expected) {
                    throw new RuntimeException($name . ': create ownership failed.');
                }
            }
            echo 'PASS: ' . $name . ' creation authorizes the stored parent owner' . "\n";
        }
        if (in_array($name, ['Stage', 'Projectentry', 'Standingadjustment', 'Stagetransition'], true)) {
            $add = new ReflectionMethod($controller, 'allowAdd');
            foreach ([[1,true],[2,false],[0,false],[-1,false]] as [$projectId,$expected]) {
                if ($add->invoke($controller, ['project_id'=>$projectId]) !== $expected) throw new RuntimeException($name . ': missing/foreign creation project did not fail closed.');
            }
            $input->projectId=0;
            if ($add->invoke($controller, []) !== false) throw new RuntimeException($name . ': missing creation context accepted.');
            $input->projectId=1;
            echo 'PASS: ' . $name . ' requires a positive creation project without component fallback' . "\n";
        }
        require dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Model/' . $name . 'Model.php';
        $class = 'Joomleague\\Component\\Joomleague\\Administrator\\Model\\' . $name . 'Model';
        $model = new $class();$model->setCurrentUser($user);
        foreach (['canDelete','canEditState'] as $action) {
            $check = new ReflectionMethod($model, $action);
            foreach ([[1,true],[2,false],[0,false],[-1,false]] as [$projectId,$expected]) {
                $record=(object)['project_id'=>$projectId,'entry_id'=>match($projectId){1=>10,2=>99,default=>0}];
                if ($check->invoke($model,$record)!==$expected) throw new RuntimeException($name . '::' . $action . ': missing/foreign owner accepted.');
            }
        }
        echo 'PASS: ' . $name . ' delete/state requires resolved project ownership' . "\n";
        $column = match ($name) { 'Round' => 'stage_id', 'Match' => 'round_id', 'Entrymember' => 'entry_id', default => 'project_id' };
        $reorder = new ReflectionMethod($model, 'getReorderConditions');
        foreach ([1, 0, -1] as $ownerId) {
            $expected = [$ownerId > 0 ? $column . ' = ' . $ownerId : '1 = 0'];
            if ($reorder->invoke($model, (object) [$column => $ownerId]) !== $expected) {
                throw new RuntimeException($name . ': ordering scope escaped its stored owner.');
            }
        }
        echo 'PASS: ' . $name . ' ordering remains scoped to its stored parent' . "\n";
    }
}
