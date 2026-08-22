<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

final class PersonsController extends AdminController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_PERSONS';

	public function getModel($name = 'Person', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
