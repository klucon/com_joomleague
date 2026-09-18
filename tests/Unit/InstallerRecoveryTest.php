<?php

declare(strict_types=1);

namespace Joomla\Database {
	interface DatabaseInterface {}
	final class ParameterType { public const INTEGER = 1; }
}

namespace Joomla\CMS\Installer\Adapter {
	class ComponentAdapter {}
}

namespace Joomla\Filesystem {
	final class File {}
}

namespace Joomla\CMS\Language {
	final class Text
	{
		public static function sprintf(string $key, mixed ...$arguments): string
		{
			return $key . ':' . implode('|', array_map('strval', $arguments));
		}
	}
}

namespace Joomla\CMS {
	final class Factory
	{
		public static object $container;
		public static object $application;
		public static function getContainer(): object { return self::$container; }
		public static function getApplication(): object { return self::$application; }
	}
}

namespace {
	define('_JEXEC', 1);
	require dirname(__DIR__, 2) . '/administrator/components/com_joomleague/script.php';

	final class FakeApplication
	{
		public array $messages = [];
		public function enqueueMessage(string $message, string $type): void { $this->messages[] = [$message, $type]; }
	}

	final class FakeContainer
	{
		public function __construct(private FakeDatabase $database) {}
		public function get(string $class): FakeDatabase { return $this->database; }
	}

	final class FakeDatabase implements \Joomla\Database\DatabaseInterface
	{
		public string $query = '';
		public array $executed = [];
		public function __construct(public array $tables, public array $counts = [], private string $driver = 'mysqli') {}
		public function getTableList(): array { return $this->tables; }
		public function replacePrefix(string $table): string { return str_replace('#__', 'test_', $table); }
		public function quoteName(string $name): string { return '`' . $name . '`'; }
		public function getName(): string { return $this->driver; }
		public function setQuery(string $query): self { $this->query = $query; return $this; }
		public function loadResult(): int
		{
			preg_match('/FROM [`"]([^`"]+)[`"]/', $this->query, $match);
			return $this->counts[$match[1] ?? ''] ?? 0;
		}
		public function execute(): self { $this->executed[] = $this->query; return $this; }
	}

	$reflection = new ReflectionClass(Com_JoomleagueInstallerScript::class);
	$tables = array_map(static fn (string $suffix): string => 'test_joomleague_' . $suffix, $reflection->getConstant('SCHEMA_TABLES'));
	$method = $reflection->getMethod('prepareIncompleteInstallation');
	$installer = new Com_JoomleagueInstallerScript();

	$run = static function (FakeDatabase $database) use ($method, $installer): array {
		\Joomla\CMS\Factory::$container = new FakeContainer($database);
		\Joomla\CMS\Factory::$application = new FakeApplication();
		$result = $method->invoke($installer);
		return [$result, $database->executed, \Joomla\CMS\Factory::$application->messages];
	};

	[$cleanResult, $cleanQueries] = $run(new FakeDatabase([]));
	if (!$cleanResult || $cleanQueries !== []) throw new RuntimeException('A clean install must continue without database mutations.');

	[$emptyResult, $emptyQueries, $emptyMessages] = $run(new FakeDatabase(array_slice($tables, 0, 12)));
	if (!$emptyResult || count(array_filter($emptyQueries, static fn (string $query): bool => str_starts_with($query, 'DROP TABLE'))) !== 1) {
		throw new RuntimeException('An empty partial schema must be removed exactly once.');
	}
	if (($emptyMessages[0][1] ?? '') !== 'warning') throw new RuntimeException('Recovered partial schema must produce a warning.');

	[$dataResult, $dataQueries, $dataMessages] = $run(new FakeDatabase(array_slice($tables, 0, 12), [$tables[0] => 1]));
	if ($dataResult || array_filter($dataQueries, static fn (string $query): bool => str_starts_with($query, 'DROP TABLE')) !== []) {
		throw new RuntimeException('A partial schema containing data must be preserved and installation blocked.');
	}
	if (($dataMessages[0][1] ?? '') !== 'error') throw new RuntimeException('Blocked recovery must produce an error.');

	echo "Installer incomplete-schema recovery OK\n";
}
