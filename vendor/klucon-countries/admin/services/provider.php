<?php
defined('_JEXEC') or die;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Database\DatabaseInterface;
use Klucon\Component\Kluconcountries\Administrator\Extension\CountriesComponent;
use Klucon\Component\Kluconcountries\Administrator\Service\CountryDirectory;
use Klucon\Component\Kluconcountries\Administrator\Service\Registry;
return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $namespace = '\\Klucon\\Component\\Kluconcountries';
        $container->registerServiceProvider(new MVCFactory($namespace));
        $container->registerServiceProvider(new ComponentDispatcherFactory($namespace));
        $container->set(ComponentInterface::class, static function (Container $container) {
            $component = new CountriesComponent($container->get(ComponentDispatcherFactoryInterface::class));
            $component->setMVCFactory($container->get(MVCFactoryInterface::class));
            $component->setCountryDirectory(new CountryDirectory(new Registry($container->get(DatabaseInterface::class))));
            return $component;
        });
    }
};
