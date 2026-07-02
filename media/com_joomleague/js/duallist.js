(function () {
	'use strict';

	const getRoot = (element) => {
		if (!(element instanceof Element)) {
			return null;
		}

		return element.closest('[data-jl-duallist]');
	};

	const getLists = (root) => ({
		available: root ? root.querySelector('[data-available]') : null,
		assigned: root ? root.querySelector('[data-assigned]') : null,
	});

	const moveSelected = (from, to) => {
		if (!from || !to) {
			return;
		}

		const selected = Array.from(from.options).filter((option) => option.selected);

		selected.forEach((option) => {
			option.selected = false;
			to.appendChild(option);
		});
	};

	const selectAll = (select) => {
		if (!select) {
			return;
		}

		Array.from(select.options).forEach((option) => {
			option.selected = true;
		});
	};

	document.addEventListener('click', (event) => {
		const target = event.target instanceof Element ? event.target : null;
		const addButton = target ? target.closest('[data-add]') : null;
		const removeButton = target ? target.closest('[data-remove]') : null;

		if (!addButton && !removeButton) {
			return;
		}

		const root = getRoot(addButton || removeButton);
		const lists = getLists(root);

		event.preventDefault();
		event.stopPropagation();

		if (addButton) {
			moveSelected(lists.available, lists.assigned);
			lists.assigned && lists.assigned.focus();
			return;
		}

		moveSelected(lists.assigned, lists.available);
		lists.available && lists.available.focus();
	});

	document.addEventListener('dblclick', (event) => {
		const target = event.target instanceof Element ? event.target : null;
		const available = target ? target.closest('[data-available]') : null;
		const assigned = target ? target.closest('[data-assigned]') : null;

		if (!available && !assigned) {
			return;
		}

		const root = getRoot(available || assigned);
		const lists = getLists(root);

		if (available) {
			moveSelected(lists.available, lists.assigned);
			lists.assigned && lists.assigned.focus();
			return;
		}

		moveSelected(lists.assigned, lists.available);
		lists.available && lists.available.focus();
	});

	document.addEventListener('submit', (event) => {
		const form = event.target;

		if (!(form instanceof HTMLFormElement)) {
			return;
		}

		form.querySelectorAll('[data-jl-duallist] [data-assigned]').forEach(selectAll);
	}, true);
})();
