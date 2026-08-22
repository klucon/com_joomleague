const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password } = process.env;
	if (!baseUrl || !username || !password) throw new Error('Joomla credentials are required.');
	const browser = await chromium.launch({ headless: true });
	try {
		for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 844 }]) {
			const page = await browser.newPage({ viewport });
			for (const [view, expected] of [['tools',/Nástroje|Tools/],['databasetools',/Export tabulek|Table exports/],['dataimport',/SQL import/],['diagnostics',/Diagnostika|Diagnostics/],['sportprofiles',/Sportovní profily|Sport Profiles/]]) {
				const url = `${baseUrl}/administrator/index.php?option=com_joomleague&view=${view}`;
				await page.goto(url, { waitUntil: 'networkidle' });
				if (await page.locator('#mod-login-username').count()) {
					await page.locator('#mod-login-username').fill(username);
					await page.locator('#mod-login-password').fill(password);
					await page.locator('form#form-login button[type="submit"]').click();
					await page.waitForLoadState('networkidle');
					await page.goto(url, { waitUntil: 'networkidle' });
				}
				const body = await page.locator('body').innerText();
				if (!expected.test(body) || /COM_JOOMLEAGUE_[A-Z0-9_]+|Notice:|Warning:|Fatal error/.test(body)) throw new Error(`Invalid tools output at ${url}: ${body.slice(0, 500)}`);
				if (await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)) throw new Error(`Tools page overflows at ${viewport.width}px: ${view}`);
			}
			await page.close();
		}
	} finally { await browser.close(); }
	console.log('Administrator tools browser verification passed.');
})().catch((error) => { console.error(error); process.exit(1); });
