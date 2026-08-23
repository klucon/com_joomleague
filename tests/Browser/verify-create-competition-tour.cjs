const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;

	if (!baseUrl || !username || !password) {
		throw new Error('JOOMLA_BASE_URL, JOOMLA_USERNAME and JOOMLA_PASSWORD are required.');
	}

	const suffix = Date.now().toString(36);
	const values = {
		sportTypeName: `Tour sport ${suffix}`,
		sportTypeCode: `tour_${suffix}`,
		competitionName: `Tour competition ${suffix}`,
		seasonName: `Tour season ${suffix}`,
		projectName: `Tour project ${suffix}`,
	};
	const browser = await chromium.launch({ headless: true });

	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		const tour = page.locator('.shepherd-element:not([hidden])');
		const next = async () => tour.locator('.shepherd-button-primary').click();
		const fillStep = async (selector, value, title) => {
			const field = page.locator(selector);
			await tour.locator('.shepherd-title').filter({ hasText: title }).waitFor();
			await field.click();
			await field.pressSequentially(value);
			await field.dispatchEvent('input');
			await tour.locator('.shepherd-button-primary:not([disabled])').click();
		};
		const selectStep = async (selector, option, title) => {
			await tour.locator('.shepherd-title').filter({ hasText: title }).waitFor();
			await page.locator(selector).selectOption(option);
			await page.locator(selector).dispatchEvent('change');
			await tour.locator('.shepherd-button-primary:not([disabled])').click();
		};

		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username);
		await page.locator('#mod-login-password').fill(password);
		await page.locator('form#form-login button[type="submit"]').click();
		await page.waitForLoadState('networkidle');
		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=dashboard`, { waitUntil: 'networkidle' });
		await page.locator('.header-tours .dropdown-toggle').first().click();
		await page.locator('.header-tours .button-start-guidedtour').filter({ hasText: /kompletní základ soutěže|complete competition foundation/i }).first().click();

		await tour.waitFor();
		await next(); // Tour introduction.
		await next(); // Preparation; the redirect step navigates immediately.
		await page.waitForURL(/view=sporttype&layout=edit/);
		await tour.waitFor();
		await next(); // Continue from the redirect step on the sport type form.
		await fillStep('#jform_name', values.sportTypeName, /Název sportovního typu|Sport type name/i);
		await fillStep('#jform_code', values.sportTypeCode, /Kód sportovního typu|Sport type code/i);
		const profileValue = await page.locator('#jform_profile_version_id option:not([value=""])').first().getAttribute('value');
		await selectStep('#jform_profile_version_id', profileValue, /Sportovní profil|Sport profile/i);
		await page.locator('#toolbar-save button').click();

		await page.waitForURL(/view=competition&layout=edit/);
		await tour.waitFor();
		await next(); // Continue from the competition redirect step.
		await fillStep('#jform_name', values.competitionName, /Název soutěže|Competition name/i);
		await page.locator('#toolbar-save button').click();

		await page.waitForURL(/view=season&layout=edit/);
		await tour.waitFor();
		await next(); // Continue from the season redirect step.
		await fillStep('#jform_name', values.seasonName, /Název sezóny|Season name/i);
		await page.locator('#toolbar-save button').click();

		await page.waitForURL(/view=project&layout=edit/);
		await tour.waitFor();
		await next(); // Continue from the project redirect step.
		await fillStep('#jform_name', values.projectName, /Název projektu|Project name/i);
		await selectStep('#jform_competition_id', { label: values.competitionName }, /Vyberte soutěž|Select competition/i);
		await selectStep('#jform_season_id', { label: values.seasonName }, /Vyberte sezónu|Select season/i);
		await selectStep('#jform_sport_type_id', { label: values.sportTypeName }, /Vyberte sportovní typ|Select sport type/i);
		await selectStep('#jform_project_type', 'league', /Formát projektu|Project format/i);
		await page.locator('#toolbar-save button').click();

		await page.waitForURL(/view=projects/);
		await tour.waitFor();
		const finalText = await tour.innerText();

		if (/COM_JOOMLEAGUE_/.test(finalText) || !new RegExp(values.projectName).test(await page.locator('#projectList tbody tr:first-child').innerText())) {
			throw new Error(`Competition tour did not finish with the new project: ${finalText}`);
		}

		await next();
		await tour.waitFor({ state: 'detached' });
		console.log(JSON.stringify(values));
	} finally {
		await browser.close();
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
