const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password, JOOMLEAGUE_PROJECT_ID: projectId } = process.env;
	if (!baseUrl || !username || !password || !projectId) throw new Error('Joomla credentials and JOOMLEAGUE_PROJECT_ID are required.');
	const browser = await chromium.launch({ headless: true });
	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=dashboard`, { waitUntil: 'networkidle' });
		if (await page.locator('#mod-login-username').count()) {
			await page.locator('#mod-login-username').fill(username); await page.locator('#mod-login-password').fill(password); await page.locator('form#form-login button[type="submit"]').click(); await page.waitForLoadState('networkidle');
		}
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=stages&project_id=${projectId}`, { waitUntil: 'networkidle' });
		let body = await page.locator('body').innerText();
		if (/\b(?:Notice|Warning):/.test(body)) throw new Error(`Stages emits a PHP diagnostic: ${body.slice(0, 1200)}`);
		const stageRows = page.locator('#stageList tbody tr').filter({ has: page.locator('a[href*="view=standings"][href*="stage_id="]') });
		if (await stageRows.count()) {
			await stageRows.first().locator('a[href*="view=standings"][href*="stage_id="]').click();
			await page.waitForLoadState('networkidle');
			body = await page.locator('body').innerText();
			if (!new URL(page.url()).searchParams.get('stage_id') || /COM_JOOMLEAGUE_[A-Z0-9_]+|\b(?:Notice|Warning):/.test(body)) throw new Error(`Stage standings context is invalid at ${page.url()}: ${body.slice(0, 1200)}`);
			const closeHref = await page.locator('a#toolbar-cancel').getAttribute('href');
			if (!closeHref || new URL(closeHref, page.url()).searchParams.get('view') !== 'stages') throw new Error('Stage standings close action does not return to stages.');
		}
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=standingadjustments&project_id=${projectId}`, { waitUntil: 'networkidle' });
		body = await page.locator('body').innerText();
		if (/COM_JOOMLEAGUE_[A-Z0-9_]+|\b(?:Notice|Warning):/.test(body)) throw new Error(`Adjustment list contains a diagnostic or untranslated key: ${body.slice(0, 1200)}`);
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&task=standingadjustment.add&project_id=${projectId}`, { waitUntil: 'networkidle' });
		if (await page.locator('#jform_project_entry_id').count() !== 1) throw new Error(`Project participant field is missing at ${page.url()}: ${(await page.locator('body').innerText()).slice(0, 1600)}`);
		for (const selector of ['#jform_scope_code', '#jform_metric_code']) if (await page.locator(`${selector} option`).count() < 1) throw new Error(`${selector} has no profile-aware options at ${page.url()}: ${await page.locator(`[name="jform[${selector.slice(7)}]"]`).evaluate((element) => element.outerHTML).catch(() => 'field missing')}`);
		body = await page.locator('body').innerText();
		if (/COM_JOOMLEAGUE_[A-Z0-9_]+|\b(?:Notice|Warning):/.test(body)) throw new Error(`Adjustment form contains a diagnostic or untranslated key: ${body.slice(0, 1200)}`);
	} finally { await browser.close(); }
	console.log('Standing adjustments and stages diagnostics browser regression OK');
})();
