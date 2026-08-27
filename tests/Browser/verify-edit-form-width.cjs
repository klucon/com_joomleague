const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;

	if (!baseUrl || !username || !password) {
		throw new Error('JOOMLA_BASE_URL, JOOMLA_USERNAME and JOOMLA_PASSWORD are required.');
	}

	const views = ['club', 'competition', 'event', 'person', 'position', 'project', 'season', 'sporttype', 'statistic', 'team', 'venue'];
	const browser = await chromium.launch({ headless: true });

	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username);
		await page.locator('#mod-login-password').fill(password);
		await page.locator('form#form-login button[type="submit"]').click();
		await page.waitForLoadState('networkidle');

		for (const view of views) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=${view}&layout=edit`, { waitUntil: 'networkidle' });
			const dimensions = await page.evaluate(() => {
				const card = document.querySelector('.main-card');
				const fieldset = Array.from(document.querySelectorAll('.options-form')).find((element) => element.getClientRects().length > 0);

				if (!card || !fieldset) return null;

				return {
					cardWidth: card.getBoundingClientRect().width,
					fieldsetWidth: fieldset.getBoundingClientRect().width,
					overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
				};
			});

			if (!dimensions) throw new Error(`${view} editor has no visible Joomla form fieldset.`);
			if (dimensions.fieldsetWidth / dimensions.cardWidth < 0.9) throw new Error(`${view} editor remains width constrained (${dimensions.fieldsetWidth}/${dimensions.cardWidth}).`);
			if (dimensions.overflow) throw new Error(`${view} editor causes horizontal page overflow.`);
		}
	} finally {
		await browser.close();
	}

	console.log('All core JoomLeague editors use the full available width');
})();
