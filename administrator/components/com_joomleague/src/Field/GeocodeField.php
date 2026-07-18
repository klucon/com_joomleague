<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/**
 * Tlačítko "Najít souřadnice" — otevře modal s interaktivní mapou (Leaflet + OpenStreetMap
 * dlaždice, bez API klíče). Mapu vycentruje podle geocodingu adresních polí (source_fields)
 * nebo podle už uložených souřadnic, uživatel doladí špendlíkem a potvrdí do lat_field/lng_field.
 * Viz media/com_joomleague/js/geocode-button.js.
 */
final class GeocodeField extends FormField
{
	protected $type = 'Geocode';

	private static bool $modalRendered = false;

	protected function getInput(): string
	{
		$sourceFields = (string) $this->element['source_fields'];
		$latField = (string) $this->element['lat_field'];
		$lngField = (string) $this->element['lng_field'];
		$endpoint = (new Uri())->setPath(Uri::base(true) . '/index.php');
		$endpoint->setQuery([
			'option'                => 'com_joomleague',
			'task'                  => 'ajax.geocode',
			'format'                => 'json',
			Session::getFormToken() => 1,
		]);

		$button = '<button type="button" class="btn btn-secondary jl-geocode-btn"'
			. ' data-endpoint="' . htmlspecialchars((string) $endpoint, ENT_QUOTES, 'UTF-8') . '"'
			. ' data-source-fields="' . htmlspecialchars($sourceFields, ENT_QUOTES, 'UTF-8') . '"'
			. ' data-lat-field="' . htmlspecialchars($latField, ENT_QUOTES, 'UTF-8') . '"'
			. ' data-lng-field="' . htmlspecialchars($lngField, ENT_QUOTES, 'UTF-8') . '">'
			. '<span class="icon-map-marker" aria-hidden="true"></span> '
			. Text::_('COM_JOOMLEAGUE_FIELD_GEOCODE_BUTTON')
			. '</button>';

		return $button . $this->renderModal();
	}

	private function renderModal(): string
	{
		if (self::$modalRendered) {
			return '';
		}

		self::$modalRendered = true;

		return '<div class="modal fade" id="jl-geocode-modal" tabindex="-1" aria-hidden="true">'
			. '<div class="modal-dialog modal-lg">'
			. '<div class="modal-content">'
			. '<div class="modal-header">'
			. '<h3 class="modal-title">' . Text::_('COM_JOOMLEAGUE_FIELD_GEOCODE_MODAL_TITLE') . '</h3>'
			. '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' . Text::_('JCLOSE') . '"></button>'
			. '</div>'
			. '<div class="modal-body">'
			. '<div id="jl-geocode-map" style="height:420px;"></div>'
			. '<p class="form-text mt-2">' . Text::_('COM_JOOMLEAGUE_FIELD_GEOCODE_MODAL_HINT') . '</p>'
			. '</div>'
			. '<div class="modal-footer">'
			. '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Text::_('JCANCEL') . '</button>'
			. '<button type="button" class="btn btn-primary jl-geocode-confirm">' . Text::_('COM_JOOMLEAGUE_FIELD_GEOCODE_MODAL_CONFIRM') . '</button>'
			. '</div>'
			. '</div>'
			. '</div>'
			. '</div>';
	}
}
