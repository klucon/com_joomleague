const { chromium } = require('playwright');

(async () => {
	const {
		JOOMLA_BASE_URL: baseUrl,
		JOOMLA_USERNAME: username,
		JOOMLA_PASSWORD: password,
		JOOMLEAGUE_PROJECT_ID: projectId,
		JOOMLEAGUE_ROUND_ID: roundId,
	} = process.env;

	if (!baseUrl || !username || !password) {
		throw new Error('Joomla credentials are required.');
	}

	const browser = await chromium.launch({ headless: true });
	const page = await browser.newPage({ acceptDownloads: true });
	const pageErrors = [];
	page.on('pageerror', (error) => pageErrors.push(`${page.url()}: ${error.stack || error.message}`));

	try {
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username);
		await page.locator('#mod-login-password').fill(password);
		await page.locator('form#form-login button[type="submit"]').click();
		await page.waitForLoadState('networkidle');

		for (const view of ['positions', 'events', 'statistics']) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=${view}`, { waitUntil: 'networkidle' });
			if (await page.locator('input[name="cid[]"]').count() === 0) {
				for (const task of ['edit', 'publish', 'unpublish', 'checkin', 'delete']) {
					if (await page.locator(`#toolbar-${task}`).count() !== 0) {
						throw new Error(`${view} renders ${task} without selectable rows.`);
					}
				}
			}
		}

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=databasetools`, { waitUntil: 'networkidle' });
		await page.locator('input[name="tables[]"]').first().check();
		const [sqlDownload] = await Promise.all([
			page.waitForEvent('download'),
			page.locator('#toolbar-download button').click(),
		]);
		if (!sqlDownload.suggestedFilename().endsWith('.sql')) throw new Error('Table export did not download an SQL file.');

		if (projectId) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=projectentries&project_id=${projectId}`, { waitUntil: 'networkidle' });
			if ((await page.locator('body').innerText()).includes('Warning: Undefined property')) {
				throw new Error('Project entries renders an undefined-property warning.');
			}

			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=projectschedule&project_id=${projectId}`, { waitUntil: 'networkidle' });
			const [csvDownload] = await Promise.all([
				page.waitForEvent('download'),
				page.locator('#toolbar-download button').click(),
			]);
			if (!csvDownload.suggestedFilename().endsWith('.csv')) throw new Error('Schedule export did not download a CSV file.');
		}

		if (roundId) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=matches&round_id=${roundId}`, { waitUntil: 'networkidle' });
			if (await page.locator('input[name="cid[]"]').count() > 0) {
				await page.locator('button[data-bs-target="#matches-batch-modal"]').click();
				await page.locator('#matches-batch-modal').waitFor({ state: 'visible' });
			}
		}

		if (pageErrors.length > 0) throw new Error(`Toolbar JavaScript errors: ${pageErrors.join(' | ')}`);
	} finally {
		await browser.close();
	}

	console.log('Toolbar actions browser regression passed.');
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
