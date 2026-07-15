<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

\defined('_JEXEC') or die;

/**
 * Formátování jména osoby podle číselníku name_format (COM_JOOMLEAGUE_GLOBAL_NAME_FORMAT_*),
 * sdíleného mezi matchreport/roster/player/staff/referees šablonami.
 */
final class PersonNameHelper
{
	public static function format(string $firstname, string $lastname, string $nickname, string $format): string
	{
		$firstname = trim($firstname);
		$lastname = trim($lastname);
		$nickname = trim($nickname);
		$nick = $nickname !== '' ? "'" . $nickname . "'" : '';
		$firstInitial = $firstname !== '' ? mb_substr($firstname, 0, 1) . '.' : '';
		$lastInitial = $lastname !== '' ? mb_substr($lastname, 0, 1) . '.' : '';

		return trim(match ($format) {
			'1' => $nick !== '' ? $lastname . ', ' . $nick . ' ' . $firstname : $lastname . ', ' . $firstname,
			'2', '17' => $nick !== '' ? $lastname . ', ' . $firstname . ' ' . $nick : $lastname . ', ' . $firstname,
			'3' => $firstname . ' ' . $lastname,
			'4' => $lastname . ', ' . $firstname,
			'5' => $nick !== '' ? $nick . ' - ' . $firstname . ' ' . $lastname : $firstname . ' ' . $lastname,
			'6' => $nick !== '' ? $nick . ' - ' . $lastname . ', ' . $firstname : $lastname . ', ' . $firstname,
			'7' => $nick !== '' ? $firstname . ' ' . $lastname . ' (' . $nickname . ')' : $firstname . ' ' . $lastname,
			'8' => $firstInitial . ' ' . $lastname,
			'9' => $lastname . ', ' . $firstInitial,
			'10' => $lastname,
			'11' => $nick !== '' ? $firstname . ' ' . $nick . ' ' . $lastInitial : $firstname . ' ' . $lastInitial,
			'12' => $nickname !== '' ? $nickname : $firstname . ' ' . $lastname,
			'13' => $firstname . ' ' . $lastInitial,
			'14' => $lastname . ' ' . $firstname,
			'15' => $lastname . "\n" . $firstname,
			'16' => $firstname . "\n" . $lastname,
			'18' => $lastname . ' ' . $firstInitial,
			default => $nick !== '' ? $firstname . ' ' . $nick . ' ' . $lastname : $firstname . ' ' . $lastname,
		});
	}
}
