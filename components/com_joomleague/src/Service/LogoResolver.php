<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\Service;
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Domain\Service\MediaPathResolver;

final class LogoResolver
{
	public static function resolve(?string ...$paths): string
	{
		return (new MediaPathResolver(JPATH_ROOT, Uri::root(true)))->resolve($paths);
	}
}
