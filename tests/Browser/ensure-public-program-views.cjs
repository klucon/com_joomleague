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

		const create = async (title, view, fields) => {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_menus&view=items&menutype=mainmenu`, { waitUntil: 'networkidle' });
			if (await page.getByRole('link', { name: title, exact: true }).count()) return;
			await page.goto(`${baseUrl}/administrator/index.php?option=com_menus&view=item&layout=edit&menutype=mainmenu`, { waitUntil: 'networkidle' });
			await page.locator('#jform_title').fill(title);
			await page.locator('.js-modal-content-select-field button[data-button-action="select"]').click();
			await page.waitForTimeout(800);
			const picker = page.frames().find((frame) => frame.url().includes('view=menutypes'));
			if (!picker) throw new Error('Joomla menu type picker did not open.');
			await picker.locator('button.accordion-button').filter({ hasText: 'JoomLeague' }).click();
			await picker.locator(`a.choose_type[data-request*="${view}"]`).click();
			for (const [name, value] of Object.entries(fields)) {
				const field = page.locator(`#jform_request_${name}`);
				await field.waitFor();
				await field.selectOption(String(value));
			}
			await page.locator('#save-group-children-save button').click();
			await page.waitForLoadState('networkidle');
			if (await page.locator('.alert-danger, .joomla-alert--danger').count()) throw new Error(`Saving ${view} menu item failed.`);
		};

		await create('Program demo klubu', 'clubplan', { project_id: 3, club_id: 1 });
		await create('Nejbližší položka programu', 'nextmatch', { project_id: 3 });

		for (const title of ['Program demo klubu', 'Nejbližší položka programu']) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_menus&view=items&menutype=mainmenu`, { waitUntil: 'networkidle' });
			await page.getByRole('link', { name: title, exact: true }).waitFor();
		}
		console.log('PUBLIC_PROGRAM_MENU_VIEWS_OK');
	} finally {
		await browser.close();
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
