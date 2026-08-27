const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;

	if (!baseUrl || !username || !password) throw new Error('JOOMLA_BASE_URL, JOOMLA_USERNAME and JOOMLA_PASSWORD are required.');

	const suffix = String(Date.now());
	const firstName = 'JoomLeague';
	const lastName = `Person ${suffix}`;
	const browser = await chromium.launch({ headless: true });

	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username);
		await page.locator('#mod-login-password').fill(password);
		await page.locator('form#form-login button[type="submit"]').click();
		await page.waitForLoadState('networkidle');

		const hideTour = page.getByRole('button', { name: 'Hide Forever' });
		if (await hideTour.isVisible().catch(() => false)) await hideTour.click();

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=person&layout=edit`, { waitUntil: 'networkidle' });
		await page.getByLabel('First name', { exact: true }).fill(firstName);
		await page.getByLabel('Last name', { exact: true }).fill(lastName);
		await page.getByLabel('Country code', { exact: true }).fill('CZ');
		await page.getByRole('button', { name: 'Save & Close' }).click();
		await page.waitForLoadState('networkidle');

		const row = page.getByRole('row').filter({ hasText: lastName });
		await row.getByRole('checkbox').check();
		await page.getByRole('button', { name: 'Delete' }).click();
		await page.getByRole('dialog').getByRole('button', { name: 'Yes', exact: true }).click();
		await page.waitForLoadState('networkidle');

		if (await page.getByRole('row').filter({ hasText: lastName }).count()) throw new Error('Temporary person was not deleted.');
	} finally {
		await browser.close();
	}

	console.log('Person create/delete workflow OK');
})();
