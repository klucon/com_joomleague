const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;

	if (!baseUrl || !username || !password) {
		throw new Error('JOOMLA_BASE_URL, JOOMLA_USERNAME and JOOMLA_PASSWORD are required.');
	}

	const browser = await chromium.launch({ headless: true });

	try {
		for (const viewport of [{ name: 'desktop', width: 1440, height: 1000 }, { name: 'mobile', width: 390, height: 844 }]) {
			const page = await browser.newPage({ viewport });
			await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
			await page.getByLabel('Username').fill(username);
			await page.getByLabel('Password').fill(password);
			await page.getByRole('button', { name: 'Log in' }).click();
			await page.waitForLoadState('networkidle');
			const hideTour = page.getByRole('button', { name: 'Hide Forever' });

			if (await hideTour.isVisible().catch(() => false)) {
				await hideTour.click();
			}

			for (const [name, route, heading, tabCount] of [
				['dashboard', 'index.php?option=com_joomleague&view=dashboard', 'JoomLeague 6.2', 0],
				['project', 'index.php?option=com_joomleague&view=project&layout=edit', 'New Project', 5],
				['competition', 'index.php?option=com_joomleague&view=competition&layout=edit', 'New competition', 3],
				['season', 'index.php?option=com_joomleague&view=season&layout=edit', 'New season', 3],
				['sporttype', 'index.php?option=com_joomleague&view=sporttype&layout=edit', 'New Sport Type', 3],
				['clubs', 'index.php?option=com_joomleague&view=clubs', 'Clubs', 0],
				['club', 'index.php?option=com_joomleague&view=club&layout=edit', 'New Club', 6],
				['teams', 'index.php?option=com_joomleague&view=teams', 'Teams', 0],
				['team', 'index.php?option=com_joomleague&view=team&layout=edit', 'New Team', 5],
				['persons', 'index.php?option=com_joomleague&view=persons', 'Persons', 0],
				['person', 'index.php?option=com_joomleague&view=person&layout=edit', 'New Person', 5],
				['venues', 'index.php?option=com_joomleague&view=venues', 'Venues', 0],
				['venue', 'index.php?option=com_joomleague&view=venue&layout=edit', 'New venue', 6],
				['positions', 'index.php?option=com_joomleague&view=positions', 'Positions', 0],
				['position', 'index.php?option=com_joomleague&task=position.add', 'New Position', 2],
				['events', 'index.php?option=com_joomleague&view=events', 'Event Types', 0],
				['statistics', 'index.php?option=com_joomleague&view=statistics', 'Statistics', 0],
			]) {
				await page.goto(`${baseUrl}/administrator/${route}`, { waitUntil: 'networkidle' });
				await page.getByRole('heading', { name: heading, exact: true }).waitFor();

				if (name === 'dashboard') {
					const sportTypesLink = page.locator('a[href*="option=com_joomleague"][href*="view=sporttypes"]');

					if (await sportTypesLink.locator('.icon-options').count() !== 1) {
						throw new Error('The Sport Types dashboard row must use the Joomla options icon.');
					}

					if (/COM_JOOMLEAGUE_[A-Z0-9_]+/.test(await page.locator('body').innerText())) {
						throw new Error('The dashboard contains an untranslated JoomLeague language constant.');
					}

					if (viewport.name === 'desktop') {
						await page.locator('a[href*="option=com_config"][href*="component=com_joomleague"][href*="return="]').last().click();
						await page.waitForLoadState('networkidle');
						await page.getByRole('button', { name: 'Close', exact: true }).click();
						await page.waitForLoadState('networkidle');

						if (!new URL(page.url()).searchParams.has('view') || new URL(page.url()).searchParams.get('view') !== 'dashboard') {
							throw new Error(`Closing component settings returned to an unexpected URL: ${page.url()}`);
						}
					}
				}

				if (tabCount > 0) {
					const tabs = page.locator('.main-card joomla-tab-element');
					const actualTabCount = await tabs.count();
					if (actualTabCount !== tabCount) {
						const bodyText = (await page.locator('body').innerText()).slice(0, 1500);
						throw new Error(`${name} has ${actualTabCount} tabs instead of ${tabCount} at ${page.url()}. Body: ${bodyText}`);
					}
					if ((await page.locator('.main-card joomla-tab-element[active]').count()) !== 1) throw new Error(`${name} must have exactly one active tab.`);
				}

				await page.screenshot({ path: `/tmp/joomleague-${name}-${viewport.name}.png`, fullPage: true });
				const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);

				if (overflow) {
					const overflowDetails = await page.evaluate(() => ({
						clientWidth: document.documentElement.clientWidth,
						scrollWidth: document.documentElement.scrollWidth,
						elements: Array.from(document.querySelectorAll('body *'))
							.filter((element) => {
								const rect = element.getBoundingClientRect();

								return rect.width > 0 && (rect.right > document.documentElement.clientWidth + 1 || rect.left < -1);
							})
							.slice(-12)
							.map((element) => {
								const rect = element.getBoundingClientRect();

								return `${element.tagName.toLowerCase()}#${element.id}.${element.className}[${Math.round(rect.left)},${Math.round(rect.width)},${Math.round(rect.right)}]`;
							}),
					}));
					throw new Error(`${name} overflows horizontally at ${viewport.name}: ${JSON.stringify(overflowDetails)}`);
				}
			}

			await page.close();
		}
	} finally {
		await browser.close();
	}

	console.log('Administrator layout OK on desktop and mobile viewports');
})();
