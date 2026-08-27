const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password, JOOMLEAGUE_PROJECT_ID: projectId } = process.env;
	if (!baseUrl || !username || !password || !projectId) throw new Error('Joomla credentials and JOOMLEAGUE_PROJECT_ID are required.');
	const browser = await chromium.launch({ headless: true });
	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		const confirmDelete = async () => {
			const native = page.waitForEvent('dialog', { timeout: 2000 }).then(async dialog => dialog.accept()).catch(() => null);
			await page.getByRole('button', { name: 'Delete' }).click(); const yes = page.getByRole('button', { name: 'Yes', exact: true });
			if (await yes.waitFor({ state: 'visible', timeout: 2500 }).then(() => true).catch(() => false)) await yes.click(); else await native;
			await page.waitForLoadState('networkidle');
		};
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' }); await page.locator('#mod-login-username').fill(username); await page.locator('#mod-login-password').fill(password); await page.locator('form#form-login button[type="submit"]').click(); await page.waitForLoadState('networkidle');
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=stages&project_id=${projectId}`, { waitUntil: 'networkidle' }); await page.getByRole('button', { name: 'New' }).click(); await page.waitForLoadState('networkidle');
		await page.getByLabel('Name').fill('Round fixture stage'); await page.locator('#jform_code').fill('round_fixture_stage'); await page.locator('#jform_stage_type').fill('league_phase'); await page.getByRole('button', { name: 'Save & Close' }).click(); await page.waitForLoadState('networkidle');
		const stageRow = page.getByRole('row').filter({ has: page.getByRole('link', { name: 'Round fixture stage', exact: true }) }); await stageRow.getByRole('link', { name: 'Manage stage rounds' }).click(); await page.waitForLoadState('networkidle');
		await page.getByRole('button', { name: 'New' }).click(); await page.waitForLoadState('networkidle');
		await page.getByLabel('Name').fill('Round 1'); await page.locator('#jform_code').fill('round_1'); await page.getByLabel('Round type').fill('standard'); await page.getByLabel('Round number').fill('1'); await page.getByLabel('Start date').fill('2026-08-15'); await page.getByLabel('End date').fill('2026-08-16');
		await page.getByRole('button', { name: 'Save & Close' }).click(); await page.waitForLoadState('networkidle');
		const roundRow = page.getByRole('row').filter({ has: page.getByRole('link', { name: 'Round 1', exact: true }) }); await roundRow.getByRole('link', { name: 'Round 1', exact: true }).click(); await page.waitForLoadState('networkidle');
		if (await page.locator('.main-card joomla-tab-element').count() !== 3) throw new Error('Round form must contain three Joomla tabs.');
		await page.getByLabel('Name').fill('Opening round'); await page.getByRole('button', { name: 'Save & Close' }).click(); await page.waitForLoadState('networkidle');
		const editedRow = page.getByRole('row').filter({ has: page.getByRole('link', { name: 'Opening round', exact: true }) }); await editedRow.getByRole('checkbox').check(); await confirmDelete();
		if (await page.getByRole('link', { name: 'Opening round', exact: true }).count()) {
			const alerts = await page.locator('.alert').allTextContents(); const task = await page.locator('input[name="task"]').inputValue().catch(() => 'missing');
			throw new Error(`Temporary round was not deleted at ${page.url()}; task=${task}; alerts=${alerts.join(' | ')}`);
		}
	} finally { await browser.close(); }
	console.log('Stage-owned round create/edit/delete workflow OK');
})();
