<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;

final class JoomleagueComponent extends MVCComponent implements RouterServiceInterface
{
	use RouterServiceTrait;
}
