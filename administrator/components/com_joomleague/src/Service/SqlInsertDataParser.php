<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

/** Parses data-only INSERT syntax; never evaluates SQL expressions. */
final class SqlInsertDataParser
{
	public function parse(string $sql, bool $backslashEscapes = true): array
	{
		$sql = trim($sql);
		if (preg_match('/\AINSERT\s+INTO\s+([`"])(#__joomleague_[a-z0-9_]+)\1\s*\(([^)]+)\)\s+VALUES\s*/i', $sql, $head) !== 1) $this->invalid();
		$columns = [];
		foreach (explode(',', $head[3]) as $column) {
			if (preg_match('/\A\s*([`"])([a-z][a-z0-9_]*)\1\s*\z/i', $column, $match) !== 1) $this->invalid();
			$columns[] = $match[2];
		}
		if (count(array_unique($columns)) !== count($columns)) $this->invalid();
		$offset = strlen($head[0]);
		$length = strlen($sql);
		$rows = [];
		$space = static function () use (&$offset, $sql, $length): void { while ($offset < $length && ctype_space($sql[$offset])) $offset++; };
		do {
			$space();
			if (($sql[$offset++] ?? '') !== '(') $this->invalid();
			$row = [];
			foreach ($columns as $index => $_) {
				$space();
				if (($sql[$offset] ?? '') === "'") {
					$offset++;
					$value = '';
					$closed = false;
					while ($offset < $length) {
						$char = $sql[$offset++];
						if ($char === "'") {
							if (($sql[$offset] ?? '') === "'") { $value .= "'"; $offset++; continue; }
							$closed = true; break;
						}
						if ($char === '\\' && $backslashEscapes) {
							if ($offset >= $length) $this->invalid();
							$escaped = $sql[$offset++];
							$value .= match ($escaped) { '0' => "\0", 'n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'Z' => "\x1a", '%', '_' => '\\' . $escaped, default => $escaped };
						} else $value .= $char;
					}
					if (!$closed) $this->invalid();
					$row[] = $value;
				} elseif (preg_match('/\G(?:NULL|[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?|\{\{profile_version:[a-z][a-z0-9_]*\}\})/i', $sql, $token, 0, $offset) === 1) {
					$offset += strlen($token[0]);
					$row[] = strtoupper($token[0]) === 'NULL' ? null : (str_starts_with($token[0], '{{') ? ['profile' => substr($token[0], 18, -2)] : $token[0]);
				} else $this->invalid();
				$space();
				if (($sql[$offset++] ?? '') !== ($index === count($columns) - 1 ? ')' : ',')) $this->invalid();
			}
			$rows[] = $row;
			$space();
			if (($sql[$offset] ?? '') !== ',') break;
			$offset++;
		} while (true);
		if (($sql[$offset] ?? '') === ';') { $offset++; $space(); }
		if ($offset !== $length) $this->invalid();
		return ['table' => $head[2], 'columns' => $columns, 'rows' => $rows];
	}
	private function invalid(): never { throw new \RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT'); }
}
