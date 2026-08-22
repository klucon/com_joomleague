<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

final class SeasonController extends FormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_SEASON';
	protected $view_list = 'seasons';

	protected function allowAdd($data = []): bool
	{
		return $this->app->getIdentity()->authorise('core.create', 'com_joomleague');
	}

	protected function allowEdit($data = [], $key = 'id'): bool
	{
		return $this->app->getIdentity()->authorise('core.edit', 'com_joomleague');
	}
}
