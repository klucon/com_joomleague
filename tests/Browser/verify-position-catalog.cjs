const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;
	if (!baseUrl || !username || !password) throw new Error('JOOMLA_BASE_URL, JOOMLA_USERNAME and JOOMLA_PASSWORD are required.');

	const browser = await chromium.launch({ headless: true });
	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'domcontentloaded' });
		await page.getByLabel('Username').fill(username);
		await page.getByLabel('Password').fill(password);
		await Promise.all([page.waitForURL(/administrator\/index\.php/), page.getByRole('button', { name: 'Log in' }).click()]);
		const hideTour = page.getByRole('button', { name: 'Hide Forever' });
		if (await hideTour.isVisible().catch(() => false)) await hideTour.click();

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=sporttype&layout=edit`, { waitUntil: 'domcontentloaded' });
		await page.getByRole('tab', { name: 'Profile data' }).click();
		for (const label of ['Create positions', 'Create event types', 'Create statistics']) {
			const field = page.getByText(label, { exact: true }).locator('..');
			if (!(await field.getByLabel('Yes').isChecked())) throw new Error(`${label} must default to Yes.`);
		}

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=positions`, { waitUntil: 'domcontentloaded' });
		await page.getByRole('heading', { name: 'Positions', exact: true }).waitFor();
		await page.screenshot({ path: '/tmp/joomleague-runtime-positions.png', fullPage: true });
		const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);
		if (overflow) throw new Error('Runtime positions list overflows horizontally.');
	} finally {
		await browser.close();
	}

	console.log('Sport type initialization controls and runtime position catalog OK');
})();
