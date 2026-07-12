<?php

/**
 * @package     Klucon.Plugin
 * @subpackage  System.joomleaguesefaliases
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\SiteRouter;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomleague\Plugin\System\Joomleaguesefaliases\Extension\Joomleaguesefaliases;

return new class () implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			$container->lazy(Joomleaguesefaliases::class, function (Container $container) {
				$plugin = new Joomleaguesefaliases(
					(array) PluginHelper::getPlugin('system', 'joomleaguesefaliases')
				);
				$plugin->setApplication(Factory::getApplication());
				$plugin->setSiteRouter($container->get(SiteRouter::class));

				return $plugin;
			})
		);
	}
};
