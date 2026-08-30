<?php

declare(strict_types=1);

namespace Joomleague\Module\NextEvent\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Language\Text;

final class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
	use HelperFactoryAwareTrait;

	protected function getLayoutData(): array
	{
		if ($this->module->title === $this->module->module) {
			$this->module->title = Text::_('MOD_JOOMLEAGUE_NEXT_EVENT');
		}

		$data = parent::getLayoutData();
		$data['event'] = $this->getHelperFactory()->getHelper('NextEventHelper')->getEvent($data['params']);

		return $data;
	}
}
