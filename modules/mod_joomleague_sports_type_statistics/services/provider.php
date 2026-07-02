<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_ranking
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Joomleague\\Module\\SportsTypeStatistics'));
        $container->registerServiceProvider(new HelperFactory('\\Joomleague\\Module\\SportsTypeStatistics\\Site\\Helper'));
        $container->registerServiceProvider(new Module());
    }
};
