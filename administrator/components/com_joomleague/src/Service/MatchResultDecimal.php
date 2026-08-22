<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class MatchResultDecimal
{
	/** @param list<string> $values */
	public static function sumEquals(string $expected, array $values): bool
	{
		return self::scaled(self::sum($values)) === self::scaled($expected);
	}

	/** @param list<string> $values */
	public static function sum(array $values): string
	{
		$sum = '0';
		foreach ($values as $value) $sum = self::add($sum, self::scaled($value));
		$negative = str_starts_with($sum, '-');
		$digits = ltrim($sum, '-');
		$digits = str_pad($digits, 10, '0', STR_PAD_LEFT);
		$integer = ltrim(substr($digits, 0, -9), '0') ?: '0';
		$fraction = rtrim(substr($digits, -9), '0');
		$result = $integer . ($fraction === '' ? '' : '.' . $fraction);
		return $negative && $result !== '0' ? '-' . $result : $result;
	}

	private static function scaled(string $value): string
	{
		if (preg_match('/^(-?)(\d{1,21})(?:\.(\d{1,9}))?$/', $value, $match) !== 1) {
			throw new \InvalidArgumentException('Decimal result value is invalid.');
		}

		$digits = ltrim($match[2] . str_pad($match[3] ?? '', 9, '0'), '0');

		if ($digits === '') return '0';

		return $match[1] === '-' ? '-' . $digits : $digits;
	}

	private static function add(string $left, string $right): string
	{
		$leftNegative = str_starts_with($left, '-');
		$rightNegative = str_starts_with($right, '-');
		$leftAbs = ltrim($left, '-');
		$rightAbs = ltrim($right, '-');

		if ($leftNegative === $rightNegative) {
			$result = self::addAbs($leftAbs, $rightAbs);
			return $leftNegative && $result !== '0' ? '-' . $result : $result;
		}

		$comparison = self::compareAbs($leftAbs, $rightAbs);

		if ($comparison === 0) return '0';

		$result = $comparison > 0 ? self::subtractAbs($leftAbs, $rightAbs) : self::subtractAbs($rightAbs, $leftAbs);
		$negative = $comparison > 0 ? $leftNegative : $rightNegative;

		return $negative ? '-' . $result : $result;
	}

	private static function addAbs(string $left, string $right): string
	{
		$length = max(strlen($left), strlen($right));
		$left = str_pad($left, $length, '0', STR_PAD_LEFT);
		$right = str_pad($right, $length, '0', STR_PAD_LEFT);
		$carry = 0;
		$result = '';

		for ($index = $length - 1; $index >= 0; $index--) {
			$sum = (int) $left[$index] + (int) $right[$index] + $carry;
			$result = ($sum % 10) . $result;
			$carry = intdiv($sum, 10);
		}

		return ltrim(($carry > 0 ? (string) $carry : '') . $result, '0') ?: '0';
	}

	private static function subtractAbs(string $larger, string $smaller): string
	{
		$length = strlen($larger);
		$smaller = str_pad($smaller, $length, '0', STR_PAD_LEFT);
		$borrow = 0;
		$result = '';

		for ($index = $length - 1; $index >= 0; $index--) {
			$digit = (int) $larger[$index] - (int) $smaller[$index] - $borrow;
			$borrow = $digit < 0 ? 1 : 0;
			if ($borrow) $digit += 10;
			$result = $digit . $result;
		}

		return ltrim($result, '0') ?: '0';
	}

	private static function compareAbs(string $left, string $right): int
	{
		return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
	}
}
