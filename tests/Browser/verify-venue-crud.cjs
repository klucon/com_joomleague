const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;
	if (!baseUrl || !username || !password) throw new Error('JOOMLA_BASE_URL, JOOMLA_USERNAME and JOOMLA_PASSWORD are required.');

	const venueName = `JoomLeague Venue CRUD ${Date.now()}`;
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
		console.log('Authenticated');

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=venue&layout=edit`, { waitUntil: 'networkidle' });
		if ((await page.locator('.main-card > joomla-tab > joomla-tab-element').count()) !== 6) throw new Error('Venue editor must have six tabs.');
		await page.getByLabel('Name *', { exact: true }).fill(venueName);
		await page.getByLabel('Short name', { exact: true }).fill('CRUD Venue');
		await page.getByRole('tab', { name: 'Location' }).click();
		if ((await page.getByLabel('Time zone', { exact: true }).locator('option:checked').textContent()).trim() !== 'Use Default (system settings)') throw new Error('Venue time zone must default to Joomla system settings.');
		await page.getByLabel('City', { exact: true }).fill('Brno');
		await page.getByLabel('Country code', { exact: true }).fill('CZ');
		await page.getByLabel('Latitude', { exact: true }).fill('49.1950602');
		await page.getByLabel('Longitude', { exact: true }).fill('16.6068371');
		await page.getByRole('tab', { name: 'Facilities' }).click();
		await page.getByLabel('Capacity', { exact: true }).fill('1250');
		await Promise.all([
			page.waitForURL(/view=venues/),
			page.getByRole('button', { name: 'Save & Close' }).click(),
		]);
		console.log('Venue saved');

		const row = page.getByRole('row').filter({ hasText: venueName });
		await row.getByRole('checkbox').check();
		console.log('Venue selected');
		await page.getByRole('button', { name: 'Delete' }).click();
		console.log('Delete confirmation opened');
		await Promise.all([
			page.waitForResponse((response) => response.request().method() === 'POST' && response.url().includes('option=com_joomleague')),
			page.getByRole('dialog').getByRole('button', { name: 'Yes' }).click(),
		]);
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=venues`, { waitUntil: 'domcontentloaded' });
		console.log('Venue delete submitted');
		if (await page.getByRole('row').filter({ hasText: venueName }).count()) throw new Error('Temporary venue was not deleted.');

		await page.setViewportSize({ width: 390, height: 844 });
		for (const route of ['view=venues', 'view=venue&layout=edit']) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&${route}`, { waitUntil: 'networkidle' });
			if (await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)) throw new Error(`${route} overflows horizontally on mobile.`);
		}
	} finally {
		await browser.close();
	}

	console.log('Venue create/delete and responsive workflow OK');
})();
