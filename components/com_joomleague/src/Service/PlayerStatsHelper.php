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
 * Odehrané starty/střídání/minuty a statistiky událostí hráče, seskupené buď po
 * zápase, nebo po soutěži (projectteam). Vzorec je 1:1 převzatý a ověřený proti
 * originálnímu JoomLeague 3 zdroji (models/player.php::getTimePlayed(),
 * models/player.php::getPlayerInOutStats(), models/person.php::getPlayerEvents()):
 *
 *   - odehrané minuty = (starty × plný čas zápasu)
 *                      + (příchody jako střídání × plný čas − minuta příchodu)
 *                      + (minuta odchodu při střídání − plný čas zápasu)
 *                      − (plný čas − minuta události u vyloučení, např. červená karta)
 *   - "odchod při střídání" se pozná podle toho, že v JINÉM řádku téhož zápasu
 *     někdo jiný nastoupil "in_for" = teamplayer_id této osoby (sloupec match_player.out
 *     se v reálných datech nepoužívá).
 */
final class PlayerStatsHelper
{
	/**
	 * @param  object[]  $appearances  vlastní starty/střídání osoby (getPlayerCareerData()['appearances'])
	 * @param  object[]  $subOut       řádky, kde někdo jiný nastoupil "in_for" této osoby (['subOut'])
	 * @param  object[]  $events       události (karty, góly, ...) této osoby (['events'])
	 * @param  callable(object): (int|string)  $keyFn  podle čeho seskupovat (match_id nebo projectteam_id)
	 *
	 * @return array<int|string, object>
	 */
	public static function aggregate(array $appearances, array $subOut, array $events, callable $keyFn): array
	{
		$buckets = [];
		$regularTimeByMatch = [];
		$keyByMatchPlayer = [];

		$ensure = static function (array &$buckets, $key): void {
			if (!isset($buckets[$key])) {
				$buckets[$key] = (object) [
					'played' => 0,
					'started' => 0,
					'sub_in' => 0,
					'sub_out' => 0,
					'sub_in_minute' => null,
					'sub_out_minute' => null,
					'minutes' => 0,
					'events' => [],
				];
			}
		};

		foreach ($appearances as $row) {
			$matchId = (int) $row->match_id;
			$regularTime = (int) $row->game_regular_time;
			$regularTimeByMatch[$matchId] = $regularTime;
			$keyByMatchPlayer[$matchId . ':' . (int) $row->teamplayer_id] = $keyFn($row);

			$key = $keyFn($row);
			$ensure($buckets, $key);
			$buckets[$key]->played++;

			if ((int) $row->came_in === 0) {
				$buckets[$key]->started++;
				$buckets[$key]->minutes += $regularTime;
			} else {
				$buckets[$key]->sub_in++;
				$buckets[$key]->sub_in_minute = (int) $row->in_out_time;
				$buckets[$key]->minutes += $regularTime - (int) $row->in_out_time;
			}
		}

		foreach ($subOut as $row) {
			$mapKey = (int) $row->match_id . ':' . (int) $row->teamplayer_id;

			if (!isset($keyByMatchPlayer[$mapKey])) {
				continue;
			}

			$key = $keyByMatchPlayer[$mapKey];
			$regularTime = $regularTimeByMatch[(int) $row->match_id] ?? 0;
			$buckets[$key]->sub_out++;
			$buckets[$key]->sub_out_minute = (int) $row->in_out_time;
			$buckets[$key]->minutes += (int) $row->in_out_time - $regularTime;
		}

		foreach ($events as $row) {
			$mapKey = (int) $row->match_id . ':' . (int) $row->teamplayer_id;

			if (!isset($keyByMatchPlayer[$mapKey])) {
				continue;
			}

			$key = $keyByMatchPlayer[$mapKey];
			$eventTypeId = (int) $row->event_type_id;

			if (!isset($buckets[$key]->events[$eventTypeId])) {
				$buckets[$key]->events[$eventTypeId] = (object) [
					'name' => (string) $row->event_name,
					'icon' => (string) $row->event_icon,
					'total' => 0.0,
				];
			}

			$buckets[$key]->events[$eventTypeId]->total += (float) $row->event_sum;

			if ((int) $row->suspension === 1) {
				$regularTime = $regularTimeByMatch[(int) $row->match_id] ?? 0;
				$buckets[$key]->minutes -= $regularTime - (int) $row->event_time;
			}
		}

		return $buckets;
	}
}
