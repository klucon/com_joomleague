const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password, JOOMLEAGUE_PROJECT_ID: projectId } = process.env;
	if (!baseUrl || !username || !password || !projectId) throw new Error('Joomla credentials and JOOMLEAGUE_PROJECT_ID are required.');
	const browser = await chromium.launch({ headless: true });
	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		const suffix = Date.now().toString(36);
		const stageName = `Match fixture stage ${suffix}`;
		const roundName = `Match fixture round ${suffix}`;
		const matchNumber = `M-${suffix}`;
		const clickAndWait = async locator => {
			await Promise.all([
				page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
				locator.click(),
			]);
		};
		const matchRowByNumber = number => page.getByRole('row').filter({ has: page.locator(`input[data-schedule-field="match_number"][value="${number}"]`) });
		const openRenderedLink = async locator => {
			const href = await locator.getAttribute('href');
			if (!href) throw new Error('Expected action link has no href.');
			await page.goto(new URL(href, page.url()).toString(), { waitUntil: 'networkidle' });
		};
		const confirmDelete = async () => {
			const native = page.waitForEvent('dialog', { timeout: 2000 }).then(dialog => dialog.accept()).catch(() => null);
			await page.getByRole('button', { name: 'Delete' }).click(); const yes = page.getByRole('button', { name: 'Yes', exact: true });
			if (await yes.waitFor({ state: 'visible', timeout: 2500 }).then(() => true).catch(() => false)) await yes.click(); else await native;
			await page.waitForLoadState('networkidle');
		};
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' }); await page.locator('#mod-login-username').fill(username); await page.locator('#mod-login-password').fill(password); await page.locator('form#form-login button[type="submit"]').click(); await page.waitForLoadState('networkidle');
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=stages&project_id=${projectId}`, { waitUntil: 'networkidle' }); await clickAndWait(page.getByRole('button', { name: 'New' }));
		await page.getByLabel('Name').fill(stageName); await page.locator('#jform_stage_type').fill('league_phase'); await clickAndWait(page.getByRole('button', { name: 'Save & Close' }));
		const stageRow = page.getByRole('row').filter({ has: page.getByRole('link', { name: stageName, exact: true }) }); await clickAndWait(stageRow.getByRole('link', { name: 'Manage stage rounds' }));
		await clickAndWait(page.getByRole('button', { name: 'New' })); await page.getByLabel('Name').fill(roundName); await page.getByLabel('Round type').fill('standard'); await page.getByLabel('Round number').fill('1'); await page.getByLabel('Start date').fill('2026-08-15'); await page.getByLabel('End date').fill('2026-08-16'); await clickAndWait(page.getByRole('button', { name: 'Save & Close' }));
		const roundRow = page.getByRole('row').filter({ has: page.getByRole('link', { name: roundName, exact: true }) }); await clickAndWait(roundRow.locator('a[href*="view=matches"]'));
		await clickAndWait(page.getByRole('button', { name: 'New' }));
		if (await page.getByLabel('Item number').count() === 0) {
			const alerts = await page.locator('.alert').allTextContents();
			throw new Error(`Match add form did not open at ${page.url()}; alerts=${alerts.join(' | ')}`);
		}
		if (await page.locator('.main-card joomla-tab-element').count() !== 3) throw new Error('Match form must contain three Joomla tabs.');
		await page.getByLabel('Item number').fill(matchNumber); await page.getByLabel('Contest item format').selectOption('head_to_head');
		const homeParticipant = page.locator('#jform_participant_slot_1');
		const awayParticipant = page.locator('#jform_participant_slot_2');
		const participantOptions = await homeParticipant.locator('option').evaluateAll(options => options.map(option => option.value).filter(Boolean));
		if (participantOptions.length < 2) throw new Error('Match fixture did not provide two selectable participants.');
		await homeParticipant.selectOption(participantOptions[0]); await awayParticipant.selectOption(participantOptions[1]);
		await page.getByLabel('Date', { exact: true }).fill('2026-08-15'); await page.getByLabel('Time', { exact: true }).fill('16:30'); await page.getByLabel('Expected duration').fill('105'); await page.getByLabel('Item status').fill('scheduled');
		await clickAndWait(page.getByRole('button', { name: 'Save & Close' }));
		const matchRow = matchRowByNumber(matchNumber); await clickAndWait(matchRow.locator('a[href*="task=match.edit"]'));
		if (await page.locator('#match-form').count() !== 1) throw new Error(`Match edit form did not open: ${page.url()}`);
		if (!await page.locator('#jform_participant_slot_1').inputValue() || !await page.locator('#jform_participant_slot_2').inputValue()) throw new Error('Saved match participants were not loaded into the edit form.');
		await clickAndWait(page.getByRole('button', { name: 'Close', exact: true }));
		const editedRow = matchRowByNumber(matchNumber);
		await openRenderedLink(editedRow.locator('a[href*="view=matchresult"]'));
		if (await page.locator('joomla-tab-element').count() !== 2) throw new Error('Match result view must contain two Joomla tabs.');
		await page.getByRole('tab', { name: 'Overview' }).click();
		await page.getByText('numeric_score', { exact: true }).waitFor(); await page.getByRole('tab', { name: 'Result' }).click(); await page.getByLabel('Result status').waitFor();
		await page.getByRole('button', { name: 'Close', exact: true }).click(); await page.waitForLoadState('networkidle');
		if (!page.url().includes('view=matches')) throw new Error(`Match result close action did not return to the round matches: ${page.url()}`);
		const matchesUrl = page.url();
		let returnedRow = matchRowByNumber(matchNumber);
		await openRenderedLink(returnedRow.locator('a[href*="view=matchofficials"]'));
		await page.getByRole('heading', { name: 'Officials', exact: true }).waitFor();
		if (/COM_JOOMLEAGUE_[A-Z0-9_]+/.test(await page.locator('body').innerText())) throw new Error('Match officials contains an untranslated language key.');
		await page.getByRole('link', { name: 'Manage project officials' }).click(); await page.waitForLoadState('networkidle');
		await page.getByRole('heading', { name: /^Project officials:/ }).waitFor();
		if (/COM_JOOMLEAGUE_[A-Z0-9_]+/.test(await page.locator('body').innerText())) throw new Error('Project officials contains an untranslated language key.');
		if (await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)) throw new Error('Project officials overflows horizontally.');
		await page.goto(matchesUrl, { waitUntil: 'networkidle' });
		returnedRow = matchRowByNumber(matchNumber);
		await openRenderedLink(returnedRow.locator('a[href*="view=matchevents"]'));
		await page.getByRole('heading', { name: 'Events', exact: true }).waitFor();
		await page.getByRole('button', { name: 'Add event', exact: true }).waitFor();
		if (/COM_JOOMLEAGUE_[A-Z0-9_]+/.test(await page.locator('body').innerText())) throw new Error('Match events contains an untranslated language key.');
		if (await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)) throw new Error('Match events overflows horizontally.');
		await page.getByRole('link', { name: 'Close', exact: true }).click(); await page.waitForLoadState('networkidle');
		if (!page.url().includes('view=matches')) throw new Error(`Match events close action did not return to round matches: ${page.url()}`);
		returnedRow = matchRowByNumber(matchNumber);
		await openRenderedLink(returnedRow.locator('a[href*="view=matchstatistics"]'));
		await page.getByRole('heading', { name: 'Statistics', exact: true }).waitFor();
		if (/COM_JOOMLEAGUE_[A-Z0-9_]+/.test(await page.locator('body').innerText())) throw new Error('Match statistics contains an untranslated language key.');
		if (await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)) throw new Error('Match statistics overflows horizontally.');
		await page.getByRole('link', { name: 'Close', exact: true }).click(); await page.waitForLoadState('networkidle');
		if (!page.url().includes('view=matches')) throw new Error(`Match statistics close action did not return to round matches: ${page.url()}`);
		returnedRow = matchRowByNumber(matchNumber); await returnedRow.getByRole('checkbox').check(); await confirmDelete();
		if (await matchRowByNumber(matchNumber).count()) throw new Error('Temporary match was not deleted.');
		await page.getByRole('link', { name: 'Close' }).click(); await page.waitForLoadState('networkidle');
		const cleanupRound = page.getByRole('row').filter({ has: page.getByRole('link', { name: roundName, exact: true }) }); await cleanupRound.getByRole('checkbox').check(); await confirmDelete();
		await page.getByRole('link', { name: 'Close' }).click(); await page.waitForLoadState('networkidle');
		const cleanupStage = page.getByRole('row').filter({ has: page.getByRole('link', { name: stageName, exact: true }) }); await cleanupStage.getByRole('checkbox').check(); await confirmDelete();
	} finally { await browser.close(); }
	console.log('Universal match scheduling create/edit/delete workflow OK');
})();
