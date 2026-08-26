<?php

declare(strict_types=1);

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomleague\Component\Joomleague\Administrator\Extension\JoomleagueComponent;

defined('_JEXEC') or die;

return new class implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		// Shared domain layer (read/calculate services with no admin- or site-specific
		// dependency) lives physically under this component's admin src/ folder — the
		// only folder Joomla always keeps installed for both clients from this single
		// manifest — but is exposed under a neutral \Domain namespace so it is reachable
		// from admin controllers, site views and modules alike without either side
		// reaching into the other's \Administrator/\Site namespace. This registration
		// runs on every bootComponent('com_joomleague') call, regardless of client.
		\JLoader::registerNamespace(
			'Joomleague\\Component\\Joomleague\\Domain',
			__DIR__ . '/../src',
			false,
			false,
			'psr4'
		);

		$container->registerServiceProvider(new MVCFactory('\\Joomleague\\Component\\Joomleague'));
		$container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomleague\\Component\\Joomleague'));
		$container->registerServiceProvider(new RouterFactory('\\Joomleague\\Component\\Joomleague'));

		$container->set(
			ComponentInterface::class,
			static function (Container $container): ComponentInterface {
				$component = new JoomleagueComponent($container->get(ComponentDispatcherFactoryInterface::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setRouterFactory($container->get(RouterFactoryInterface::class));

				return $component;
			}
		);
	}
};
