<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class DatabasetoolController extends BaseController
{
	public function optimize(): void
	{
		$this->run('optimize', 'COM_JOOMLEAGUE_DBTOOLS_OPTIMIZE_SUCCESS');
	}

	public function repair(): void
	{
		$this->run('repair', 'COM_JOOMLEAGUE_DBTOOLS_REPAIR_SUCCESS');
	}

	private function run(string $method, string $messageKey): void
	{
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.admin', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$count = (int) $this->getModel('Databasetools')->{$method}();
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=databasetools', false), Text::sprintf($messageKey, $count));
	}
}
