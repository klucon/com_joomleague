const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password, JOOMLEAGUE_PROJECT_ID: projectId } = process.env;
	if (!baseUrl || !username || !password || !projectId) throw new Error('Joomla credentials and JOOMLEAGUE_PROJECT_ID are required.');
	const browser = await chromium.launch({ headless: true });
	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=dashboard`, { waitUntil: 'networkidle' });
		if (await page.locator('#mod-login-username').count()) { await page.locator('#mod-login-username').fill(username); await page.locator('#mod-login-password').fill(password); await page.locator('form#form-login button[type="submit"]').click(); await page.waitForLoadState('networkidle'); }
		const urls = [
			`${baseUrl}/administrator/index.php?option=com_joomleague&view=stages&project_id=${projectId}`,
			`${baseUrl}/administrator/index.php?option=com_joomleague&view=stagetransitions&project_id=${projectId}`,
			`${baseUrl}/administrator/index.php?option=com_joomleague&task=stagetransition.add&project_id=${projectId}`,
		];
		for (const [index, url] of urls.entries()) {
			await page.goto(url, { waitUntil: 'networkidle' });
			const body = await page.locator('body').innerText();
			if (/COM_JOOMLEAGUE_[A-Z0-9_]+|\b(?:Notice|Warning|Fatal error):/.test(body)) throw new Error(`Stage progression page is invalid at ${page.url()}: ${body.slice(0, 1400)}`);
			if (index > 0 && await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)) throw new Error(`Stage progression page overflows at ${page.url()}.`);
		}
		for (const field of ['#jform_source_stage_id', '#jform_target_stage_id', '#jform_selector_type', '#jform_rank_from', '#jform_rank_to', '#jform_standing_scope', '#jform_match_outcome', '#jform_source_round_id', '#jform_carry_over_mode']) if (await page.locator(field).count() !== 1) throw new Error(`Missing stage progression field ${field}.`);
		if (await page.locator('textarea[name="jform[selector_config_json]"]').count()) throw new Error('Internal transition JSON is exposed to administrators.');
	} finally { await browser.close(); }
	console.log('Stage progression administration OK');
})();
