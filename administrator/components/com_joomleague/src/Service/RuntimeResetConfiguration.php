<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use RuntimeException;

final class RuntimeResetConfiguration
{
	public const PARAMETER = 'allow_full_data_reset';

	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function isEnabled(): bool
	{
		return (int) ComponentHelper::getParams('com_joomleague')->get(self::PARAMETER, 0) === 1;
	}

	public function disable(): void
	{
		$type = 'component';
		$element = 'com_joomleague';
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName(['extension_id', 'params']))
			->from($this->database->quoteName('#__extensions'))
			->where($this->database->quoteName('type') . ' = :type')
			->where($this->database->quoteName('element') . ' = :element')
			->bind(':type', $type)
			->bind(':element', $element);
		$row = $this->database->setQuery($query)->loadObject();

		if ($row === null) {
			throw new RuntimeException('The JoomLeague component configuration could not be loaded.');
		}

		$params = new Registry((string) $row->params);
		$params->set(self::PARAMETER, 0);
		$extensionId = (int) $row->extension_id;
		$json = $params->toString();
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__extensions'))
			->set($this->database->quoteName('params') . ' = :params')
			->where($this->database->quoteName('extension_id') . ' = :extensionId')
			->bind(':params', $json)
			->bind(':extensionId', $extensionId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();
	}
}
