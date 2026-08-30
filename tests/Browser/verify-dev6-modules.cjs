const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const pagePath = process.env.JOOMLA_MODULE_TEST_PATH;

	if (!baseUrl || !pagePath) {
		throw new Error('Module browser test environment is incomplete.');
	}

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

			const aside = page.locator('aside.jl-aside');
			await aside.waitFor();
			const text = await aside.innerText();
			const expected = [
				'No future programme event is available.',
				'Stonebridge Foxes',
				'Active members: 15',
				'Officials',
				'Stonebridge Foxes Arena',
				'Running Race Demo League 2025/2026',
			];

			if (await aside.locator('nav[aria-label="Competition navigation"]').count() !== 1) {
				throw new Error(`Competition navigation module is missing at ${viewport.name}.`);
			}

			for (const value of expected) {
				if (!text.includes(value)) {
					throw new Error(`Expected module content is missing at ${viewport.name}: ${value}`);
				}
			}

			if (/MOD_JOOMLEAGUE_[A-Z0-9_]+|COM_JOOMLEAGUE_[A-Z0-9_]+/.test(text)) {
				throw new Error(`An untranslated language key is visible at ${viewport.name}.`);
			}

			const overflow = await aside.evaluate(
				(element) => element.scrollWidth - element.clientWidth,
			);

			if (overflow > 1) {
				const offenders = await aside.evaluate((container) => [...container.querySelectorAll('*')]
					.map((element) => {
						const rect = element.getBoundingClientRect();
						const containerRect = container.getBoundingClientRect();

						return {
							tag: element.tagName,
							className: typeof element.className === 'string' ? element.className : '',
							text: (element.textContent || '').trim().slice(0, 80),
							right: Math.round(rect.right - containerRect.right),
							width: Math.round(rect.width),
						};
					})
					.filter((item) => item.right > 1)
					.slice(0, 10));
				throw new Error(`Module area overflows by ${overflow}px at ${viewport.name}: ${JSON.stringify(offenders)}`);
			}

			const links = await aside.locator('a[href*="com_joomleague"]').evaluateAll((items) => [
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

			await page.screenshot({
				path: `/mnt/disk-b/server-backups/joomleague-release/dev6-modules-${viewport.name}.png`,
				fullPage: true,
			});

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
