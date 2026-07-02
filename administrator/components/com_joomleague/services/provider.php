<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\SiteRouter;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomleague\Component\Joomleague\Administrator\Extension\JoomleagueComponent;
use Joomleague\Component\Joomleague\Administrator\MVC\Factory\JoomleagueMVCFactory;
use Joomleague\Component\Joomleague\Administrator\Service\ClubProvisioningService;
use Joomleague\Component\Joomleague\Administrator\Service\ScheduleGeneratorService;
use Joomleague\Component\Joomleague\Administrator\Service\ScheduleTemplateService;
use Joomleague\Component\Joomleague\Administrator\Service\SportsBootstrapService;
use Joomleague\Component\Joomleague\Administrator\Service\TemplateConfigBootstrapService;

\JLoader::registerNamespace('Joomleague\\Component\\Joomleague\\Administrator', JPATH_ADMINISTRATOR . '/components/com_joomleague/src');
\JLoader::registerNamespace('Joomleague\\Component\\Joomleague\\Site', JPATH_SITE . '/components/com_joomleague/src');

if (!class_exists(JoomleagueComponent::class)) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';
}

return new class () implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		$container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomleague\\Component\\Joomleague'));
		$container->registerServiceProvider(new RouterFactory('\\Joomleague\\Component\\Joomleague'));

		$container->set(
			ScheduleTemplateService::class,
			static fn (): ScheduleTemplateService => new ScheduleTemplateService()
		);

		$container->set(
			ScheduleGeneratorService::class,
			static fn (Container $container): ScheduleGeneratorService => new ScheduleGeneratorService(
				$container->get(DatabaseInterface::class),
				$container->get(ScheduleTemplateService::class)
			)
		);

		$container->set(
			ClubProvisioningService::class,
			static fn (Container $container): ClubProvisioningService => new ClubProvisioningService(
				$container->get(DatabaseInterface::class)
			)
		);

		$container->set(
			SportsBootstrapService::class,
			static fn (Container $container): SportsBootstrapService => new SportsBootstrapService(
				$container->get(DatabaseInterface::class)
			)
		);

		$container->set(
			TemplateConfigBootstrapService::class,
			static fn (Container $container): TemplateConfigBootstrapService => new TemplateConfigBootstrapService(
				$container->get(DatabaseInterface::class)
			)
		);

		$container->set(
			MVCFactoryInterface::class,
			static function (Container $container): MVCFactoryInterface {
				if (JPATH_BASE !== JPATH_ADMINISTRATOR) {
					$factory = new MVCFactory('\\Joomleague\\Component\\Joomleague');
					$factory->setFormFactory($container->get(FormFactoryInterface::class));
					$factory->setDispatcher($container->get(DispatcherInterface::class));
					$factory->setDatabase($container->get(DatabaseInterface::class));
					$factory->setSiteRouter($container->get(SiteRouter::class));
					$factory->setCacheControllerFactory($container->get(CacheControllerFactoryInterface::class));
					$factory->setUserFactory($container->get(UserFactoryInterface::class));
					$factory->setMailerFactory($container->get(MailerFactoryInterface::class));

					return $factory;
				}

				$application = $container->get(AdministratorApplication::class);
				$factory = new JoomleagueMVCFactory(
					'\\Joomleague\\Component\\Joomleague',
					$container->get(ClubProvisioningService::class),
					$application,
					$container->get(ScheduleGeneratorService::class),
					$container->get(ScheduleTemplateService::class),
					$container->get(SportsBootstrapService::class)
				);
				$factory->setFormFactory($container->get(FormFactoryInterface::class));
				$factory->setDispatcher($container->get(DispatcherInterface::class));
				$factory->setDatabase($container->get(DatabaseInterface::class));
				$factory->setSiteRouter($container->get(SiteRouter::class));
				$factory->setCacheControllerFactory($container->get(CacheControllerFactoryInterface::class));
				$factory->setUserFactory($container->get(UserFactoryInterface::class));
				$factory->setMailerFactory($container->get(MailerFactoryInterface::class));

				return $factory;
			}
		);

		$container->set(
			ComponentInterface::class,
			static function (Container $container): ComponentInterface {
				$component = new JoomleagueComponent(
					$container->get(ComponentDispatcherFactoryInterface::class)
				);
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setRouterFactory($container->get(RouterFactoryInterface::class));

				return $component;
			}
		);
	}
};
