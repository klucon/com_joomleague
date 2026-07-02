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

	const syncEditors = () => {
		if (!window.Joomla || !Joomla.editors || !Joomla.editors.instances) {
			return;
		}

		Object.keys(Joomla.editors.instances).forEach((name) => {
			const editor = Joomla.editors.instances[name];

			if (editor && typeof editor.save === 'function') {
				editor.save();
			}
		});
	};

	window.Joomla = window.Joomla || {};

	Joomla.submitbutton = (task) => {
		const form = document.getElementById('adminForm');

		if (!form) {
			return;
		}

		const taskInput = document.getElementById('jl-matchdata-task');

		if (taskInput) {
			taskInput.value = task || 'matchdata.save';
		}

		syncEditors();
		form.submit();
	};

	document.addEventListener('click', (event) => {
		const removeButton = event.target.closest('.jl-matchdata-remove');

		if (removeButton) {
			const row = removeButton.closest('tr');

			if (row) {
				row.remove();
			}

			return;
		}

		if (event.target.id !== 'jl-matchdata-add-row') {
			return;
		}

		const table = document.getElementById('jl-matchdata-table');
		const body = table ? table.querySelector('tbody') : null;
		const source = body ? body.querySelector('tr:last-child') : null;

		if (!table || !body || !source) {
			return;
		}

		const index = parseInt(table.dataset.nextIndex || '0', 10);
		const clone = source.cloneNode(true);

		clone.querySelectorAll('input, select, textarea').forEach((field) => {
			if (field.name) {
				field.name = field.name.replace(/rows\[[0-9]+\]/, `rows[${index}]`);
			}

			if (field.tagName === 'SELECT') {
				field.selectedIndex = 0;
			} else if (field.type === 'checkbox' || field.type === 'radio') {
				field.checked = false;
			} else if (field.type === 'number') {
				field.value = field.name.includes('[event_sum]') ? '1' : '0';
			} else {
				field.value = '';
			}
		});

		body.appendChild(clone);
		table.dataset.nextIndex = String(index + 1);
	});
}());
