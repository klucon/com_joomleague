const initialiseEntryMemberForm = () => {
	const type = document.getElementById('jform_member_person_type');
	const role = document.getElementById('jform_role_code');

	if (!type || !role) return;

	const options = Array.from(role.options).map((option) => option.cloneNode(true));
	const updateRoles = () => {
		const selected = role.value;
		role.replaceChildren(...options
			.filter((option) => !option.value || option.dataset.personType === type.value)
			.map((option) => option.cloneNode(true)));
		role.value = Array.from(role.options).some((option) => option.value === selected) ? selected : '';
	};

	type.addEventListener('change', updateRoles);
	updateRoles();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initialiseEntryMemberForm);
} else {
	initialiseEntryMemberForm();
}
