<?php

/**
 * @package     Klucon.Plugin
 * @subpackage  Extension.joomleagueesport
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Plugin\Extension\Joomleagueesport\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * JoomLeague esport extension plugin.
 *
 * Clean Joomla 6 scaffold replacing the legacy (empty) esport/clan stub.
 * It reacts to the install/update lifecycle of the JoomLeague component and
 * provides a single, documented extension point for esport/clan specific
 * behaviour without touching the component core.
 *
 * @since  1.0.0
 */
final class Joomleagueesport extends CMSPlugin implements SubscriberInterface
{
    /**
     * The JoomLeague component element this plugin extends.
     *
     * @var  string
     */
    private const COMPONENT = 'com_joomleague';

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
        return [
            'onExtensionAfterInstall' => 'onExtensionAfterInstall',
            'onExtensionAfterUpdate'  => 'onExtensionAfterUpdate',
        ];
    }

    /**
     * Triggered after any extension is installed.
     *
     * @param   Event  $event  The installer event.
     *
     * @return  void
     */
    public function onExtensionAfterInstall(Event $event): void
    {
        $this->handleLifecycle($event);
    }

    /**
     * Triggered after any extension is updated.
     *
     * @param   Event  $event  The installer event.
     *
     * @return  void
     */
    public function onExtensionAfterUpdate(Event $event): void
    {
        $this->handleLifecycle($event);
    }

    /**
     * Run esport specific provisioning when the JoomLeague component itself is
     * installed or updated. Kept intentionally side-effect free; this is the
     * documented extension point for esport/clan behaviour.
     *
     * @param   Event  $event  The installer event.
     *
     * @return  void
     */
    private function handleLifecycle(Event $event): void
    {
        $installer = $event->getArgument('installer');

        if (!\is_object($installer) || !method_exists($installer, 'getManifest')) {
            return;
        }

        $manifest = $installer->getManifest();
        $element  = isset($manifest->name) ? (string) $manifest->name : '';

        if ($element !== self::COMPONENT) {
            return;
        }

        // Extension point: esport/clan specific provisioning for JoomLeague.
        // Intentionally left as a no-op scaffold so the component core stays untouched.
    }
}
