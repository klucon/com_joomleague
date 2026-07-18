(function () {
	'use strict';

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
		const status = root.querySelector('[data-teamperson-status]');

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
		const results = root.querySelector('[data-teamperson-results]');

		if (!results) {
			return;
		}

		results.innerHTML = '';

		if (!items.length) {
			const empty = document.createElement('div');
			empty.className = 'jl-projectteam-result jl-projectteam-result--empty';
			empty.textContent = root.dataset.emptyText || 'No matching persons.';
			results.appendChild(empty);
			results.hidden = false;
			return;
		}

		items.forEach((item) => {
			const option = document.createElement('button');
			option.type = 'button';
			option.className = 'jl-projectteam-result';
			option.dataset.personId = item.value;
			option.setAttribute('role', 'option');
			option.textContent = item.text;
			results.appendChild(option);
		});

		results.hidden = false;
	};

	const init = (root) => {
		const input = root.querySelector('[data-teamperson-search]');
		const results = root.querySelector('[data-teamperson-results]');
		const tokenName = root.dataset.token;
		let timer = 0;

		if (!input || !results || !tokenName) {
			return;
		}

		const baseParams = () => ({
			projectteam_id: root.dataset.projectteamId || '0',
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

		const addPerson = async (personId) => {
			try {
				input.disabled = true;
				await requestJson(root.dataset.addUrl, { ...baseParams(), person_id: personId }, 'POST');
				window.location.reload();
			} catch (error) {
				input.disabled = false;
				setStatus(root, error.message || root.dataset.errorText || 'Request failed.', 'danger');
			}
		};

		input.addEventListener('input', search);
		input.addEventListener('keydown', (event) => {
			if (event.key !== 'Enter') {
				return;
			}

			const first = results.querySelector('[data-person-id]');

			if (!first) {
				return;
			}

			event.preventDefault();
			addPerson(first.getAttribute('data-person-id'));
		});

		results.addEventListener('click', (event) => {
			const target = event.target instanceof Element ? event.target.closest('[data-person-id]') : null;

			if (!target) {
				return;
			}

			addPerson(target.getAttribute('data-person-id'));
		});
	};

	document.querySelectorAll('[data-jl-teamperson-assignment]').forEach(init);
})();
