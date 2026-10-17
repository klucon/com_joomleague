<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

/** Conservative compatibility: semantic contract changes require a dedicated migration. */
final class ProfileMigrationPlan
{
	public function compare(array $from, array $to): array
	{
		$changes = [];
		$this->walk($from, $to, '', $changes);
		return $changes;
	}

	private function walk(mixed $from, mixed $to, string $path, array &$changes): void
	{
		if (is_array($from) && is_array($to)) {
			foreach (array_unique(array_merge(array_keys($from), array_keys($to))) as $key) {
				$pointer = $path . '/' . str_replace(['~', '/'], ['~0', '~1'], (string) $key);
				if (!array_key_exists($key, $from) || !array_key_exists($key, $to)) {
					$this->change($pointer, $from[$key] ?? null, $to[$key] ?? null, $changes);
				} else {
					$this->walk($from[$key], $to[$key], $pointer, $changes);
				}
			}
		} elseif ($from !== $to) {
			$this->change($path, $from, $to, $changes);
		}
	}

	private function change(string $path, mixed $from, mixed $to, array &$changes): void
	{
		$metadata = in_array($path, ['/version', '/schema_version', '/name_key', '/description_key'], true)
			|| preg_match('~/(name_key|description_key|label_key)$~', $path) === 1;
		$changes[] = ['path' => $path, 'from' => $from, 'to' => $to, 'compatible' => $metadata];
	}
}
