<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Version;

/**
 * Vyhledání souřadnic podle adresy přes veřejné Nominatim API (OpenStreetMap) — jen pro
 * ruční dohledání v admin editaci (tlačítko "Najít souřadnice"), žádné hromadné dotazy.
 * Nominatim usage policy vyžaduje identifikující User-Agent, viz
 * https://operations.osmfoundation.org/policies/nominatim/.
 */
final class GeocodingHelper
{
	private const ENDPOINT = 'https://nominatim.openstreetmap.org/search';

	public static function lookup(string $query): ?array
	{
		$query = trim($query);

		if ($query === '') {
			return null;
		}

		try {
			$http = HttpFactory::getHttp([], ['curl', 'stream']);
			$url = self::ENDPOINT . '?' . http_build_query([
				'q' => $query,
				'format' => 'json',
				'limit' => 1,
			]);
			$response = $http->get($url, ['User-Agent' => 'JoomLeague/' . (new Version())->getShortVersion() . ' (+https://klucon.cz; info@klucon.cz)'], 8);

			if ($response->code !== 200) {
				return null;
			}

			$results = json_decode((string) $response->body, true);

			if (!\is_array($results) || $results === []) {
				return null;
			}

			$first = $results[0];

			if (!isset($first['lat'], $first['lon'])) {
				return null;
			}

			return ['lat' => (float) $first['lat'], 'lon' => (float) $first['lon']];
		} catch (\Throwable $exception) {
			return null;
		}
	}
}
