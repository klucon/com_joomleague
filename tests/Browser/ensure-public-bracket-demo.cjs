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

		const setSelect = async (selector, value) => page.locator(selector).evaluate((element, selected) => {
			element.value = selected;
			element.dispatchEvent(new Event('change', { bubbles: true }));
		}, value);
		const saveClose = async (task) => {
			await page.locator(`joomla-toolbar-button[task="${task}"] button`).click();
			await page.waitForLoadState('networkidle');
		};

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=rounds&project_id=3&stage_id=1`, { waitUntil: 'networkidle' });
		if (await page.getByRole('link', { name: 'Demo final round', exact: true }).count() === 0) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&task=round.add&project_id=3&stage_id=1`, { waitUntil: 'networkidle' });
			await page.locator('#jform_name').fill('Demo final round');
			await page.locator('#jform_code').fill('demo_final_round');
			await page.locator('#jform_round_type').fill('final');
			await page.locator('#jform_sequence_number').fill('2');
			await setSelect('#jform_published', '1');
			await saveClose('round.save');
		}

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=rounds&project_id=3&stage_id=1`, { waitUntil: 'networkidle' });
		const roundLink = page.getByRole('link', { name: 'Demo final round', exact: true });
		await roundLink.waitFor();
		const roundId = new URL(await roundLink.getAttribute('href'), baseUrl).searchParams.get('id');
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=matches&project_id=3&stage_id=1&round_id=${roundId}`, { waitUntil: 'networkidle' });
		if (await page.locator('input[value="BRACKET-002"]').count() === 0) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&task=match.add&project_id=3&stage_id=1&round_id=${roundId}`, { waitUntil: 'networkidle' });
			await page.locator('#jform_match_number').fill('BRACKET-002');
			await page.locator('#jform_contest_type').selectOption('head_to_head');
			await page.locator('#jform_participant_slot_1').selectOption({ label: 'Demo North' });
			await page.locator('#jform_participant_slot_2').selectOption({ label: 'Demo South' });
			await page.locator('#jform_scheduled_date').fill('2026-09-12');
			await page.locator('#jform_scheduled_time').fill('17:00');
			await page.locator('#jform_duration_minutes').fill('90');
			await page.locator('#jform_venue_id').selectOption('1');
			await page.locator('#jform_status_code').fill('scheduled');
			await setSelect('#jform_published', '1');
			await saveClose('match.save');
		}

		await page.goto(`${baseUrl}/administrator/index.php?option=com_menus&view=items&menutype=mainmenu`, { waitUntil: 'networkidle' });
		if (await page.getByRole('link', { name: 'Demo postupový pavouk', exact: true }).count() === 0) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_menus&view=item&layout=edit&menutype=mainmenu`, { waitUntil: 'networkidle' });
			await page.locator('#jform_title').fill('Demo postupový pavouk');
			await page.locator('.js-modal-content-select-field button[data-button-action="select"]').click();
			await page.waitForTimeout(800);
			const picker = page.frames().find((frame) => frame.url().includes('view=menutypes'));
			if (!picker) throw new Error('Joomla menu type picker did not open.');
			await picker.locator('button.accordion-button').filter({ hasText: 'JoomLeague' }).click();
			await picker.locator('a.choose_type[data-request*="bracket"]').click();
			await page.locator('#jform_request_project_id').waitFor();
			await page.locator('#jform_request_project_id').selectOption('3');
			await page.locator('#jform_request_stage_id').selectOption('1');
			await page.locator('#save-group-children-save button').click();
			await page.waitForLoadState('networkidle');
		}

		await page.goto(`${baseUrl}/administrator/index.php?option=com_menus&view=items&menutype=mainmenu`, { waitUntil: 'networkidle' });
		const menuLink = page.getByRole('link', { name: 'Demo postupový pavouk', exact: true });
		await menuLink.waitFor();
		console.log(`BRACKET_MENU_ID=${new URL(await menuLink.getAttribute('href'), baseUrl).searchParams.get('id')}`);
	} finally {
		await browser.close();
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
