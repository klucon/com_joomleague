const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password } = process.env;
	const projectId = process.env.JOOMLEAGUE_PROJECT_ID || '1';
	const matchId = process.env.JOOMLEAGUE_MATCH_ID || '1';
	if (!baseUrl || !username || !password) throw new Error('Joomla browser-test credentials are required.');

	const browser = await chromium.launch({ headless: true });
	try {
		for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 844 }]) {
			const page = await browser.newPage({ viewport });
			await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'domcontentloaded' });
			await page.locator('#mod-login-username').fill(username);
			await page.locator('#mod-login-password').fill(password);
			await Promise.all([page.waitForURL(/administrator\/index\.php/), page.locator('form#form-login button[type="submit"]').click()]);

			for (const [path, expected] of [
				[`view=positions`, /Role domain|Doména role/],
				[`view=projectofficials&project_id=${projectId}`, /Competition roles|Soutěžní role/],
				[`view=matchofficials&match_id=${matchId}`, /Competition roles|Soutěžní role/],
			]) {
				await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&${path}`, { waitUntil: 'networkidle' });
				const body = await page.locator('body').innerText();
				if (!expected.test(body)) throw new Error(`Expected competition-role UI is missing from ${path}.`);
				if (/COM_JOOMLEAGUE_[A-Z0-9_]+|Notice:|Warning:|Fatal error|Call Stack/.test(body)) throw new Error(`Invalid output at ${path}.`);
				const invalidLayout = await page.evaluate(() => [...document.querySelectorAll('#adminForm, #adminForm > .container-fluid, .table-responsive')].some((element) => element.getBoundingClientRect().width > document.documentElement.clientWidth + 1));
				if (invalidLayout) throw new Error(`The component layout exceeds the ${viewport.width}px viewport in ${path}.`);
			}
			await page.close();
		}
	} finally {
		await browser.close();
	}

	console.log('Competition-role administration UI OK');
})().catch((error) => { console.error(error); process.exit(1); });
