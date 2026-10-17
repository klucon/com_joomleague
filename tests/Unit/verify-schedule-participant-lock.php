<?php
declare(strict_types=1);

namespace Joomla\Database {
	interface DatabaseInterface {}
	class ParameterType { public const INTEGER = 1; }
}
namespace {
	define('_JEXEC', 1);
	require __DIR__ . '/../../administrator/components/com_joomleague/src/Service/MatchCompetitionDataGuard.php';
	$db = new class implements \Joomla\Database\DatabaseInterface {
		public int $reads = 0;
		public object $query;
		public function getQuery(bool $new): object {
			return new class {
				public string $table = '';
				public string $condition = '';
				public function select(string $value): self { return $this; }
				public function from(string $value): self { $this->table = $value; return $this; }
				public function where(string $value): self { $this->condition = $value; return $this; }
			};
		}
		public function quoteName(string $name): string { return $name; }
		public function setQuery(object $query): self { $this->query = $query; return $this; }
		public function loadColumn(): array {
			if ($this->query->condition !== 'match_id IN (1,2,3,4,5)') throw new \RuntimeException('IDs must be normalized and scoped.');
			return [++$this->reads];
		}
	};
	$guard = new \Joomleague\Component\Joomleague\Administrator\Service\MatchCompetitionDataGuard($db);
	if ($guard->lockedMatchIds([]) !== [] || $guard->lockedMatchIds([0, -1]) !== [] || $db->reads !== 0) throw new \RuntimeException('Empty batch queried.');
	$locked = $guard->lockedMatchIds([1, 2, 3, 4, 5, 1, 0, -1]);
	if (array_keys($locked) !== [1, 2, 3, 4] || isset($locked[5]) || $db->reads !== 4) throw new \RuntimeException('Competition data lock failed.');
	$template = file_get_contents(__DIR__ . '/../../administrator/components/com_joomleague/tmpl/matches/default.php');
	if (substr_count($template, "\$canEditParticipants ? '' : 'disabled'") !== 2) throw new \RuntimeException('Both participant selectors must respect the lock.');
	foreach (['en-GB', 'cs-CZ'] as $language) {
		$strings = parse_ini_file(__DIR__ . '/../../administrator/components/com_joomleague/language/' . $language . '/com_joomleague.ini');
		if (empty($strings['COM_JOOMLEAGUE_MATCH_SCHEDULE_PARTICIPANTS_LOCKED'])) throw new \RuntimeException('Missing lock translation.');
	}
	echo "Schedule participant lock: all four data types, empty matches, batch scope and translations OK\n";
}
