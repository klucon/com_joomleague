<?php

/**
 * @package     Joomleague.Site
 * @subpackage  mod_joomleague_standings
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Standings\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected function getLayoutData()
    {
        // A module instance's Title defaults to the raw technical name
        // (e.g. when discovered/duplicated without the admin renaming it) —
        // show the translated module name instead of that literal string.
        // loadLanguage() (called by the parent dispatch() before this runs)
        // already loaded mod_joomleague_standings.ini, which carries this
        // key alongside .sys.ini so both the module edit screen and this
        // render path resolve it.
        if ($this->module->title === $this->module->module) {
            $this->module->title = Text::_('MOD_JOOMLEAGUE_STANDINGS');
        }

        $data = parent::getLayoutData();

        $data['standings'] = $this->getHelperFactory()->getHelper('StandingsHelper')->getStandings($data['params']);

        return $data;
    }
}
