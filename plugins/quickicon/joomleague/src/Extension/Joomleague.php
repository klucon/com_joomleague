<?php

/**
 * @package     Klucon.Plugin
 * @subpackage  Quickicon.joomleague
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Plugin\Quickicon\Joomleague\Extension;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Adds a JoomLeague quick icon to the administrator control panel.
 *
 * @since  1.0.0
 */
final class Joomleague extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load the language file on instantiation.
     *
     * @var  boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return ['onGetIcons' => 'onGetIcons'];
    }

    /**
     * Add the JoomLeague control panel icon.
     *
     * @param   QuickIconsEvent  $event  The event object.
     *
     * @return  void
     */
    public function onGetIcons(QuickIconsEvent $event): void
    {
        if ($event->getContext() !== $this->params->get('context', 'mod_quickicon')) {
            return;
        }

        if (!$this->getApplication()->getIdentity()->authorise('core.manage', 'com_joomleague')) {
            return;
        }

        $result   = $event->getArgument('result', []);
        $result[] = [
            [
                'link'  => 'index.php?option=com_joomleague',
                'image' => 'icon-trophy',
                'icon'  => '',
                'text'  => Text::_('PLG_QUICKICON_JOOMLEAGUE_TEXT'),
                'id'    => 'plg_quickicon_joomleague',
                'group' => 'MOD_QUICKICON_COMPONENTS',
            ],
        ];

        $event->setArgument('result', $result);
    }
}
