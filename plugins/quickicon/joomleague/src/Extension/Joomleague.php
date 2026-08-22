<?php

namespace Joomleague\Plugin\Quickicon\Joomleague\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent;

defined('_JEXEC') or die;

final class Joomleague extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadLanguage = true;

	public static function getSubscribedEvents(): array
	{
		return ['onGetIcons' => 'onGetIcons'];
	}

	public function onGetIcons(QuickIconsEvent $event): void
	{
		if (
			$event->getContext() !== $this->params->get('context', 'mod_quickicon')
			|| !ComponentHelper::isEnabled('com_joomleague')
			|| !$this->getApplication()->getIdentity()->authorise('core.manage', 'com_joomleague')
		) {
			return;
		}

		$result = $event->getArgument('result', []);
		$result[] = [[
			'link' => 'index.php?option=com_joomleague&view=dashboard',
			'image' => 'icon-trophy',
			'icon' => '',
			'text' => Text::_('PLG_QUICKICON_JOOMLEAGUE_DASHBOARD'),
			'id' => 'plg_quickicon_joomleague',
			'group' => 'MOD_QUICKICON_SITE',
		]];

		$event->setArgument('result', $result);
	}
}
