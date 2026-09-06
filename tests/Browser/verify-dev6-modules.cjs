const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL || 'https://demo.joomleague.eu';
	const pagePath = process.env.JOOMLA_MODULE_TEST_PATH || '/';

	const browser = await chromium.launch({ headless: true });

	try {
		for (const viewport of [
			{ width: 1440, height: 1000, name: 'desktop' },
			{ width: 390, height: 844, name: 'mobile' },
		]) {
			const page = await browser.newPage({ viewport });
			const errors = [];

			page.on('pageerror', (error) => errors.push(error.message));
			page.on('console', (message) => {
				if (message.type() === 'error') {
					errors.push(message.text());
				}
			});

			const response = await page.goto(new URL(pagePath, baseUrl).toString(), {
				waitUntil: 'networkidle',
			});

			if (!response || response.status() !== 200) {
				throw new Error(`Module test page returned ${response ? response.status() : 'no response'}.`);
			}

			const moduleArea = page.locator('main, .jl-competition-nav');
			const text = await moduleArea.allInnerTexts().then((parts) => parts.join('\n'));
			const moduleSelectors = [
				'.mod-joomleague-next-event',
				'.mod-joomleague-standings',
				'.mod-joomleague-calendar',
				'.mod-joomleague-programme-ticker',
				'.mod-joomleague-latest-results',
				'.mod-joomleague-program',
				'.mod-joomleague-birthdays',
				'.mod-joomleague-statranking',
				'.mod-joomleague-eventranking',
				'.mod-joomleague-spotlight',
			];
			const moduleHeadings = [
				'At Westmoor Arena',
				'Featured team',
				'Westmoor Football Club',
				'Coaching and officials',
				'Explore competitions',
			];

			if (await page.locator('nav[aria-label="Competition navigation"]').count() !== 1) {
				throw new Error(`Competition navigation module is missing at ${viewport.name}.`);
			}

			for (const selector of moduleSelectors) {
				if (await page.locator(selector).count() !== 1) {
					throw new Error(`Published module ${selector} is missing or duplicated at ${viewport.name}.`);
				}
			}

			for (const heading of moduleHeadings) {
				if (await page.getByRole('heading', { name: heading, exact: true }).count() !== 1) {
					throw new Error(`Published module heading is missing at ${viewport.name}: ${heading}`);
				}
			}

			if (/MOD_JOOMLEAGUE_[A-Z0-9_]+|COM_JOOMLEAGUE_[A-Z0-9_]+/.test(text)) {
				throw new Error(`An untranslated language key is visible at ${viewport.name}.`);
			}

			const overflow = await page.evaluate(
				() => document.documentElement.scrollWidth - document.documentElement.clientWidth,
			);

			if (overflow > 1) {
				const offenders = await page.evaluate(() => [...document.querySelectorAll('body *')]
					.map((element) => {
						const rect = element.getBoundingClientRect();

						return {
							tag: element.tagName,
							className: typeof element.className === 'string' ? element.className : '',
							text: (element.textContent || '').trim().slice(0, 80),
							right: Math.round(rect.right),
							width: Math.round(rect.width),
						};
					})
					.filter((item) => item.right > document.documentElement.clientWidth + 1)
					.slice(0, 10));
				throw new Error(`Module page overflows by ${overflow}px at ${viewport.name}: ${JSON.stringify(offenders)}`);
			}

			const links = await moduleArea.locator('a[href*="com_joomleague"]').evaluateAll((items) => [
				...new Set(items.map((item) => item.href)),
			]);

			if (links.length < 20) {
				throw new Error(`Only ${links.length} JoomLeague module links were rendered at ${viewport.name}.`);
			}

			for (const link of links) {
				const linkedResponse = await page.request.get(link);

				if (linkedResponse.status() !== 200) {
					throw new Error(`Module link returned ${linkedResponse.status()}: ${link}`);
				}
			}

			if (errors.length > 0) {
				throw new Error(`Browser errors at ${viewport.name}: ${errors.join('; ')}`);
			}

			await page.close();
		}

		console.log('JoomLeague dev6 module browser verification passed.');
	} finally {
		await browser.close();
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
