const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;
	const projectId = process.env.JOOMLEAGUE_PROJECT_ID;
	const teamName = process.env.JOOMLEAGUE_TEAM_NAME;
	const personName = process.env.JOOMLEAGUE_PERSON_NAME;

	if (!baseUrl || !username || !password || !projectId || !teamName || !personName) {
		throw new Error('Joomla credentials and project-entry fixture values are required.');
	}

	const browser = await chromium.launch({ headless: true });

	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		const confirmDelete = async () => {
			const nativeDialog = page.waitForEvent('dialog', { timeout: 2000 }).then(async (dialog) => {
				await dialog.accept();
				return true;
			}).catch(() => false);
			await page.getByRole('button', { name: 'Delete' }).click();
			const yes = page.getByRole('button', { name: 'Yes', exact: true });
			if (await yes.waitFor({ state: 'visible', timeout: 3000 }).then(() => true).catch(() => false)) {
				await yes.click();
			} else {
				await nativeDialog;
			}
			await page.waitForLoadState('networkidle');
			const errors = await page.locator('.alert-danger, .alert-error').allTextContents();

			if (errors.length) {
				throw new Error(`Delete failed: ${errors.join(' ')}`);
			}
		};
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.getByLabel('Username').fill(username);
		await page.getByLabel('Password').fill(password);
		await page.getByRole('button', { name: 'Log in' }).click();
		await page.waitForLoadState('networkidle');
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=projectentries&project_id=${projectId}`, { waitUntil: 'networkidle' });
		await page.getByRole('button', { name: 'New' }).click();
		await page.waitForLoadState('networkidle');
		await page.getByLabel('Entry type').selectOption('team');
		await page.getByLabel('Team', { exact: true }).selectOption({ label: teamName });
		await page.getByLabel('Entry code').fill('fixture-entry');
		await page.getByRole('button', { name: 'Save & Close' }).click();
		await page.waitForLoadState('networkidle');

		const row = page.getByRole('row').filter({ hasText: teamName });
		await row.getByRole('link', { name: `Manage members of ${teamName}` }).click();
		await page.waitForLoadState('networkidle');
		await page.getByRole('button', { name: 'New' }).click();
		await page.waitForLoadState('networkidle');
		await page.locator('#jform_person_id').selectOption({ label: personName });
		await page.locator('#jform_member_person_type').selectOption('player');
		await page.locator('#jform_role_code').selectOption('goalkeeper');
		await page.locator('#jform_shirt_number').fill('1');
		await page.getByRole('button', { name: 'Save & Close' }).click();
		await page.waitForLoadState('networkidle');
		const memberRow = page.getByRole('row').filter({ hasText: personName.split(', ').reverse().join(' ') });
		await memberRow.getByRole('link').click();
		await page.waitForLoadState('networkidle');
		await page.getByRole('tab', { name: 'Membership status' }).click();
		await page.getByLabel('Valid from').fill('2026-07-01');
		await page.getByRole('button', { name: 'Save & Close' }).click();
		await page.waitForLoadState('networkidle');
		await memberRow.getByRole('checkbox').check();
		if (await page.locator('input[name="boxchecked"]').inputValue() !== '1') {
			throw new Error('Selecting a membership did not update the Joomla list selection state.');
		}
		await confirmDelete();
		if (await page.getByRole('link', { name: personName.split(', ').reverse().join(' '), exact: true }).count()) {
			const buttons = await page.getByRole('button').allTextContents();
			const task = await page.locator('input[name="task"]').inputValue().catch(() => 'missing');
			const deleteHtml = await page.getByRole('button', { name: 'Delete' }).evaluate((node) => node.outerHTML);
			throw new Error(`Temporary project participant membership was not deleted. task=${task}; delete=${deleteHtml}; buttons=${buttons.join('|')}`);
		}
		await page.locator('a[href*="view=projectentries"][href*="project_id="]').click();
		await page.waitForLoadState('networkidle');
		await row.getByRole('link', { name: teamName, exact: true }).click();
		await page.waitForLoadState('networkidle');
		await page.getByRole('tab', { name: 'Competition data' }).click();
		await page.getByLabel('Seed').fill('7');
		await page.getByRole('button', { name: 'Save & Close' }).click();
		await page.waitForLoadState('networkidle');
		await row.getByRole('checkbox').check();
		await confirmDelete();

		if (await page.getByRole('link', { name: teamName, exact: true }).count()) {
			throw new Error('Temporary project participant was not deleted.');
		}
	} finally {
		await browser.close();
	}

	console.log('Project participant and membership create/edit/delete workflow OK');
})();
