<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class SystemDiagnosticsService
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/** @return array<string, mixed> */
	public function inspect(): array
	{
		$config = Factory::getApplication()->getConfig();
		$paths = [
			'tmp' => (string) $config->get('tmp_path'),
			'log' => (string) $config->get('log_path'),
			'cache' => JPATH_ADMINISTRATOR . '/cache',
		];
		$writable = [];
		foreach ($paths as $code => $path) $writable[$code] = $path !== '' && is_dir($path) && is_writable($path);

		return [
			'driver' => $this->database->getName(),
			'database_version' => $this->database->getVersion(),
			'table_count' => count(ComponentTableCatalog::installed($this->database)),
			'component_version' => '6.2.0-dev1',
			'php_version' => PHP_VERSION,
			'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
			'post_max_size' => (string) ini_get('post_max_size'),
			'memory_limit' => (string) ini_get('memory_limit'),
			'max_execution_time' => (string) ini_get('max_execution_time'),
			'writable' => $writable,
			'demo_reset_enabled' => getenv('JOOMLEAGUE_ALLOW_DEMO_RESET') === '1',
		];
	}
}
