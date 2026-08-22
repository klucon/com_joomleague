const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password } = process.env;
	if (!baseUrl || !username || !password) throw new Error('Joomla credentials are required.');

	const browser = await chromium.launch({ headless: true });
	try {
		for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 844 }]) {
			const page = await browser.newPage({ viewport });
			const url = `${baseUrl}/administrator/index.php?option=com_joomleague&view=matchlineup&match_id=3441`;
			await page.goto(url, { waitUntil: 'networkidle' });
			if (await page.locator('#mod-login-username').count()) {
				await page.locator('#mod-login-username').fill(username);
				await page.locator('#mod-login-password').fill(password);
				await page.locator('form#form-login button[type="submit"]').click();
				await page.waitForLoadState('networkidle');
				await page.goto(url, { waitUntil: 'networkidle' });
			}

			const body = await page.locator('body').innerText();
			if (!body.includes('FC Rakšice A') || !body.includes('Kamil Chalaň') || /COM_JOOMLEAGUE_[A-Z0-9_]+|Notice:|Warning:|Fatal error/.test(body)) {
				throw new Error('The default match participant roster was not rendered correctly.');
			}
			const activeParticipant = page.locator('a.btn-primary', { hasText: 'FC Rakšice A' });
			if (await activeParticipant.count() !== 1) throw new Error('The participant with an available roster was not selected.');
			if (await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)) {
				throw new Error(`Match lineup page overflows at ${viewport.width}px.`);
			}
			await page.close();
		}
	} finally {
		await browser.close();
	}
	console.log('Default match lineup participant selection OK');
})().catch((error) => { console.error(error); process.exit(1); });
