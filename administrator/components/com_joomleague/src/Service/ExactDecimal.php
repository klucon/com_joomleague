<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

/** Exact base-10 arithmetic for profile rule expressions. */
final class ExactDecimal
{
	private const INPUT_SCALE = 9;
	private const RESULT_SCALE = 18;

	public static function add(string $left, string $right): string
	{
		[$leftNegative, $leftDigits, $leftScale] = self::parts($left, self::RESULT_SCALE);
		[$rightNegative, $rightDigits, $rightScale] = self::parts($right, self::RESULT_SCALE);
		$scale = max($leftScale, $rightScale);
		$leftDigits .= str_repeat('0', $scale - $leftScale);
		$rightDigits .= str_repeat('0', $scale - $rightScale);

		if ($leftNegative === $rightNegative) {
			return self::format($leftNegative, self::addAbs($leftDigits, $rightDigits), $scale);
		}

		$comparison = self::compareAbs($leftDigits, $rightDigits);
		if ($comparison === 0) {
			return '0';
		}

		return $comparison > 0
			? self::format($leftNegative, self::subtractAbs($leftDigits, $rightDigits), $scale)
			: self::format($rightNegative, self::subtractAbs($rightDigits, $leftDigits), $scale);
	}

	public static function multiply(string $left, string $right): string
	{
		[$leftNegative, $leftDigits, $leftScale] = self::parts($left, self::INPUT_SCALE);
		[$rightNegative, $rightDigits, $rightScale] = self::parts($right, self::INPUT_SCALE);
		$digits = self::multiplyAbs($leftDigits, $rightDigits);

		return self::format($leftNegative !== $rightNegative, $digits, $leftScale + $rightScale);
	}

	public static function compare(string $left, string $right): int
	{
		[$leftNegative, $leftDigits, $leftScale] = self::parts($left, self::RESULT_SCALE);
		[$rightNegative, $rightDigits, $rightScale] = self::parts($right, self::RESULT_SCALE);
		$scale = max($leftScale, $rightScale);
		$leftDigits .= str_repeat('0', $scale - $leftScale);
		$rightDigits .= str_repeat('0', $scale - $rightScale);

		if ($leftNegative !== $rightNegative) {
			return $leftNegative ? -1 : 1;
		}

		$result = self::compareAbs($leftDigits, $rightDigits);
		return $leftNegative ? -$result : $result;
	}

	public static function fromNumber(int|float $value): string
	{
		if (is_int($value)) {
			return (string) $value;
		}
		if (!is_finite($value)) {
			throw new \InvalidArgumentException('Decimal value must be finite.');
		}

		return self::formatInput(number_format($value, self::INPUT_SCALE, '.', ''));
	}

	/** @return array{bool,string,int} */
	private static function parts(string $value, int $maximumScale): array
	{
		$value = trim($value);
		if (preg_match('/^(-?)(\d{1,42})(?:\.(\d{1,' . $maximumScale . '}))?$/', $value, $match) !== 1) {
			throw new \InvalidArgumentException('Decimal value is outside the supported precision.');
		}
		$integer = ltrim($match[2], '0') ?: '0';
		$fraction = rtrim($match[3] ?? '', '0');
		$digits = ltrim($integer . $fraction, '0') ?: '0';

		return [$match[1] === '-' && $digits !== '0', $digits, strlen($fraction)];
	}

	private static function formatInput(string $value): string
	{
		$value = rtrim(rtrim($value, '0'), '.');
		return $value === '-0' || $value === '' ? '0' : $value;
	}

	private static function format(bool $negative, string $digits, int $scale): string
	{
		$digits = ltrim($digits, '0') ?: '0';
		if ($scale > 0) {
			$digits = str_pad($digits, $scale + 1, '0', STR_PAD_LEFT);
			$value = substr($digits, 0, -$scale) . '.' . substr($digits, -$scale);
		} else {
			$value = $digits;
		}
		$value = self::formatInput($value);

		return $negative && $value !== '0' ? '-' . $value : $value;
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
		$smaller = str_pad($smaller, strlen($larger), '0', STR_PAD_LEFT);
		$borrow = 0;
		$result = '';
		for ($index = strlen($larger) - 1; $index >= 0; $index--) {
			$digit = (int) $larger[$index] - (int) $smaller[$index] - $borrow;
			$borrow = $digit < 0 ? 1 : 0;
			$result = ($digit < 0 ? $digit + 10 : $digit) . $result;
		}

		return ltrim($result, '0') ?: '0';
	}

	private static function multiplyAbs(string $left, string $right): string
	{
		if ($left === '0' || $right === '0') {
			return '0';
		}
		$result = array_fill(0, strlen($left) + strlen($right), 0);
		for ($leftIndex = strlen($left) - 1; $leftIndex >= 0; $leftIndex--) {
			for ($rightIndex = strlen($right) - 1; $rightIndex >= 0; $rightIndex--) {
				$position = $leftIndex + $rightIndex + 1;
				$total = $result[$position] + ((int) $left[$leftIndex] * (int) $right[$rightIndex]);
				$result[$position] = $total % 10;
				$result[$position - 1] += intdiv($total, 10);
			}
		}

		return ltrim(implode('', $result), '0') ?: '0';
	}

	private static function compareAbs(string $left, string $right): int
	{
		$left = ltrim($left, '0') ?: '0';
		$right = ltrim($right, '0') ?: '0';

		return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
	}
}
