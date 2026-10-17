<?php
declare(strict_types=1);

namespace Joomla\CMS\User {
	class User {
		public static array $levels = [1, 7];
		public function getAuthorisedViewLevels(): array { return self::$levels; }
	}
}
namespace Joomla\CMS\Router {
	class SiteRouter {
		public function __construct(object $app, object $menu) {}
		public function build(string $url): object { return new \Joomla\CMS\Uri\Uri($url); }
	}
}
namespace Joomla\CMS\Application {
	class SiteApplication { public function getLanguage(): object { return new \stdClass(); } }
}
namespace Joomla\CMS {
	class Factory { public static function getApplication(): object { return new \Joomla\CMS\Application\SiteApplication(); } }
}
namespace Joomla\CMS\Menu {
	class SiteMenu {
		public function __construct(array $options) {
			if (!$options['user'] instanceof \Joomla\CMS\User\User) throw new \RuntimeException('Guest menu required.');
		}
	}
}
namespace Joomla\CMS\Uri {
	class Uri {
		public function __construct(private string $url = '') {}
		public static function getInstance(): self { return new self(); }
		public function getScheme(): string { return 'https'; }
		public function getHost(): string { return 'example.test'; }
		public function getPort(): ?int { return null; }
		public function setScheme(string $value): void {}
		public function setHost(string $value): void {}
		public function setPort(?int $value): void {}
		public function toString(array $parts): string { return 'https://example.test/' . $this->url; }
	}
}
namespace Joomla\Database {
	interface DatabaseInterface {}
	class ParameterType { public const INTEGER = 1; }
}
namespace {
	define('_JEXEC', 1);
	require __DIR__ . '/../../components/com_joomleague/src/Service/PublicUrlProvider.php';
	$query = new class {
		public array $conditions = [];
		public function __call(string $method, array $arguments): self {
			if ($method === 'where') $this->conditions[] = $arguments[0];
			return $this;
		}
	};
	$db = new class($query) implements \Joomla\Database\DatabaseInterface {
		public function __construct(public object $query) {}
		public function getQuery(bool $new): object { $this->query->conditions = []; return $this->query; }
		public function quoteName(string $name, ?string $alias = null): string { return $name . ($alias ? ' AS ' . $alias : ''); }
		public function setQuery(object $query, int $offset, int $limit): self {
			if ($offset !== 0 || $limit > 1000) throw new \RuntimeException('Unbounded batch.');
			return $this;
		}
		public function loadColumn(): array { return [3, 9]; }
	};
	$provider = new \Joomleague\Component\Joomleague\Site\Service\PublicUrlProvider($db);
	foreach ($provider->types() as $type) {
		$rows = $provider->read($type, 2, 10);
		if (array_column($rows, 'id') !== [3, 9] || $rows[0]['lastmod'] !== null) throw new \RuntimeException('Invalid discovery output.');
		$expected = in_array($type, ['project', 'eventreport'], true) ? ['competition.access', 'season.access', ($type === 'project' ? 'item' : 'project') . '.access'] : ['item.access'];
		foreach ($expected as $column) {
			if (!in_array($column . ' IN (1,7)', $query->conditions, true)) throw new \RuntimeException('Missing anonymous parent ACL: ' . $column);
		}
	}
	foreach ([['users', 0, 10], ['club', -1, 10], ['club', 0, 1001]] as $invalid) {
		try { $provider->read(...$invalid); } catch (\InvalidArgumentException) { continue; }
		throw new \RuntimeException('Invalid batch accepted.');
	}
	\Joomla\CMS\User\User::$levels = [];
	if ($provider->read('club') !== []) throw new \RuntimeException('Empty guest access must fail closed.');
	echo "Public URL provider batch and anonymous ACL contract OK\n";
}
