<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

final class VenuesController extends AdminController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_VENUES';

	public function getModel($name = 'Venue', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
