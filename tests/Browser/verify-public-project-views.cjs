const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const projectsPath = process.env.JOOMLA_PROJECTS_MENU_PATH;
	const projectPath = process.env.JOOMLA_PROJECT_MENU_PATH;
	const participantsPath = process.env.JOOMLA_PARTICIPANTS_MENU_PATH;
	const expectedProject = process.env.JOOMLA_EXPECTED_PROJECT;
	const expectedParticipant = process.env.JOOMLA_EXPECTED_PARTICIPANT;

	if (!baseUrl || !projectsPath || !projectPath || !participantsPath || !expectedProject || !expectedParticipant) {
		throw new Error('Public project view browser test environment is incomplete.');
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
				|| !/Účastníci|Participants/.test(projectText)
				|| !/Program a výsledky|Programme and results/.test(projectText)
				|| !/Pořadí|Standings/.test(projectText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(projectText)) {
				throw new Error(`Competition overview is incomplete or untranslated at ${viewport.width}px.`);
			}

			const detailOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
			if (detailOverflow > 1 || errors.length > 0) {
				throw new Error(`Competition overview failed at ${viewport.width}px: overflow=${detailOverflow}, errors=${errors.join('; ')}`);
			}

			await page.goto(new URL(participantsPath, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
			await page.locator('main').waitFor();
			const participantsText = await page.locator('main').innerText();
			const participantsOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

			if (!participantsText.includes(expectedProject)
				|| !participantsText.includes(expectedParticipant)
				|| !/Tým|Team|Jednotlivec|Individual|Skupina|Group/.test(participantsText)
				|| /COM_JOOMLEAGUE_[A-Z0-9_]+/.test(participantsText)
				|| participantsOverflow > 1
				|| errors.length > 0) {
				throw new Error(`Competition participants failed at ${viewport.width}px: overflow=${participantsOverflow}, errors=${errors.join('; ')}`);
			}

			await page.close();
		}

		console.log('Public competition catalogue, overview and participants passed desktop and mobile menu routes.');
	} finally {
		await browser.close();
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
