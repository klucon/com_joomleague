const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password } = process.env;
	const eventId = process.env.JOOMLEAGUE_EVENT_ID;
	if (!baseUrl || !username || !password || !eventId) throw new Error('Joomla URL, credentials and event ID are required.');
	const browser = await chromium.launch({ headless: true });
	try {
		for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 844 }]) {
			const page = await browser.newPage({ viewport });
			const url = `${baseUrl}/index.php?option=com_joomleague&view=eventreport&event_id=${eventId}`;
			await page.goto(url, { waitUntil: 'networkidle' });
			if (await page.locator('#username').isVisible().catch(() => false)) {
				await page.locator('#username').fill(username);
				await page.locator('#passwd').fill(password);
				await Promise.all([page.waitForLoadState('networkidle'), page.locator('form button[type="submit"]').click()]);
				await page.goto(url, { waitUntil: 'networkidle' });
			}
			const link = page.locator('section', { hasText: /Refereeing and competition staff|Rozhodčí a soutěžní personál/ }).locator('a[href*="view=person"], a[href*="/person/"]').first();
			if (!(await link.isVisible())) throw new Error('The event official does not link to a person profile.');
			const lineupHeading = page.getByRole('heading', { name: /Line-up|Starting list|Sestava \/ startovní listina/ }).first();
			if (await lineupHeading.count()) {
				const firstLineup = lineupHeading.locator('..').locator('.card').first().locator('.list-group li');
				const firstPlayerRole = await firstLineup.locator('small').first().innerText();
				if (!/Goalkeeper|Brankář/i.test(firstPlayerRole)) throw new Error(`The football line-up does not start with its first profile position: ${firstPlayerRole}`);
				const nameOffsets = await firstLineup.locator('a[href*="view=person"], a[href*="/person/"]').evaluateAll((links) => links.slice(0, 8).map((link) => Math.round(link.getBoundingClientRect().left)));
				if (nameOffsets.length > 1 && new Set(nameOffsets).size !== 1) throw new Error(`Line-up names are not aligned: ${nameOffsets.join(', ')}`);
			}
			const body = await page.locator('body').innerText();
			if (/COM_JOOMLEAGUE_[A-Z0-9_]+|Notice:|Warning:|Fatal error|Call Stack/.test(body)) throw new Error('The event report contains an untranslated key or runtime error.');
			await page.close();
		}
	} finally {
		await browser.close();
	}
	console.log('Event-report official profile link OK');
})().catch((error) => { console.error(error); process.exit(1); });
