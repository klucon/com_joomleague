<?php
namespace Klucon\Plugin\Kluconcountries\Joomleague\Extension;
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

final class Joomleague extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array { return ['onKluconCountriesResolve' => 'resolve']; }
    public function resolve(Event $event): void
    {
        if ($event->getArgument('consumer') !== 'com_joomleague' || !ComponentHelper::isEnabled('com_kluconcountries')) return;
        $code = $event->getArgument('code');
        $language = $event->getArgument('language');
        if (!is_string($code) || !is_string($language)) return;
        try {
            $component = $this->getApplication()->bootComponent('com_kluconcountries');
            if (method_exists($component, 'getCountry')) $event->setArgument('country', $component->getCountry($code, $language));
        } catch (\Throwable $error) {
            // Optional integration must never make the consuming page unavailable.
            return;
        }
    }
}
