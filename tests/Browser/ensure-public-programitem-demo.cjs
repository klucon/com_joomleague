const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password } = process.env;
	if (!baseUrl || !username || !password) throw new Error('Joomla credentials are required.');
	const browser = await chromium.launch({ headless: true });
	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username);
		await page.locator('#mod-login-password').fill(password);
		await page.locator('form#form-login button[type="submit"]').click();
		await page.waitForLoadState('networkidle');

		const saveClose = async (task) => {
			await page.locator(`joomla-toolbar-button[task="${task}"] button`).click();
			await page.waitForLoadState('networkidle');
		};
		const setSelect = async (selector, value) => page.locator(selector).evaluate((element, selected) => {
			element.value = selected;
			element.dispatchEvent(new Event('change', { bubbles: true }));
		}, value);

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=stages&project_id=3`, { waitUntil: 'networkidle' });
		let stageLink = page.getByRole('link', { name: 'Demo main stage', exact: true });
		if (await stageLink.count() === 0) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&task=stage.add&project_id=3`, { waitUntil: 'networkidle' });
			await page.locator('#jform_name').fill('Demo main stage');
			await page.locator('#jform_stage_type').fill('league_phase');
			await page.locator('#jform_sequence_number').fill('1');
			await page.locator('#jform_start_date').fill('2026-08-01');
			await page.locator('#jform_end_date').fill('2026-10-31');
			await setSelect('#jform_published', '1');
			await saveClose('stage.save');
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=stages&project_id=3`, { waitUntil: 'networkidle' });
			stageLink = page.getByRole('link', { name: 'Demo main stage', exact: true });
		}

		const stageUrl = new URL(await stageLink.getAttribute('href'), baseUrl);
		const stageId = stageUrl.searchParams.get('id');
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=rounds&project_id=3&stage_id=${stageId}`, { waitUntil: 'networkidle' });
		let roundLink = page.getByRole('link', { name: 'Demo round 1', exact: true });
		if (await roundLink.count() === 0) {
			const roundListUrl = new URL(page.url());
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&task=round.add&project_id=3&stage_id=${roundListUrl.searchParams.get('stage_id')}`, { waitUntil: 'networkidle' });
			await page.locator('#jform_name').fill('Demo round 1');
			await page.locator('#jform_code').fill('demo_round_1');
			await page.locator('#jform_round_type').fill('standard');
			await page.locator('#jform_sequence_number').fill('1');
			await setSelect('#jform_published', '1');
			await saveClose('round.save');
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=rounds&project_id=3&stage_id=${stageId}`, { waitUntil: 'networkidle' });
			roundLink = page.getByRole('link', { name: 'Demo round 1', exact: true });
		}

		const roundUrl = new URL(await roundLink.getAttribute('href'), baseUrl);
		const roundId = roundUrl.searchParams.get('id');
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=matches&project_id=3&stage_id=${stageId}&round_id=${roundId}`, { waitUntil: 'networkidle' });
		if (await page.locator('input[value="DEMO-001"]').count() === 0) {
			const matchListUrl = new URL(page.url());
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&task=match.add&project_id=3&stage_id=${matchListUrl.searchParams.get('stage_id')}&round_id=${matchListUrl.searchParams.get('round_id')}`, { waitUntil: 'networkidle' });
			await page.locator('#jform_match_number').fill('DEMO-001');
			await page.locator('#jform_contest_type').selectOption('head_to_head');
			await page.locator('#jform_participant_slot_1').selectOption({ label: 'Demo North' });
			await page.locator('#jform_participant_slot_2').selectOption({ label: 'Demo South' });
			await page.locator('#jform_scheduled_date').fill('2026-09-05');
			await page.locator('#jform_scheduled_time').fill('17:00');
			await page.locator('#jform_duration_minutes').fill('90');
			await page.locator('#jform_venue_id').selectOption('1');
			await page.locator('#jform_status_code').fill('scheduled');
			await setSelect('#jform_published', '1');
			await saveClose('match.save');
		}
		const demoLink = page.locator('input[value="DEMO-001"]');
		if (await demoLink.count() === 0) {
			throw new Error(`Demo programme item was not saved (${page.url()}): ${(await page.locator('joomla-alert,.alert').allTextContents()).join(' | ')}`);
		}
	} finally {
		await browser.close();
	}
	console.log('Public programme item demo data are available.');
})();
