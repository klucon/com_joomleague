<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Vykreslení vlajky státu podle kódu z číselníku #__joomleague_country.
 * Kód = zároveň název souboru vlajky (images/com_joomleague/flags/<code>.svg).
 */
final class FlagHelper
{
	public static function render(?string $code, bool $showName = true, int $size = 20): string
	{
		$code = strtolower(trim((string) $code));

		if ($code === '') {
			return '';
		}

		$height = (int) round($size * 2 / 3);
		$name   = Text::_('COM_JOOMLEAGUE_COUNTRY_' . strtoupper(str_replace('-', '_', $code)));
		$src    = Uri::root(true) . '/images/com_joomleague/flags/' . $code . '.svg';

		$img = '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="' . htmlspecialchars($name, ENT_QUOTES)
			. '" title="' . htmlspecialchars($name, ENT_QUOTES) . '" width="' . $size . '" height="' . $height
			. '" loading="lazy" style="border:1px solid rgba(0,0,0,.15);border-radius:2px;vertical-align:middle;object-fit:cover">';

		if (!$showName) {
			return $img;
		}

		return '<span style="display:inline-flex;align-items:center;gap:.4rem">' . $img
			. '<span>' . htmlspecialchars($name, ENT_QUOTES) . '</span></span>';
	}
}
