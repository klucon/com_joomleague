<?php
declare(strict_types=1);
namespace Joomla\CMS\Uri {
	final class Uri { public static function root(bool $pathOnly = false): string { return '/joomla'; } }
}
namespace {
	define('_JEXEC', 1);
	define('JPATH_ROOT', sys_get_temp_dir() . '/jl-logo-test-' . bin2hex(random_bytes(6)));
	require __DIR__ . '/../../administrator/components/com_joomleague/src/Service/MediaPathResolver.php';
	require __DIR__ . '/../../components/com_joomleague/src/Service/LogoResolver.php';
	use Joomleague\Component\Joomleague\Site\Service\LogoResolver;
	mkdir(JPATH_ROOT . '/images/com_joomleague/database/clubs/large', 0700, true);
	$path = 'images/com_joomleague/database/clubs/large/crest.png';
	file_put_contents(JPATH_ROOT . '/' . $path, 'fixture');
	try {
		$cases = [
			[[$path], '/joomla/' . $path],
			[[$path . '#joomlaImage://local-images/crest.png?width=100&height=100'], '/joomla/' . $path],
			[['media/com_joomleague/clubs/large/crest.png'], '/joomla/' . $path],
			[['missing.png', $path], '/joomla/' . $path],
			[['images/com_joomleague/database/placeholders/placeholder_450_2.png', $path], '/joomla/' . $path],
			[['https://example.org/crest.png'], 'https://example.org/crest.png'],
			[['../secret', '%2e%2e/secret', '//example.org/a', 'javascript:alert(1)'], ''],
		];
		foreach ($cases as [$input, $expected]) if (LogoResolver::resolve(...$input) !== $expected) throw new \RuntimeException('Logo resolution failed: ' . json_encode($input));
		echo "PASS: SEF/subdirectory URLs, existing-file legacy fallback, inherited logo, placeholders and unsafe paths.\n";
	} finally {
		unlink(JPATH_ROOT . '/' . $path);
		for ($dir = dirname(JPATH_ROOT . '/' . $path); $dir !== dirname(JPATH_ROOT); $dir = dirname($dir)) rmdir($dir);
	}
}
