const initialiseEntryMemberForm = () => {
	const type = document.getElementById('jform_member_person_type');
	const role = document.getElementById('jform_role_code');

	if (!type || !role) return;

	const options = Array.from(role.options).map((option) => option.cloneNode(true));
	const person = document.getElementById('jform_person_id');
	const search = document.getElementById('entrymember-person-search');
	const all = document.getElementById('entrymember-person-all');
	const status = document.getElementById('entrymember-person-status');
	const settings = Joomla.getOptions('com_joomleague.entrymember', {});
	let pending;
	let timer;
	let sequence = 0;
	const lookup = async () => {
		const revision = ++sequence;
		pending?.abort();
		if (!person || !search || !type.value) return;
		pending = new AbortController();
		const url = new URL('index.php', window.location.href);
		const token = Joomla.getOptions('csrf.token');
		url.search = new URLSearchParams({option: 'com_joomleague', task: 'entrymember.people', format: 'raw',
			entry_id: settings.entryId, member_id: settings.memberId, member_person_type: type.value,
			term: search.value, all: all.checked ? '1' : '0', [token]: '1'}).toString();
		try {
			const response = await fetch(url, {signal: pending.signal, credentials: 'same-origin', cache: 'no-store'});
			const rows = await response.json();
			if (!response.ok || !Array.isArray(rows)) throw new Error('Lookup failed');
			if (revision !== sequence) return;
			const selected = person.selectedOptions[0];
			const value = person.value;
			const choices = rows.map((row) => new Option(row.text, String(row.value)));
			if (value && !rows.some((row) => String(row.value) === value) && selected) choices.unshift(new Option(selected.textContent, value));
			person.replaceChildren(new Option('', ''), ...choices);
			person.value = value;
			status.textContent = Joomla.Text._('COM_JOOMLEAGUE_ENTRYMEMBER_SEARCH_HELP');
		} catch (error) {
			if (error.name !== 'AbortError' && revision === sequence) status.textContent = Joomla.Text._('COM_JOOMLEAGUE_ENTRYMEMBER_SEARCH_FAILED');
		}
	};
	const updateRoles = () => {
		const selected = role.value;
		role.replaceChildren(...options
			.filter((option) => !option.value || option.dataset.personType === type.value)
			.map((option) => option.cloneNode(true)));
		role.value = Array.from(role.options).some((option) => option.value === selected) ? selected : '';
		for (const field of [person, role]) {
			if (!field) continue;
			field.closest('.control-group').hidden = !type.value;
			field.disabled = !type.value;
		}
	};

	type.addEventListener('change', updateRoles);
	type.addEventListener('change', lookup);
	search?.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(lookup, 250); });
	search?.addEventListener('keydown', (event) => {
		if (event.key === 'Enter') { event.preventDefault(); clearTimeout(timer); lookup(); person?.focus(); }
	});
	all?.addEventListener('change', lookup);
	updateRoles();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initialiseEntryMemberForm);
} else {
	initialiseEntryMemberForm();
}
