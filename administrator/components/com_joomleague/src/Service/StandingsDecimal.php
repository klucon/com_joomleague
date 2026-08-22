<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

final class StandingsDecimal
{
	public static function normalize(string|int $value): string
	{
		[$negative, $integer, $fraction] = self::parts((string) $value);
		$integer = ltrim($integer, '0') ?: '0';
		$fraction = rtrim($fraction, '0');
		$result = $integer . ($fraction === '' ? '' : '.' . $fraction);
		return $negative && $result !== '0' ? '-' . $result : $result;
	}

	public static function add(string|int $left, string|int $right): string
	{
		return self::fromScaled(self::addSigned(self::scaled((string) $left), self::scaled((string) $right)));
	}

	public static function subtract(string|int $left, string|int $right): string
	{
		$right = self::scaled((string) $right);
		return self::fromScaled(self::addSigned(self::scaled((string) $left), $right === '0' ? '0' : (str_starts_with($right, '-') ? substr($right, 1) : '-' . $right)));
	}

	public static function compare(string|int $left, string|int $right): int
	{
		$left = self::scaled((string) $left); $right = self::scaled((string) $right);
		$leftNegative = str_starts_with($left, '-'); $rightNegative = str_starts_with($right, '-');
		if ($leftNegative !== $rightNegative) return $leftNegative ? -1 : 1;
		$result = self::compareAbs(ltrim($left, '-'), ltrim($right, '-'));
		return $leftNegative ? -$result : $result;
	}

	public static function divide(string|int $numerator, string|int $denominator, int $scale = 9): ?string
	{
		if ($scale < 0 || $scale > 9) throw new \InvalidArgumentException('Standings decimal scale is invalid.');
		$left = self::scaled((string) $numerator); $right = self::scaled((string) $denominator);
		if ($right === '0') return null;
		$negative = str_starts_with($left, '-') !== str_starts_with($right, '-');
		$dividend = ltrim(ltrim($left, '-'), '0') ?: '0'; $divisor = ltrim(ltrim($right, '-'), '0');
		[$integer, $remainder] = self::divideAbs($dividend, $divisor);
		$fraction = '';
		for ($index = 0; $index < $scale && $remainder !== '0'; $index++) {
			[$digit, $remainder] = self::divideAbs($remainder . '0', $divisor);
			$fraction .= $digit;
		}
		$result = (ltrim($integer, '0') ?: '0') . ($fraction === '' ? '' : '.' . rtrim($fraction, '0'));
		$result = rtrim($result, '.');
		return $negative && $result !== '0' ? '-' . $result : $result;
	}

	/** @return array{0:bool,1:string,2:string} */
	private static function parts(string $value): array
	{
		if (preg_match('/^(-?)(\d{1,21})(?:\.(\d{1,9}))?$/', trim($value), $match) !== 1) throw new \InvalidArgumentException('Standings decimal value is invalid.');
		return [$match[1] === '-', $match[2], $match[3] ?? ''];
	}

	private static function scaled(string $value): string
	{
		[$negative, $integer, $fraction] = self::parts($value);
		$digits = ltrim($integer . str_pad($fraction, 9, '0'), '0') ?: '0';
		return $negative && $digits !== '0' ? '-' . $digits : $digits;
	}

	private static function fromScaled(string $value): string
	{
		$negative = str_starts_with($value, '-'); $digits = str_pad(ltrim($value, '-'), 10, '0', STR_PAD_LEFT);
		$integer = ltrim(substr($digits, 0, -9), '0') ?: '0'; $fraction = rtrim(substr($digits, -9), '0');
		$result = $integer . ($fraction === '' ? '' : '.' . $fraction);
		return $negative && $result !== '0' ? '-' . $result : $result;
	}

	private static function addSigned(string $left, string $right): string
	{
		$leftNegative = str_starts_with($left, '-'); $rightNegative = str_starts_with($right, '-');
		$leftAbs = ltrim($left, '-'); $rightAbs = ltrim($right, '-');
		if ($leftNegative === $rightNegative) { $sum = self::addAbs($leftAbs, $rightAbs); return $leftNegative && $sum !== '0' ? '-' . $sum : $sum; }
		$comparison = self::compareAbs($leftAbs, $rightAbs); if ($comparison === 0) return '0';
		$difference = $comparison > 0 ? self::subtractAbs($leftAbs, $rightAbs) : self::subtractAbs($rightAbs, $leftAbs);
		$negative = $comparison > 0 ? $leftNegative : $rightNegative;
		return $negative ? '-' . $difference : $difference;
	}

	private static function addAbs(string $left, string $right): string
	{
		$length = max(strlen($left), strlen($right)); $left = str_pad($left, $length, '0', STR_PAD_LEFT); $right = str_pad($right, $length, '0', STR_PAD_LEFT); $carry = 0; $result = '';
		for ($index = $length - 1; $index >= 0; $index--) { $sum = (int) $left[$index] + (int) $right[$index] + $carry; $result = ($sum % 10) . $result; $carry = intdiv($sum, 10); }
		return ltrim(($carry ? (string) $carry : '') . $result, '0') ?: '0';
	}

	private static function subtractAbs(string $larger, string $smaller): string
	{
		$length = strlen($larger); $smaller = str_pad($smaller, $length, '0', STR_PAD_LEFT); $borrow = 0; $result = '';
		for ($index = $length - 1; $index >= 0; $index--) { $digit = (int) $larger[$index] - (int) $smaller[$index] - $borrow; $borrow = $digit < 0 ? 1 : 0; if ($borrow) $digit += 10; $result = $digit . $result; }
		return ltrim($result, '0') ?: '0';
	}

	private static function compareAbs(string $left, string $right): int
	{
		$left = ltrim($left, '0') ?: '0'; $right = ltrim($right, '0') ?: '0';
		return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
	}

	/** @return array{0:string,1:string} */
	private static function divideAbs(string $dividend, string $divisor): array
	{
		$quotient = ''; $remainder = '0';
		foreach (str_split(ltrim($dividend, '0') ?: '0') as $digit) {
			$remainder = ltrim(($remainder === '0' ? '' : $remainder) . $digit, '0') ?: '0'; $quotientDigit = 0;
			while (self::compareAbs($remainder, $divisor) >= 0) { $remainder = self::subtractAbs($remainder, $divisor); $quotientDigit++; }
			$quotient .= (string) $quotientDigit;
		}
		return [ltrim($quotient, '0') ?: '0', $remainder];
	}
}
