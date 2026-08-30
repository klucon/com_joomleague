<?php

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomleague\Plugin\Finder\Joomleague\Extension\Joomleague;

return new class () implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		$container->set(PluginInterface::class, $container->lazy(Joomleague::class, static function (Container $container): Joomleague {
			$plugin = new Joomleague((array) PluginHelper::getPlugin('finder', 'joomleague'));
			$plugin->setApplication(Factory::getApplication());
			$plugin->setDatabase($container->get(DatabaseInterface::class));
			return $plugin;
		}));
	}
};
