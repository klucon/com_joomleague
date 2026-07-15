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
		var teamSelect = document.getElementById('jform_projectteam_id');
		var posSelect = document.getElementById('jform_project_position_id');

		if (!teamSelect || !posSelect) {
			return;
		}

		var noneLabel = posSelect.options.length ? posSelect.options[0].textContent : '';

		function setOptions(items, selectedValue) {
			posSelect.innerHTML = '';

			var noneOption = document.createElement('option');
			noneOption.value = '';
			noneOption.textContent = noneLabel;
			posSelect.appendChild(noneOption);

			items.forEach(function (item) {
				var option = document.createElement('option');
				option.value = item.value;
				option.textContent = item.text;

				if (selectedValue && String(selectedValue) === String(item.value)) {
					option.selected = true;
				}

				posSelect.appendChild(option);
			});
		}

		function loadPositions(projectteamId, selectedValue) {
			if (!projectteamId) {
				setOptions([], '');

				return;
			}

			fetch('index.php?option=com_joomleague&task=ajax.teampositionsoptions&pt=' + encodeURIComponent(projectteamId), {
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			})
				.then(function (response) { return response.json(); })
				.then(function (result) {
					setOptions((result && result.items) || [], selectedValue);
				});
		}

		// Při otevření editace zúžit už předvyplněný seznam pozic na projekt vybraného
		// týmu, ale zachovat aktuálně uloženou hodnotu vybranou.
		var initialValue = posSelect.value;

		if (teamSelect.value) {
			loadPositions(teamSelect.value, initialValue);
		}

		// Při změně týmu se seznam pozic přenačte pro nový projekt; dřívější volba
		// pozice se resetuje, protože už nemusí do nového projektu patřit.
		teamSelect.addEventListener('change', function () {
			loadPositions(teamSelect.value, '');
		});
	});
}());
