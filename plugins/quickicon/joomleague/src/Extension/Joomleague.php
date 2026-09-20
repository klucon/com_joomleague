<?php

namespace Joomleague\Plugin\Quickicon\Joomleague\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
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
		$version = $this->getInstalledVersion();
		$result[] = [[
			'link' => 'index.php?option=com_joomleague&view=dashboard',
			'image' => 'icon-trophy',
			'icon' => '',
			'text' => $version === ''
				? Text::_('PLG_QUICKICON_JOOMLEAGUE_DASHBOARD')
				: Text::sprintf('PLG_QUICKICON_JOOMLEAGUE_DASHBOARD_VERSION', $version),
			'id' => 'plg_quickicon_joomleague',
			'group' => 'MOD_QUICKICON_SITE',
		]];

		$event->setArgument('result', $result);
	}

	private function getInstalledVersion(): string
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);

		foreach ([['package', 'pkg_joomleague'], ['component', 'com_joomleague']] as [$type, $element]) {
			$query = $database->getQuery(true)
				->select($database->quoteName('manifest_cache'))
				->from($database->quoteName('#__extensions'))
				->where($database->quoteName('type') . ' = :type')
				->where($database->quoteName('element') . ' = :element')
				->bind(':type', $type, ParameterType::STRING)
				->bind(':element', $element, ParameterType::STRING);
			$manifest = json_decode((string) $database->setQuery($query)->loadResult(), true);
			$version = is_array($manifest) ? trim((string) ($manifest['version'] ?? '')) : '';

			if ($version !== '') {
				return $version;
			}
		}

		return '';
	}
}
