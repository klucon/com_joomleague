<?php
defined('_JEXEC') or die;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Klucon\Plugin\Kluconcountries\Joomleague\Extension\Joomleague;
return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function () {
            $plugin = new Joomleague((array) PluginHelper::getPlugin('kluconcountries', 'joomleague'));
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        });
    }
};
