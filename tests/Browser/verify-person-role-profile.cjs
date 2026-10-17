const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password } = process.env;
	const personId = process.env.JOOMLEAGUE_PERSON_ID;
	if (!baseUrl || !username || !password || !personId) throw new Error('Joomla URL, credentials and person ID are required.');

	const browser = await chromium.launch({ headless: true });
	try {
		for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 844 }]) {
			const page = await browser.newPage({ viewport });
			await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'domcontentloaded' });
			await page.locator('#mod-login-username').fill(username);
			await page.locator('#mod-login-password').fill(password);
			await Promise.all([page.waitForURL(/administrator\/index\.php/), page.locator('form#form-login button[type="submit"]').click()]);
			const profileUrl = `${baseUrl}/index.php?option=com_joomleague&view=person&person_id=${personId}`;
			await page.goto(profileUrl, { waitUntil: 'networkidle' });
			if (await page.locator('#username').isVisible().catch(() => false)) {
				await page.locator('#username').fill(username);
				await page.locator('#passwd').fill(password);
				await Promise.all([page.waitForLoadState('networkidle'), page.locator('form button[type="submit"]').click()]);
				await page.goto(profileUrl, { waitUntil: 'networkidle' });
			}
			const body = await page.locator('body').innerText();
			if (!/Programme assignments|Nominace k položkám programu/.test(body)) throw new Error('The role-aware person profile is missing.');
			if (/COM_JOOMLEAGUE_PERSON_OFFICIATED_SITE_CLUB_BALANCE/.test(body)) throw new Error('The site-club referee balance heading is untranslated.');
			if (/Current competition roles|Aktuální soutěžní role|Competition role history|Historie soutěžních rolí/.test(body)) throw new Error('The redundant public competition-role summary is still visible.');
			if (/COM_JOOMLEAGUE_[A-Z0-9_]+|Notice:|Warning:|Fatal error|Call Stack/.test(body)) throw new Error('The person profile contains an untranslated key or runtime error.');
			const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1);
			if (overflow) throw new Error(`The person profile exceeds the ${viewport.width}px viewport.`);
			await page.close();
		}
	} finally {
		await browser.close();
	}

	console.log('Role-aware public person profile OK');
})().catch((error) => { console.error(error); process.exit(1); });
