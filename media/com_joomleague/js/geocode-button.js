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

	var DEFAULT_LAT = 49.8;
	var DEFAULT_LNG = 15.5;
	var DEFAULT_ZOOM = 7;
	var FOUND_ZOOM = 16;

	document.addEventListener('DOMContentLoaded', function () {
		var modalEl = document.getElementById('jl-geocode-modal');

		if (!modalEl || typeof window.L === 'undefined') {
			return;
		}

		var backdrop = null;
		var map = null;
		var marker = null;
		var activeLatField = null;
		var activeLngField = null;

		function openModal() {
			modalEl.classList.add('show');
			modalEl.style.display = 'block';
			modalEl.setAttribute('aria-modal', 'true');
			modalEl.removeAttribute('aria-hidden');
			document.body.classList.add('modal-open');

			if (!backdrop) {
				backdrop = document.createElement('div');
				backdrop.className = 'modal-backdrop fade show';
				document.body.appendChild(backdrop);
			}
		}

		function closeModal() {
			modalEl.classList.remove('show');
			modalEl.style.display = 'none';
			modalEl.setAttribute('aria-hidden', 'true');
			modalEl.removeAttribute('aria-modal');
			document.body.classList.remove('modal-open');

			if (backdrop) {
				backdrop.remove();
				backdrop = null;
			}
		}

		modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (el) {
			el.addEventListener('click', closeModal);
		});

		function showMapAt(lat, lng, zoom) {
			openModal();

			setTimeout(function () {
				if (!map) {
					map = L.map('jl-geocode-map').setView([lat, lng], zoom);
					L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
						maxZoom: 19,
						attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
					}).addTo(map);
					marker = L.marker([lat, lng], { draggable: true }).addTo(map);
					map.on('click', function (e) {
						marker.setLatLng(e.latlng);
					});
				} else {
					map.setView([lat, lng], zoom);
					marker.setLatLng([lat, lng]);
				}

				map.invalidateSize();
			}, 50);
		}

		document.querySelectorAll('.jl-geocode-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				activeLatField = document.getElementById('jform_' + btn.dataset.latField);
				activeLngField = document.getElementById('jform_' + btn.dataset.lngField);

				if (!activeLatField || !activeLngField) {
					return;
				}

				var existingLat = parseFloat(activeLatField.value);
				var existingLng = parseFloat(activeLngField.value);

				if (!isNaN(existingLat) && !isNaN(existingLng)) {
					showMapAt(existingLat, existingLng, FOUND_ZOOM);

					return;
				}

				var sourceFields = (btn.dataset.sourceFields || '').split(',').filter(Boolean);
				var query = sourceFields
					.map(function (name) {
						var el = document.getElementById('jform_' + name);

						return el ? el.value.trim() : '';
					})
					.filter(Boolean)
					.join(', ');

				if (!query) {
					showMapAt(DEFAULT_LAT, DEFAULT_LNG, DEFAULT_ZOOM);

					return;
				}

				var token = (window.Joomla && Joomla.getOptions) ? Joomla.getOptions('csrf.token') : '';
				var tokenParam = token ? encodeURIComponent(token) + '=1' : '';

				btn.disabled = true;

				fetch('index.php?option=com_joomleague&task=ajax.geocode&q=' + encodeURIComponent(query) + '&' + tokenParam, {
					headers: { 'X-Requested-With': 'XMLHttpRequest' }
				})
					.then(function (response) { return response.json(); })
					.then(function (result) {
						btn.disabled = false;

						if (result && typeof result.lat === 'number' && typeof result.lon === 'number') {
							showMapAt(result.lat, result.lon, FOUND_ZOOM);
						} else {
							showMapAt(DEFAULT_LAT, DEFAULT_LNG, DEFAULT_ZOOM);
						}
					})
					.catch(function () {
						btn.disabled = false;
						showMapAt(DEFAULT_LAT, DEFAULT_LNG, DEFAULT_ZOOM);
					});
			});
		});

		var confirmBtn = modalEl.querySelector('.jl-geocode-confirm');

		if (confirmBtn) {
			confirmBtn.addEventListener('click', function () {
				if (marker && activeLatField && activeLngField) {
					var pos = marker.getLatLng();
					activeLatField.value = pos.lat.toFixed(7);
					activeLngField.value = pos.lng.toFixed(7);
				}

				closeModal();
			});
		}
	});
}());
