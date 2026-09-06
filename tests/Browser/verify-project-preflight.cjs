const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password, JOOMLEAGUE_PROJECT_ID: projectId } = process.env;
	if (!baseUrl || !username || !password || !projectId) throw new Error('Joomla credentials and JOOMLEAGUE_PROJECT_ID are required.');
	const browser = await chromium.launch({ headless: true });
	try {
		for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 844 }]) {
			const page = await browser.newPage({ viewport });
			for (const [path, expected] of [
				[`view=projectpanel&project_id=${projectId}`, /Project readiness|Připravenost projektu/],
				[`view=projectpreflight&project_id=${projectId}`, /Project is (?:not )?operationally ready|Projekt (?:není|je) provozně připraven/],
			]) {
				const url = `${baseUrl}/administrator/index.php?option=com_joomleague&${path}`;
				await page.goto(url, { waitUntil: 'networkidle' });
				if (await page.locator('#mod-login-username').count()) {
					await page.locator('#mod-login-username').fill(username); await page.locator('#mod-login-password').fill(password);
					await page.locator('form#form-login button[type="submit"]').click(); await page.waitForLoadState('networkidle'); await page.goto(url, { waitUntil: 'networkidle' });
				}
				const body = await page.locator('body').innerText();
				if (!expected.test(body) || /COM_JOOMLEAGUE_[A-Z0-9_]+|Notice:|Warning:|Fatal error/.test(body)) throw new Error(`Invalid preflight output at ${url}.`);
				if (await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)) throw new Error(`Preflight page overflows at ${viewport.width}px.`);
			}
			await page.close();
		}
	} finally { await browser.close(); }
	console.log('Project preflight administration OK');
})().catch((error) => { console.error(error); process.exit(1); });
