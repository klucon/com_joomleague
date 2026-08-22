<?php

declare(strict_types=1);

namespace Joomleague\Plugin\Console\Joomleague\Extension;

defined('_JEXEC') or die;

use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;
use Joomleague\Plugin\Console\Joomleague\Console\ResetDemoDataCommand;

final class Joomleague extends CMSPlugin implements SubscriberInterface
{
	public static function getSubscribedEvents(): array
	{
		return [ApplicationEvents::BEFORE_EXECUTE => 'registerCommands'];
	}

	public function registerCommands(): void
	{
		$this->getApplication()->addCommand(
			new ResetDemoDataCommand(Factory::getContainer()->get(DatabaseInterface::class))
		);
	}
}
