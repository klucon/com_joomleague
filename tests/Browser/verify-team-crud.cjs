const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;

	if (!baseUrl || !username || !password) {
		throw new Error('JOOMLA_BASE_URL, JOOMLA_USERNAME and JOOMLA_PASSWORD are required.');
	}

	const teamName = `JoomLeague Team CRUD ${Date.now()}`;
	const browser = await chromium.launch({ headless: true });

	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.getByLabel('Username').fill(username);
		await page.getByLabel('Password').fill(password);
		await page.getByRole('button', { name: 'Log in' }).click();
		await page.waitForLoadState('networkidle');

		const hideTour = page.getByRole('button', { name: 'Hide Forever' });
		if (await hideTour.isVisible().catch(() => false)) await hideTour.click();

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=team&layout=edit`, { waitUntil: 'networkidle' });
		await page.getByLabel('Name *', { exact: true }).fill(teamName);
		await page.getByLabel('Middle name', { exact: true }).fill('Team CRUD');
		await page.getByLabel('Short name', { exact: true }).fill('CRUD');
		await page.getByRole('button', { name: 'Save & Close' }).click();
		await page.waitForLoadState('networkidle');

		const row = page.getByRole('row').filter({ hasText: teamName });
		await row.getByRole('checkbox').check();
		await page.getByRole('button', { name: 'Delete' }).click();
		await page.getByRole('dialog').getByRole('button', { name: 'Yes', exact: true }).click();
		await page.waitForLoadState('networkidle');

		if (await page.getByRole('row').filter({ hasText: teamName }).count()) {
			throw new Error('Temporary team was not deleted.');
		}
	} finally {
		await browser.close();
	}

	console.log('Team create/delete workflow OK');
})();
