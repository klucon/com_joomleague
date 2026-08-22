const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password, JOOMLEAGUE_PROJECT_ID: projectId, JOOMLEAGUE_TEAM_NAME: teamName, JOOMLEAGUE_PERSON_NAME: personName } = process.env;
	if (!baseUrl || !username || !password || !projectId || !teamName || !personName) throw new Error('Joomla credentials and stage fixture values are required.');
	const browser = await chromium.launch({ headless: true });
	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username); await page.locator('#mod-login-password').fill(password); await page.locator('form#form-login button[type="submit"]').click(); await page.waitForLoadState('networkidle');
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=stages&project_id=${projectId}`, { waitUntil: 'networkidle' });
		await page.getByRole('button', { name: 'New' }).click(); await page.waitForLoadState('networkidle');
		await page.getByLabel('Name').fill('Assignment stage'); await page.locator('#jform_code').fill('assignment_stage'); await page.locator('#jform_stage_type').fill('group');
		await page.getByRole('button', { name: 'Save & Close' }).click(); await page.waitForLoadState('networkidle');
		const row = page.getByRole('row').filter({ has: page.getByRole('link', { name: 'Assignment stage', exact: true }) });
		await row.getByRole('link', { name: 'Manage stage participants' }).click(); await page.waitForLoadState('networkidle');
		const team = page.getByRole('checkbox', { name: `Select ${teamName} for this stage` });
		const person = page.getByRole('checkbox', { name: `Select ${personName} for this stage` });
		if (!(await team.isChecked()) || !(await person.isChecked())) throw new Error('Inherited mode must show all project participants selected.');
		await page.locator('label[for="entry-mode-explicit"]').click(); await person.uncheck(); await page.getByRole('button', { name: 'Save' }).click(); await page.waitForLoadState('networkidle');
		if (!(await team.isChecked()) || await person.isChecked()) throw new Error('Explicit stage selection was not persisted.');
		await page.locator('label[for="entry-mode-inherit"]').click(); await page.getByRole('button', { name: 'Save' }).click(); await page.waitForLoadState('networkidle');
		if (!(await team.isChecked()) || !(await person.isChecked())) throw new Error('Returning to inherited mode did not restore effective participants.');
	} finally { await browser.close(); }
	console.log('Stage participant inheritance and explicit assignment workflow OK');
})();
