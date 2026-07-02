<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

final class LeaguesController extends AdminController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_LEAGUES';

	public function getModel($name = 'League', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
