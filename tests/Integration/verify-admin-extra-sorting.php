<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
require JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';
foreach (['Competitions', 'Projects', 'Clubs', 'Teams', 'Persons', 'Venues'] as $modelName) {
    require JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Model/' . $modelName . 'Model.php';
}

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
    ->alias('JSession', 'session.cli')
    ->alias(Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(Joomla\Session\Session::class, 'session.cli')
    ->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
$factory = Factory::getApplication()->bootComponent('com_joomleague')->getMVCFactory();
$database = $container->get(DatabaseInterface::class);
$columns = [
    'Competitions' => ['a.code', 'a.organisation', 'a.country_code', 'project_count'],
    'Projects' => ['competition.name', 'season.name', 'sport_type.name', 'a.project_type', 'a.lifecycle_state', 'override_count'],
    'Clubs' => ['team_count'],
    'Teams' => ['project_count'],
    'Persons' => ['assignment_count'],
    'Venues' => ['a.country_code'],
];

foreach ($columns as $name => $fields) {
    $model = $factory->createModel($name, 'Administrator', ['ignore_request' => true]);
    $model->setState('filter.published', '');
    $model->setState('filter.search', '');
    $allowed = new ReflectionProperty($model, 'filter_fields');
    $queryMethod = new ReflectionMethod($model, 'getListQuery');
    foreach ($fields as $field) {
        if (!in_array($field, $allowed->getValue($model), true)) {
            throw new RuntimeException("$name: $field is not allowed");
        }
        foreach (['ASC', 'DESC'] as $direction) {
            $model->setState('list.ordering', $field);
            $model->setState('list.direction', $direction);
            $query = $queryMethod->invoke($model);
            $rows = $database->setQuery($query, 0, 100)->loadObjectList();
            if (str_ends_with($field, '_count')) {
                $previous = null;
                foreach ($rows as $row) {
                    $value = (int) $row->$field;
                    if ($previous !== null && ($direction === 'ASC' ? $value < $previous : $value > $previous)) {
                        throw new RuntimeException("$name: actual numeric order is incorrect for $field $direction");
                    }
                    $previous = $value;
                }
            }
        }
    }
    echo "$name: additional SQL sorting passed\n";
}
