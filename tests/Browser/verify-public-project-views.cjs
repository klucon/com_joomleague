const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const projectsPath = process.env.JOOMLA_PROJECTS_MENU_PATH;
	const projectPath = process.env.JOOMLA_PROJECT_MENU_PATH;
	const expectedProject = process.env.JOOMLA_EXPECTED_PROJECT;

	if (!baseUrl || !projectsPath || !projectPath || !expectedProject) {
		throw new Error('JOOMLA_BASE_URL, JOOMLA_PROJECTS_MENU_PATH, JOOMLA_PROJECT_MENU_PATH and JOOMLA_EXPECTED_PROJECT are required.');
	}

	const browser = await chromium.launch({ headless: true });

	try {
		for (const viewport of [
			{ width: 1440, height: 1000 },
			{ width: 390, height: 844 },
		]) {
			const page = await browser.newPage({ viewport });
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));

			await page.goto(new URL(projectsPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const projectsText = await page.locator('main').innerText();

			if (!projectsText.includes(expectedProject) || /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(projectsText)) {
				throw new Error(`Competition catalogue is incomplete or untranslated at ${viewport.width}px.`);
			}

			const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
			if (overflow > 1) {
				throw new Error(`Competition catalogue overflows by ${overflow}px at ${viewport.width}px.`);
			}

			await page.goto(new URL(projectPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const projectText = await page.locator('main').innerText();

			if (!projectText.includes(expectedProject)
				|| !/Program a výsledky|Programme and results/.test(projectText)
				|| !/Pořadí|Standings/.test(projectText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(projectText)) {
				throw new Error(`Competition overview is incomplete or untranslated at ${viewport.width}px.`);
			}

			const detailOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
			if (detailOverflow > 1 || errors.length > 0) {
				throw new Error(`Competition overview failed at ${viewport.width}px: overflow=${detailOverflow}, errors=${errors.join('; ')}`);
			}

			await page.close();
		}

		console.log('Public competition catalogue and overview passed desktop and mobile menu routes.');
	} finally {
		await browser.close();
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
