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

		for (let number = 1; number <= 8; number += 1) {
			const name = `Demo Team ${number}`;
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=teams`, { waitUntil: 'networkidle' });
			if (await page.getByRole('link', { name, exact: true }).count() === 0) {
				await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=team&layout=edit`, { waitUntil: 'networkidle' });
				await page.locator('#jform_name').fill(name);
				await page.locator('#jform_middle_name').fill(name);
				await page.locator('#jform_short_name').fill(`DT${number}`);
				await saveClose('team.save');
			}

			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=projectentries&project_id=3`, { waitUntil: 'networkidle' });
			if (await page.getByRole('link', { name, exact: true }).count() === 0) {
				await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&task=projectentry.add&project_id=3`, { waitUntil: 'networkidle' });
				await page.locator('#jform_entry_kind').selectOption('team');
				await page.locator('#jform_team_id').selectOption({ label: name });
				await page.locator('#jform_entry_code').fill(`demo-team-${number}`);
				await saveClose('projectentry.save');
			}
		}

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=stages&project_id=3`, { waitUntil: 'networkidle' });
		let stageLink = page.getByRole('link', { name: 'Demo knockout stage', exact: true });
		if (await stageLink.count() === 0) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&task=stage.add&project_id=3`, { waitUntil: 'networkidle' });
			await page.locator('#jform_name').fill('Demo knockout stage');
			await page.locator('#jform_stage_type').fill('knockout');
			await page.locator('#jform_sequence_number').fill('2');
			await setSelect('#jform_published', '1');
			await saveClose('stage.save');
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=stages&project_id=3`, { waitUntil: 'networkidle' });
			stageLink = page.getByRole('link', { name: 'Demo knockout stage', exact: true });
		}
		const stageId = new URL(await stageLink.getAttribute('href'), baseUrl).searchParams.get('id');

		const ensureRound = async (name, code, sequence, type) => {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=rounds&project_id=3&stage_id=${stageId}`, { waitUntil: 'networkidle' });
			let link = page.getByRole('link', { name, exact: true });
			if (await link.count() === 0) {
				await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&task=round.add&project_id=3&stage_id=${stageId}`, { waitUntil: 'networkidle' });
				await page.locator('#jform_name').fill(name);
				await page.locator('#jform_code').fill(code);
				await page.locator('#jform_round_type').fill(type);
				await page.locator('#jform_sequence_number').fill(String(sequence));
				await setSelect('#jform_published', '1');
				await saveClose('round.save');
				await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=rounds&project_id=3&stage_id=${stageId}`, { waitUntil: 'networkidle' });
				link = page.getByRole('link', { name, exact: true });
			}
			return new URL(await link.getAttribute('href'), baseUrl).searchParams.get('id');
		};

		const quarterfinalId = await ensureRound('Demo quarterfinals', 'demo_quarterfinals', 10, 'quarterfinal');
		const semifinalId = await ensureRound('Demo semifinals', 'demo_semifinals', 11, 'semifinal');
		const finalId = await ensureRound('Demo final', 'demo_final', 12, 'final');

		const createProgrammeItem = async (roundId, number, firstTeam, secondTeam, date) => {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=matches&project_id=3&stage_id=${stageId}&round_id=${roundId}`, { waitUntil: 'networkidle' });
			if (await page.locator(`input[value="${number}"]`).count() > 0) return;
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&task=match.add&project_id=3&stage_id=${stageId}&round_id=${roundId}`, { waitUntil: 'networkidle' });
			await page.locator('#jform_match_number').fill(number);
			await page.locator('#jform_contest_type').selectOption('head_to_head');
			await page.locator('#jform_participant_slot_1').selectOption({ label: firstTeam });
			await page.locator('#jform_participant_slot_2').selectOption({ label: secondTeam });
			await page.locator('#jform_scheduled_date').fill(date);
			await page.locator('#jform_scheduled_time').fill('17:00');
			await page.locator('#jform_duration_minutes').fill('90');
			await page.locator('#jform_venue_id').selectOption('1');
			await page.locator('#jform_status_code').fill('scheduled');
			await setSelect('#jform_published', '1');
			await saveClose('match.save');
		};

		await createProgrammeItem(quarterfinalId, 'DEMO-QF-1', 'Demo Team 1', 'Demo Team 8', '2026-09-20');
		await createProgrammeItem(quarterfinalId, 'DEMO-QF-2', 'Demo Team 4', 'Demo Team 5', '2026-09-20');
		await createProgrammeItem(quarterfinalId, 'DEMO-QF-3', 'Demo Team 2', 'Demo Team 7', '2026-09-21');
		await createProgrammeItem(quarterfinalId, 'DEMO-QF-4', 'Demo Team 3', 'Demo Team 6', '2026-09-21');
		await createProgrammeItem(semifinalId, 'DEMO-SF-1', 'Demo Team 1', 'Demo Team 4', '2026-09-27');
		await createProgrammeItem(semifinalId, 'DEMO-SF-2', 'Demo Team 2', 'Demo Team 3', '2026-09-27');
		await createProgrammeItem(finalId, 'DEMO-F-1', 'Demo Team 1', 'Demo Team 2', '2026-10-04');

		await page.goto(`${baseUrl}/administrator/index.php?option=com_menus&view=items&menutype=mainmenu`, { waitUntil: 'networkidle' });
		const menuLink = page.getByRole('link', { name: 'Demo postupový pavouk', exact: true });
		await menuLink.click();
		await page.waitForLoadState('networkidle');
		await page.locator('#jform_request_project_id').selectOption('3');
		await page.locator('#jform_request_stage_id').selectOption(stageId);
		await page.locator('#save-group-children-save button').click();
		await page.waitForLoadState('networkidle');

		console.log(`BRACKET_STAGE_ID=${stageId}; BRACKET_FINAL_ROUND_ID=${finalId}`);
	} finally {
		await browser.close();
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
