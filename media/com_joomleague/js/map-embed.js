/**
 * @package     Klucon
 * @subpackage  com_joomleague
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz)
 * @license     GNU General Public License version 2 or later
 */

(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		if (typeof window.L === 'undefined') {
			return;
		}

		document.querySelectorAll('.jl-map-embed').forEach(function (el) {
			var lat = parseFloat(el.dataset.lat);
			var lng = parseFloat(el.dataset.lng);

			if (isNaN(lat) || isNaN(lng)) {
				return;
			}

			var map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], 16);
			L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 19,
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
			}).addTo(map);
			L.marker([lat, lng]).addTo(map);
		});
	});
}());
