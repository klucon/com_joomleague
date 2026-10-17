<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Exception\RouteNotFoundException;

final class PublicViewGuard
{
	/** @param array<string,mixed> $data */
	public static function requireItem(array $data, string $key): void
	{
		if (!isset($data[$key])) {
			throw new RouteNotFoundException(Text::_('COM_JOOMLEAGUE_ERROR_NOT_FOUND'));
		}
	}
}
