const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password, JOOMLEAGUE_ROUND_ID: roundId } = process.env;
	if (!baseUrl || !username || !password || !roundId) throw new Error('Joomla credentials and JOOMLEAGUE_ROUND_ID are required.');

	const browser = await chromium.launch({ headless: true });
	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username);
		await page.locator('#mod-login-password').fill(password);
		await page.locator('form#form-login button[type="submit"]').click();
		await page.waitForLoadState('networkidle');
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=matches&round_id=${roundId}`, { waitUntil: 'networkidle' });

		const table = page.locator('#matchList');
		const tokenName = await table.getAttribute('data-csrf-token');
		if (!tokenName || tokenName === 'round_id') throw new Error('The match list did not expose an explicit Joomla CSRF token.');

		const row = table.locator('tbody tr[data-editable="1"]').first();
		const participant = row.locator('select[data-schedule-field="participant_slot_1"]');
		const current = await participant.inputValue();
		if (!current) throw new Error('The selected match has no first participant.');

		const responsePromise = page.waitForResponse(response => response.url().includes('task=matches.saveInline'));
		await participant.selectOption(current);
		const response = await responsePromise;
		const payload = await response.json();
		if (!response.ok() || payload.success === false) throw new Error(payload.message || `Autosave failed with HTTP ${response.status()}.`);
		await row.locator('[data-save-status]').filter({ hasText: /Saved|Ulozeno|Ulozeno/i }).waitFor({ timeout: 5000 }).catch(async () => {
			if (!/saved|ulo/i.test(await row.locator('[data-save-status]').innerText())) throw new Error('Autosave success state was not rendered.');
		});
	} finally {
		await browser.close();
	}

	console.log('Match inline autosave CSRF verification passed.');
})();
