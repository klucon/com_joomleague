<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class InstalledVersionProvider
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function get(): string
	{
		foreach ([['package', 'pkg_joomleague'], ['component', 'com_joomleague']] as [$type, $element]) {
			$query = $this->database->getQuery(true)
				->select($this->database->quoteName('manifest_cache'))
				->from($this->database->quoteName('#__extensions'))
				->where($this->database->quoteName('type') . ' = :type')
				->where($this->database->quoteName('element') . ' = :element')
				->bind(':type', $type, ParameterType::STRING)
				->bind(':element', $element, ParameterType::STRING);

			$manifest = json_decode((string) $this->database->setQuery($query)->loadResult(), true);
			$version = is_array($manifest) ? trim((string) ($manifest['version'] ?? '')) : '';

			if ($version !== '') {
				return $version;
			}
		}

		return '';
	}
}
