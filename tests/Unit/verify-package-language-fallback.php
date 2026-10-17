<?php
declare(strict_types=1);

namespace Joomla\CMS {
	class Factory {
		public static object $language;
		public static function getApplication(): object {
			return new class { public function getLanguage(): object { return Factory::$language; } };
		}
	}
}
namespace Joomla\CMS\Installer {
	class InstallerAdapter {
		public function getParent(): object {
			return new class { public function getPath(string $name): string { return '/package'; } };
		}
	}
}
namespace {
	define('_JEXEC', 1);
	define('JPATH_SITE', '/site');
	require __DIR__ . '/../../build/pkg_script.php';
	$method = new ReflectionMethod(Pkg_JoomleagueInstallerScript::class, 'loadLanguage');
	foreach (['en-GB', 'cs-CZ', 'nl-NL'] as $tag) {
		$language = new class($tag) {
			public array $calls = [];
			public string $text = '';
			public function __construct(private string $tag) {}
			public function getTag(): string { return $this->tag; }
			public function load(string $extension, string $path, string $tag, bool $reload, bool $default): bool {
				$this->calls[] = $tag;
				if ($tag === 'nl-NL') return false;
				$this->text = $tag;
				return true;
			}
		};
		\Joomla\CMS\Factory::$language = $language;
		$method->invoke(new Pkg_JoomleagueInstallerScript(), new \Joomla\CMS\Installer\InstallerAdapter());
		if ($language->calls[0] !== 'en-GB' || $language->text !== ($tag === 'cs-CZ' ? 'cs-CZ' : 'en-GB')) {
			throw new RuntimeException('Package language fallback failed for ' . $tag);
		}
	}
	echo "Package language fallback OK: en-GB, cs-CZ, nl-NL\n";
}
