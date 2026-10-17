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
			const url = `${baseUrl}/index.php?option=com_joomleague&view=clubrivals&club_id=${clubId}`;
			await page.goto(url, { waitUntil: 'networkidle' });
			if (await page.locator('#username').isVisible().catch(() => false)) {
				await page.locator('#username').fill(username); await page.locator('#passwd').fill(password);
				await Promise.all([page.waitForLoadState('networkidle'), page.locator('form button[type="submit"]').click()]);
				await page.goto(url, { waitUntil: 'networkidle' });
			}
			const body = await page.locator('body').innerText();
			if (!/Rivals of|Soupeři klubu/i.test(body)) throw new Error(`Club rival history heading is missing: ${JSON.stringify(await page.locator('h1').allInnerTexts())}`);
			if ((await page.locator('#clubrivals-project').count()) !== 1 || (await page.locator('#clubrivals-season').count()) !== 1 || (await page.locator('#clubrivals-type').count()) !== 1) throw new Error('Club rival filters are incomplete.');
			if (/COM_JOOMLEAGUE_[A-Z0-9_]+|Notice:|Warning:|Fatal error|Call Stack/.test(body)) throw new Error('Club rival history contains an untranslated key or runtime error.');
			const overviewRows = page.locator('.com-joomleague-clubrivals > .table-responsive tbody tr');
			if ((await overviewRows.count()) !== 25) throw new Error('The default club rival page size is not 25.');
			const playedSort = page.locator('.com-joomleague-clubrivals thead a[href*="sort=played"]');
			if ((await playedSort.count()) !== 1) throw new Error('The sortable Played heading is missing.');
			await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), playedSort.click()]);
			if (new URL(page.url()).searchParams.get('sort') !== 'played' || new URL(page.url()).searchParams.get('direction') !== 'asc') throw new Error('Rival sorting did not select Played ascending.');
			await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.locator('.com-joomleague-clubrivals thead a[href*="sort=played"]').click()]);
			if (new URL(page.url()).searchParams.get('direction') !== 'desc') throw new Error('Rival sorting did not toggle to descending.');
			await page.goto(url, { waitUntil: 'networkidle' });
			await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.locator('#clubrivals-limit').selectOption('10')]);
			if ((await overviewRows.count()) !== 10 || new URL(page.url()).searchParams.get('limit') !== '10') throw new Error(`The 10-item club rival page size was not applied (rows=${await overviewRows.count()}, url=${page.url()}).`);
			const secondPageHref = await page.locator('a').evaluateAll((links) => links.map((link) => link.href).find((href) => new URL(href).searchParams.get('start') === '10') || '');
			if (secondPageHref === '') throw new Error('The second club rival page link is missing.');
			await page.goto(secondPageHref, { waitUntil: 'networkidle' });
			if (new URL(page.url()).searchParams.get('start') !== '10' || (await overviewRows.count()) !== 10) throw new Error('Club rival pagination did not open the second page.');
			await page.goto(url, { waitUntil: 'networkidle' });
			const firstRival = page.locator('.com-joomleague-clubrivals tbody th a').first();
			if ((await firstRival.count()) < 1) throw new Error(`Club rival history has no opponent links (url=${page.url()}, project=${await page.locator('#clubrivals-project').inputValue()}, season=${await page.locator('#clubrivals-season').inputValue()}, type=${await page.locator('#clubrivals-type').inputValue()}).`);
			await Promise.all([page.waitForLoadState('networkidle'), firstRival.click()]);
			const detailBody = await page.locator('body').innerText();
			if (!/filter_opponent_club_id=\d+/.test(page.url()) || !/Event history|Historie událostí/i.test(detailBody)) throw new Error('Club rival match history did not open.');
			if (/COM_JOOMLEAGUE_[A-Z0-9_]+|Notice:|Warning:|Fatal error|Call Stack/.test(detailBody)) throw new Error('Club rival detail contains an untranslated key or runtime error.');
			const componentBounds = await page.locator('.com-joomleague-clubrivals').evaluate((element) => { const rect = element.getBoundingClientRect(); return { left: rect.left, right: rect.right, viewport: document.documentElement.clientWidth }; });
			if (componentBounds.left < -1 || componentBounds.right > componentBounds.viewport + 1) throw new Error(`Club rival history exceeds the ${viewport.width}px viewport.`);
			await page.close();
		}
	} finally { await browser.close(); }
	console.log('Club rival history desktop/mobile output OK');
})().catch((error) => { console.error(error); process.exit(1); });
