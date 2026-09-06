<?php
declare(strict_types=1);defined('_JEXEC')or die;
use Joomla\CMS\Extension\Service\Provider\HelperFactory;use Joomla\CMS\Extension\Service\Provider\Module;use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;use Joomla\DI\Container;use Joomla\DI\ServiceProviderInterface;
return new class()implements ServiceProviderInterface{public function register(Container$c):void{$c->registerServiceProvider(new ModuleDispatcherFactory('\\Joomleague\\Module\\Birthdays'));$c->registerServiceProvider(new HelperFactory('\\Joomleague\\Module\\Birthdays\\Site\\Helper'));$c->registerServiceProvider(new Module());}};
