<?php

declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\SportCategoryService;

final class SportcategoriesModel extends BaseDatabaseModel
{
	public function getItems(): array
	{
		return (new SportCategoryService($this->getDatabase()))->categories();
	}
}
