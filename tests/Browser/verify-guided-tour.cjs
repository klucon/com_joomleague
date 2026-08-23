const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;

	if (!baseUrl || !username || !password) {
		throw new Error('JOOMLA_BASE_URL, JOOMLA_USERNAME and JOOMLA_PASSWORD are required.');
	}

	const browser = await chromium.launch({ headless: true });

	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username);
		await page.locator('#mod-login-password').fill(password);
		await page.locator('form#form-login button[type="submit"]').click();
		await page.waitForLoadState('networkidle');
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=dashboard`, { waitUntil: 'networkidle' });

		await page.locator('.header-tours .dropdown-toggle').first().click();
		const startButton = page.locator('.header-tours .button-start-guidedtour').filter({ hasText: /JoomLeague/ }).first();
		await startButton.waitFor();
		await startButton.click();

		const tour = page.locator('.shepherd-element:not([hidden])');
		await tour.waitFor();
		const intro = await tour.innerText();

		if (/COM_JOOMLEAGUE_/.test(intro) || !/JoomLeague/.test(intro)) {
			throw new Error(`Guided tour intro is missing or untranslated: ${intro}`);
		}

		await tour.locator('.shepherd-button-primary').click();

		for (let step = 1; step <= 6; step += 1) {
			await tour.waitFor();
			const text = await tour.innerText();

			if (/COM_JOOMLEAGUE_/.test(text)) {
				throw new Error(`Guided tour step ${step} contains an untranslated key: ${text}`);
			}

			const target = await page.locator('.shepherd-target').count();

			if (target !== 1) {
				throw new Error(`Guided tour step ${step} has ${target} highlighted targets instead of one.`);
			}

			await tour.locator('.shepherd-button-primary').click();
		}

		await page.locator('.shepherd-element:not([hidden])').waitFor({ state: 'detached' });
		console.log('JoomLeague guided tour passed all six translated steps.');
	} finally {
		await browser.close();
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
