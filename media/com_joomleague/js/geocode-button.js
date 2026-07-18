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
	var leafletLoading = null;

	document.addEventListener('DOMContentLoaded', function () {
		var modalEl = document.getElementById('jl-geocode-modal');

		if (!modalEl) {
			return;
		}

		var backdrop = null;
		var map = null;
		var marker = null;
		var activeLatField = null;
		var activeLngField = null;

		function findField(name) {
			if (!name) {
				return null;
			}

			return document.getElementById('jform_' + name)
				|| document.querySelector('[name="jform[' + name + ']"]')
				|| document.querySelector('[name$="[' + name + ']"]');
		}

		function mediaBase() {
			var script = document.querySelector('script[src*="/media/com_joomleague/js/geocode-button.js"]');

			if (script && script.src) {
				return script.src.split('/media/com_joomleague/js/geocode-button.js')[0] + '/media/com_joomleague';
			}

			return (window.Joomla && Joomla.getOptions && Joomla.getOptions('system.paths') && Joomla.getOptions('system.paths').root)
				? Joomla.getOptions('system.paths').root.replace(/\/$/, '') + '/media/com_joomleague'
				: '/media/com_joomleague';
		}

		function ensureLeaflet() {
			if (typeof window.L !== 'undefined') {
				return Promise.resolve();
			}

			if (leafletLoading) {
				return leafletLoading;
			}

			var base = mediaBase();

			if (!document.querySelector('link[href*="/media/com_joomleague/vendor/leaflet/leaflet.css"]')) {
				var link = document.createElement('link');
				link.rel = 'stylesheet';
				link.href = base + '/vendor/leaflet/leaflet.css';
				document.head.appendChild(link);
			}

			leafletLoading = new Promise(function (resolve, reject) {
				var script = document.createElement('script');
				script.src = base + '/vendor/leaflet/leaflet.js';
				script.onload = resolve;
				script.onerror = reject;
				document.head.appendChild(script);
			});

			return leafletLoading;
		}

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

		function renderMapAt(lat, lng, zoom) {
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

		function showMapAt(lat, lng, zoom) {
			ensureLeaflet()
				.then(function () {
					renderMapAt(lat, lng, zoom);
				})
				.catch(function () {
					openModal();
				});
		}

		document.querySelectorAll('.jl-geocode-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				activeLatField = findField(btn.dataset.latField);
				activeLngField = findField(btn.dataset.lngField);

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
						var el = findField(name.trim());

						return el ? el.value.trim() : '';
					})
					.filter(Boolean)
					.join(', ');

				if (!query) {
					showMapAt(DEFAULT_LAT, DEFAULT_LNG, DEFAULT_ZOOM);

					return;
				}

				var endpoint = btn.dataset.endpoint || 'index.php?option=com_joomleague&task=ajax.geocode&format=json';
				var separator = endpoint.indexOf('?') === -1 ? '?' : '&';

				btn.disabled = true;

				fetch(endpoint + separator + 'q=' + encodeURIComponent(query), {
					credentials: 'same-origin',
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
