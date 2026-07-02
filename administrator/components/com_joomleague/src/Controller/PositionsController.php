<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

final class PositionsController extends AdminController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_POSITIONS';

	public function getModel($name = 'Position', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
