<?php

declare(strict_types=1);

namespace Joomla\CMS\Uri {
	final class Uri { public static function root(bool $pathOnly = false): string { return '/joomla'; } }
}
namespace Joomla\CMS\Component {
	final class ComponentHelper {
		public static array $params = [];
		public static function getParams(string $component): object {
			return new class { public function get(string $key, mixed $default = null): mixed { return ComponentHelper::$params[$key] ?? $default; } };
		}
	}
}
namespace {
	define('_JEXEC', 1);
	define('JPATH_ROOT', sys_get_temp_dir() . '/jl-media-' . bin2hex(random_bytes(8)));
	require __DIR__ . '/../../administrator/components/com_joomleague/src/Service/MediaPathResolver.php';
	require __DIR__ . '/../../components/com_joomleague/src/Service/MediaResolver.php';
	require __DIR__ . '/../../administrator/components/com_joomleague/script.php';
	use Joomla\CMS\Component\ComponentHelper;
	use Joomleague\Component\Joomleague\Domain\Service\MediaPathResolver;
	use Joomleague\Component\Joomleague\Site\Service\MediaResolver;
	function check(bool $condition, string $message): void { if (!$condition) throw new \RuntimeException($message); }
	mkdir(JPATH_ROOT, 0700);
	$outside = JPATH_ROOT . '-outside';
	mkdir($outside, 0700);
	try {
		$installer = new \Com_JoomleagueInstallerScript();
		$method = new \ReflectionMethod($installer, 'ensureMediaDirectories');
		$method->invoke($installer);
		foreach (MediaPathResolver::DIRECTORIES as $directory) check(is_dir(JPATH_ROOT . '/images/com_joomleague/' . $directory), 'Missing fresh-install directory: ' . $directory);
		file_put_contents(JPATH_ROOT . '/images/com_joomleague/clubs/own.png', 'original-user-image');
		$hash = hash_file('sha256', JPATH_ROOT . '/images/com_joomleague/clubs/own.png');
		$method->invoke($installer);
		check($hash === hash_file('sha256', JPATH_ROOT . '/images/com_joomleague/clubs/own.png'), 'Update modified user image.');
		ComponentHelper::$params = ['images_base_path' => 'images/jl-custom'];
		$method->invoke($installer);
		foreach (['club' => 'logo', 'person' => 'picture', 'team' => 'picture', 'venue' => 'picture'] as $kind => $suffix) {
			$file = 'images/jl-custom/placeholders/' . $kind . '.png';
			file_put_contents(JPATH_ROOT . '/' . $file, $kind);
			ComponentHelper::$params['placeholder_' . $kind . '_' . $suffix] = $file . '#joomlaImage://local-images/test?width=1&height=1';
			check(MediaResolver::image($kind, 'missing.png') === '/joomla/' . $file, 'Configured fallback: ' . $kind);
		}
		file_put_contents(JPATH_ROOT . '/images/jl-custom/clubs/crest.png', 'crest');
		file_put_contents(JPATH_ROOT . '/images/jl-custom/clubs/český znak.png', 'utf8');
		check(MediaResolver::image('club', 'clubs/crest.png') === '/joomla/images/jl-custom/clubs/crest.png', 'Custom base resolution.');
		check(MediaResolver::image('club', 'images/com_joomleague/clubs/crest.png') === '/joomla/images/jl-custom/clubs/crest.png', 'Legacy/custom equivalence.');
		check(MediaResolver::image('club', 'images/com_joomleague/clubs/own.png') === '/joomla/images/com_joomleague/clubs/own.png', 'Existing original path changed.');
		check(MediaResolver::image('', '/joomla/images/jl-custom/clubs/crest.png') === '/joomla/images/jl-custom/clubs/crest.png', 'Subdirectory duplicated.');
		check(MediaResolver::image('', 'clubs/český znak.png') === '/joomla/images/jl-custom/clubs/' . rawurlencode('český znak.png'), 'Unicode/space URL encoding.');
		$team = ['entry_kind' => 'team', 'team_logo' => 'missing.png', 'club_logo' => 'clubs/crest.png'];
		check(MediaResolver::participantImage($team) === '/joomla/images/jl-custom/clubs/crest.png', 'Club inheritance.');
		check(MediaResolver::participantImage(['entry_kind' => 'group']) === '', 'Group inherited unrelated fallback.');
		check(MediaResolver::participantImage(['entry_kind' => 'person']) === '/joomla/images/jl-custom/placeholders/person.png', 'Individual fallback.');
		$paths = new MediaPathResolver(JPATH_ROOT, '/joomla', 'images/jl-custom');
		file_put_contents($outside . '/secret.png', 'secret');
		symlink($outside, JPATH_ROOT . '/images/jl-custom/escape');
		foreach (['../secret.png', '%2e%2e/secret.png', 'images/jl-custom/escape/secret.png', 'javascript:x.png', '//host/image.png', 'images\\x.png', 'https://user:password@host.test/x.png', 'images/jl-custom/clubs/crest.png%00'] as $path) check($paths->resolve([$path]) === '', 'Unsafe image accepted: ' . $path);
		foreach (['../images', '/images/foo', 'images/../foo', 'images//foo', 'images/foo.php', 'media/test'] as $base) {
			try { MediaPathResolver::basePath($base); throw new \RuntimeException('Unsafe base accepted: ' . $base); } catch (\InvalidArgumentException) {}
		}
		try { MediaPathResolver::ensureDirectories(JPATH_ROOT, 'images/jl-custom/escape'); throw new \RuntimeException('Escaping directory accepted.'); } catch (\InvalidArgumentException) {}
		echo "PASS: fresh installation/update directories, custom base, all four configured placeholders, inheritance, Joomla metadata, Unicode, subdirectory URLs, user-file preservation and path/symlink safety.\n";
	} finally {
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(JPATH_ROOT, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($iterator as $entry) { if ($entry->isLink() || $entry->isFile()) unlink($entry->getPathname()); else rmdir($entry->getPathname()); }
		rmdir(JPATH_ROOT);
		if (is_file($outside . '/secret.png')) unlink($outside . '/secret.png');
		rmdir($outside);
	}
}
