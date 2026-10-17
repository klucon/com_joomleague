<?php

declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\SportVariantService;

final class SportvariantsModel extends BaseDatabaseModel
{
	public function service(): SportVariantService
	{
		return new SportVariantService($this->getDatabase());
	}
}
