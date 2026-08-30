<?php

declare(strict_types=1);

namespace Joomleague\Module\Program\Site\Dispatcher;

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
			$this->module->title = Text::_('MOD_JOOMLEAGUE_PROGRAM');
		}

		$data = parent::getLayoutData();
		$data['programme'] = $this->getHelperFactory()->getHelper('ProgramHelper')->getProgramme($data['params']);

		return $data;
	}
}
