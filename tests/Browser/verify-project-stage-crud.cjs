const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;
	const projectId = process.env.JOOMLEAGUE_PROJECT_ID;

	if (!baseUrl || !username || !password || !projectId) {
		throw new Error('Joomla credentials and JOOMLEAGUE_PROJECT_ID are required.');
	}

	const browser = await chromium.launch({ headless: true });

	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		const listUrl = `${baseUrl}/administrator/index.php?option=com_joomleague&view=stages&project_id=${projectId}`;
		const saveStage = async (name, code, type, parent = '') => {
			await page.getByRole('button', { name: 'New' }).click();
			await page.waitForLoadState('networkidle');
			if (!(await page.getByLabel('Name').count())) {
				throw new Error(`Stage form did not open at ${page.url()}: ${(await page.locator('body').innerText()).slice(0, 1000)}`);
			}
			await page.getByLabel('Name').fill(name);
			await page.locator('#jform_code').fill(code);
			await page.locator('#jform_stage_type').fill(type);
			if (parent) await page.getByLabel('Parent stage').selectOption({ label: parent });
			await page.getByRole('button', { name: 'Save & Close' }).click();
			await page.waitForLoadState('networkidle');
			const errors = await page.locator('.alert-danger, .alert-error, .alert-warning').allTextContents();
			if (errors.length) throw new Error(`Stage save failed: ${errors.join(' ')}`);
			if (!(await page.getByRole('link', { name, exact: true }).count())) {
				throw new Error(`Saved stage is missing at ${page.url()}: ${(await page.locator('body').innerText()).slice(0, 1200)}`);
			}
		};
		const deleteRow = async (name) => {
			const row = page.getByRole('row').filter({ has: page.getByRole('link', { name, exact: true }) });
			await row.getByRole('checkbox').check();
			const dialog = page.waitForEvent('dialog', { timeout: 2000 }).then(async (item) => item.accept()).catch(() => null);
			await page.getByRole('button', { name: 'Delete' }).click();
			const yes = page.getByRole('button', { name: 'Yes', exact: true });
			if (await yes.waitFor({ state: 'visible', timeout: 2500 }).then(() => true).catch(() => false)) await yes.click();
			else await dialog;
			await page.waitForLoadState('networkidle');
		};

		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username);
		await page.locator('#mod-login-password').fill(password);
		await page.locator('form#form-login button[type="submit"]').click();
		await page.waitForLoadState('networkidle');
		await page.goto(listUrl, { waitUntil: 'networkidle' });
		await saveStage('Fixture group stage', 'fixture_group', 'group_stage');
		await saveStage('Fixture knockout stage', 'fixture_knockout', 'knockout', 'Fixture group stage');

		const childRow = page.getByRole('row').filter({ has: page.getByRole('link', { name: 'Fixture knockout stage', exact: true }) });
		if (!(await childRow.innerText()).includes('Fixture group stage')) throw new Error('The child stage does not show its parent.');
		await childRow.getByRole('link', { name: 'Fixture knockout stage', exact: true }).click();
		await page.waitForLoadState('networkidle');
		if (await page.locator('.main-card joomla-tab-element').count() !== 3) throw new Error('Stage form must contain three Joomla tabs.');
		await page.getByLabel('Sequence number').fill('2');
		await page.getByRole('button', { name: 'Save & Close' }).click();
		await page.waitForLoadState('networkidle');
		await deleteRow('Fixture knockout stage');
		await deleteRow('Fixture group stage');

		if (await page.getByRole('link', { name: /Fixture (group|knockout) stage/ }).count()) {
			throw new Error('Temporary project stages were not deleted.');
		}
	} finally {
		await browser.close();
	}

	console.log('Project stage create/edit/parent/delete workflow OK');
})();
