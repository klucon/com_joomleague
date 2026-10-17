const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password, JOOMLEAGUE_CLUB_ID: clubId } = process.env;
	if (!baseUrl || !username || !password || !clubId) throw new Error('Joomla URL, credentials and club ID are required.');
	const browser = await chromium.launch({ headless: true });
	try {
		for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 844 }]) {
			const page = await browser.newPage({ viewport });
			await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'domcontentloaded' });
			await page.locator('#mod-login-username').fill(username);
			await page.locator('#mod-login-password').fill(password);
			await Promise.all([page.waitForURL(/administrator\/index\.php/), page.locator('form#form-login button[type="submit"]').click()]);
			const url = `${baseUrl}/index.php?option=com_joomleague&view=clubpeople&club_id=${clubId}`;
			await page.goto(url, { waitUntil: 'networkidle' });
			if (await page.locator('#username').isVisible().catch(() => false)) {
				await page.locator('#username').fill(username); await page.locator('#passwd').fill(password);
				await Promise.all([page.waitForLoadState('networkidle'), page.locator('form button[type="submit"]').click()]);
				await page.goto(url, { waitUntil: 'networkidle' });
			}
			const body = await page.locator('body').innerText();
			if (!/People of|Osoby klubu/i.test(body)) throw new Error('Club people heading is missing.');
			if (/COM_JOOMLEAGUE_[A-Z0-9_]+|Notice:|Warning:|Fatal error|Call Stack/.test(body)) throw new Error('Club people contains an untranslated key or runtime error.');
			const rows = page.locator('.com-joomleague-clubpeople tbody tr');
			if ((await rows.count()) !== 25) throw new Error(`The default club people page size is not 25 (rows=${await rows.count()}).`);
			const appearancesSort = page.locator('.com-joomleague-clubpeople thead a[href*="sort=appearances"]');
			if ((await appearancesSort.count()) !== 1) throw new Error('The sortable Appearances heading is missing.');
			await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), appearancesSort.click()]);
			if (new URL(page.url()).searchParams.get('sort') !== 'appearances' || new URL(page.url()).searchParams.get('direction') !== 'asc') throw new Error(`People sorting did not select Appearances ascending: ${page.url()}`);
			await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.locator('.com-joomleague-clubpeople thead a[href*="sort=appearances"]').click()]);
			if (new URL(page.url()).searchParams.get('direction') !== 'desc') throw new Error('People sorting did not toggle to descending.');
			const values = await page.locator('.com-joomleague-clubpeople tbody tr td:nth-child(3)').allTextContents();
			const numbers = values.map((value) => Number.parseInt(value.trim(), 10));
			if (numbers.some((value, index) => index > 0 && numbers[index - 1] < value)) throw new Error('Appearance values are not sorted descending.');
			const componentBounds = await page.locator('.com-joomleague-clubpeople').evaluate((element) => { const rect = element.getBoundingClientRect(); return { left: rect.left, right: rect.right, viewport: document.documentElement.clientWidth }; });
			if (componentBounds.left < -1 || componentBounds.right > componentBounds.viewport + 1) throw new Error(`Club people exceeds the ${viewport.width}px viewport.`);
			await page.close();
		}
	} finally { await browser.close(); }
	console.log('Club people sorting and desktop/mobile output OK');
})().catch((error) => { console.error(error); process.exit(1); });
