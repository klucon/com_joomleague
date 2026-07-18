(function () {
	'use strict';

	const createElement = (tag, attributes = {}, text = '') => {
		const element = document.createElement(tag);

		Object.entries(attributes).forEach(([key, value]) => {
			if (value === null || value === undefined) {
				return;
			}

			element.setAttribute(key, String(value));
		});

		if (text !== '') {
			element.textContent = text;
		}

		return element;
	};

	const requestJson = async (url, params, method = 'GET') => {
		const options = { headers: { Accept: 'application/json' } };
		let target = url;

		if (method === 'GET') {
			const query = new URLSearchParams(params);
			target += (target.includes('?') ? '&' : '?') + query.toString();
		} else {
			options.method = method;
			options.headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
			options.body = new URLSearchParams(params).toString();
		}

		const response = await fetch(target, options);
		const data = await response.json().catch(() => ({}));

		if (!response.ok || data.error) {
			throw new Error(data.error || response.statusText);
		}

		return data;
	};

	const setStatus = (root, message, type = 'info') => {
		const status = root.querySelector('[data-projectteam-status]');

		if (!status) {
			return;
		}

		status.className = 'alert alert-' + type + ' mt-3 mb-0';
		status.textContent = message;
		status.hidden = message === '';
	};

	const clearResults = (results) => {
		results.innerHTML = '';
		results.hidden = true;
	};

	const renderResults = (root, items) => {
		const results = root.querySelector('[data-projectteam-results]');

		if (!results) {
			return;
		}

		results.innerHTML = '';

		if (!items.length) {
			results.appendChild(createElement('div', { class: 'jl-projectteam-result jl-projectteam-result--empty' }, root.dataset.emptyText || 'No matching teams.'));
			results.hidden = false;
			return;
		}

		items.forEach((item) => {
			const option = createElement('button', {
				type: 'button',
				class: 'jl-projectteam-result',
				'data-team-id': item.value,
				role: 'option',
			}, item.text);
			results.appendChild(option);
		});

		results.hidden = false;
	};

	const appendActionLink = (cell, href, icon, label, variant) => {
		const link = createElement('a', { class: 'btn btn-sm ' + variant, href });
		link.appendChild(createElement('span', { class: icon, 'aria-hidden': 'true' }));
		link.appendChild(document.createTextNode(' ' + label));
		cell.appendChild(link);
	};

	const withCount = (label, count) => label + ' (' + (Number.parseInt(count, 10) || 0) + ')';

	const appendTeamRow = (root, item) => {
		const body = root.ownerDocument.querySelector('[data-projectteam-assigned-body]');

		if (!body || body.querySelector('[data-assignment-id="' + item.assignment_id + '"]')) {
			return;
		}

		const row = createElement('tr', {
			'data-projectteam-row': '1',
			'data-assignment-id': item.assignment_id,
		});

		const orderingCell = createElement('td');
		orderingCell.appendChild(createElement('input', {
			class: 'form-control form-control-sm',
			type: 'number',
			min: '1',
			step: '1',
			name: 'ordering[' + item.assignment_id + ']',
			value: item.ordering || body.children.length + 1,
			style: 'width: 6rem;',
		}));
		row.appendChild(orderingCell);

		const imageCell = createElement('td');
		imageCell.appendChild(createElement('span', { class: 'icon-image text-muted', 'aria-hidden': 'true' }));
		row.appendChild(imageCell);

		const nameCell = createElement('td');
		nameCell.appendChild(createElement('a', { href: item.edit_url }, item.name));
		row.appendChild(nameCell);

		const playersCell = createElement('td');
		appendActionLink(playersCell, item.players_url, 'icon-users', withCount(root.dataset.teamplayersLabel || 'Players', item.player_count), 'btn-success');
		row.appendChild(playersCell);

		const staffCell = createElement('td');
		appendActionLink(staffCell, item.staff_url, 'icon-address', withCount(root.dataset.teamstaffsLabel || 'Staff', item.staff_count), 'btn-outline-secondary');
		row.appendChild(staffCell);

		row.appendChild(createElement('td', {}, item.id));
		row.appendChild(createElement('td', {}, item.assignment_id));

		const actionCell = createElement('td');
		const removeButton = createElement('button', {
			class: 'btn btn-sm btn-outline-danger',
			type: 'button',
			'data-projectteam-remove': item.assignment_id,
		});
		removeButton.appendChild(createElement('span', { class: 'icon-trash', 'aria-hidden': 'true' }));
		removeButton.appendChild(document.createTextNode(' ' + (root.dataset.removeLabel || 'Remove')));
		actionCell.appendChild(removeButton);
		row.appendChild(actionCell);

		body.appendChild(row);
	};

	const init = (root) => {
		const input = root.querySelector('[data-projectteam-search]');
		const results = root.querySelector('[data-projectteam-results]');
		const tokenName = root.dataset.token;
		let timer = 0;

		if (!input || !results || !tokenName) {
			return;
		}

		const baseParams = () => ({
			project_id: root.dataset.projectId || '0',
			[tokenName]: '1',
		});

		const search = () => {
			const query = input.value.trim();
			window.clearTimeout(timer);

			if (query.length < 2) {
				clearResults(results);
				return;
			}

			timer = window.setTimeout(async () => {
				try {
					const data = await requestJson(root.dataset.searchUrl, { ...baseParams(), q: query });
					renderResults(root, data.items || []);
					setStatus(root, '');
				} catch (error) {
					clearResults(results);
					setStatus(root, error.message || root.dataset.errorText || 'Request failed.', 'danger');
				}
			}, 180);
		};

		const addTeam = async (teamId) => {
			try {
				const data = await requestJson(root.dataset.addUrl, { ...baseParams(), team_id: teamId }, 'POST');
				appendTeamRow(root, data.item);
				input.value = '';
				clearResults(results);
				input.focus();
				setStatus(root, '');
			} catch (error) {
				setStatus(root, error.message || root.dataset.errorText || 'Request failed.', 'danger');
			}
		};

		input.addEventListener('input', search);
		input.addEventListener('keydown', (event) => {
			if (event.key !== 'Enter') {
				return;
			}

			const first = results.querySelector('[data-team-id]');

			if (!first) {
				return;
			}

			event.preventDefault();
			addTeam(first.getAttribute('data-team-id'));
		});

		results.addEventListener('click', (event) => {
			const target = event.target instanceof Element ? event.target.closest('[data-team-id]') : null;

			if (!target) {
				return;
			}

			addTeam(target.getAttribute('data-team-id'));
		});

		document.addEventListener('click', async (event) => {
			const button = event.target instanceof Element ? event.target.closest('[data-projectteam-remove]') : null;

			if (!button) {
				return;
			}

			const assignmentId = button.getAttribute('data-projectteam-remove');

			if (!assignmentId || !window.confirm(root.dataset.confirmRemove || 'Remove selected team?')) {
				return;
			}

			try {
				await requestJson(root.dataset.removeUrl, { ...baseParams(), assignment_id: assignmentId }, 'POST');
				const row = button.closest('[data-projectteam-row]');
				row && row.remove();
				setStatus(root, '');
			} catch (error) {
				setStatus(root, error.message || root.dataset.errorText || 'Request failed.', 'danger');
			}
		});
	};

	document.querySelectorAll('[data-jl-projectteam-assignment]').forEach(init);
})();
