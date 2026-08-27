const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const eventId = process.env.JOOMLA_EVENT_ID;
	const expectedParticipants = (process.env.JOOMLA_EXPECTED_PARTICIPANTS || '').split('|').filter(Boolean);

	if (!baseUrl || !eventId || expectedParticipants.length === 0) {
		throw new Error('JOOMLA_BASE_URL, JOOMLA_EVENT_ID and JOOMLA_EXPECTED_PARTICIPANTS are required.');
	}

	const browser = await chromium.launch({ headless: true });
	try {
		for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 844 }]) {
			const page = await browser.newPage({ viewport });
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));
			await page.goto(`${baseUrl}/index.php?option=com_joomleague&view=eventreport&event_id=${eventId}`, { waitUntil: 'networkidle' });
			await page.locator('main .com-joomleague-eventreport').waitFor();
			const text = await page.locator('main').innerText();
			const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

			for (const participant of expectedParticipants) {
				if (!text.includes(participant)) throw new Error(`Participant ${participant} is missing at ${viewport.width}px.`);
			}
			if (/COM_JOOMLEAGUE_[A-Z0-9_]+/.test(text) || overflow > 1 || errors.length > 0) {
				throw new Error(`Event report failed at ${viewport.width}px: overflow=${overflow}, errors=${errors.join('; ')}`);
			}
			await page.screenshot({ path: `/workspace/logs/browser-lab/eventreport-${viewport.width}.png`, fullPage: true });
			await page.close();
		}
		console.log('Public event report OK.');
	} finally {
		await browser.close();
	}
})();
