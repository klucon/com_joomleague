<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;

/**
 * Sestavuje odkaz "Zobrazit na mapě" podle zvoleného poskytovatele v Možnosti komponenty
 * (fieldset "maps"). Google/OSM/Seznam/Bing/Yandex mají pevnou šablonu URL, "custom" čte
 * vlastní šablonu s placeholderem {address} (případně i {lat}/{lng}/{zoom}).
 *
 * Pokud je u záznamu vyplněné latitude/longitude (viz Field/GeocodeField), použije se
 * přesnější "pin" šablona se souřadnicemi + zoomem místo fulltextového hledání adresy —
 * u OSM/Nominatim to zejména řeší slabší fuzzy vyhledávání menších/neúplných adres.
 */
final class MapUrlHelper
{
	private const TEMPLATES = [
		'google' => 'https://www.google.com/maps/search/?api=1&query={address}',
		'osm' => 'https://www.openstreetmap.org/search?query={address}',
		'seznam' => 'https://mapy.com/zakladni?q={address}',
		'bing' => 'https://www.bing.com/maps?q={address}',
		'yandex' => 'https://yandex.com/maps/?text={address}',
	];

	private const PIN_TEMPLATES = [
		'google' => 'https://www.google.com/maps/search/?api=1&query={lat},{lng}',
		'osm' => 'https://www.openstreetmap.org/?mlat={lat}&mlon={lng}#map={zoom}/{lat}/{lng}',
		'seznam' => 'https://mapy.com/zakladni?x={lng}&y={lat}&z={zoom}&source=coor&id={lng}%2C{lat}',
		'bing' => 'https://www.bing.com/maps?cp={lat}~{lng}&lvl={zoom}&sp=point.{lat}_{lng}_',
		'yandex' => 'https://yandex.com/maps/?ll={lng},{lat}&z={zoom}&pt={lng},{lat}',
	];

	public static function build(string $address, ?float $lat = null, ?float $lng = null): string
	{
		$address = trim($address);

		if ($address === '' && ($lat === null || $lng === null)) {
			return '';
		}

		$params = ComponentHelper::getParams('com_joomleague');
		$provider = (string) $params->get('map_provider', 'osm');
		$zoom = (int) $params->get('map_zoom', 16);

		if ($lat !== null && $lng !== null && $provider !== 'custom' && isset(self::PIN_TEMPLATES[$provider])) {
			$template = self::PIN_TEMPLATES[$provider];
		} else {
			$template = $provider === 'custom'
				? (string) $params->get('map_url_template', '')
				: (self::TEMPLATES[$provider] ?? self::TEMPLATES['osm']);

			if (trim($template) === '') {
				$template = self::TEMPLATES['osm'];
			}
		}

		return str_replace(
			['{address}', '{lat}', '{lng}', '{zoom}'],
			[rawurlencode($address), $lat !== null ? (string) $lat : '', $lng !== null ? (string) $lng : '', (string) $zoom],
			$template
		);
	}
}
