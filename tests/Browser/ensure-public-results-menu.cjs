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

		await page.goto(`${baseUrl}/administrator/index.php?option=com_menus&view=items&menutype=mainmenu`, { waitUntil: 'networkidle' });
		if (await page.getByRole('link', { name: 'Program demo soutěže', exact: true }).count() === 0) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_menus&view=item&layout=edit&menutype=mainmenu`, { waitUntil: 'networkidle' });
			await page.locator('#jform_title').fill('Program demo soutěže');
			await page.locator('.js-modal-content-select-field button[data-button-action="select"]').click();
			await page.waitForTimeout(800);
			const picker = page.frames().find((frame) => frame.url().includes('view=menutypes'));
			if (!picker) throw new Error('Joomla menu type picker did not open.');
			await picker.locator('button.accordion-button').filter({ hasText: 'JoomLeague' }).click();
			await picker.locator('a.choose_type[data-request*="results"]').click();
			await page.locator('#jform_request_project_id').waitFor();
			await page.locator('#jform_request_project_id').selectOption('3');
			await page.locator('#save-group-children-save button').click();
			await page.waitForLoadState('networkidle');
		}

		await page.goto(`${baseUrl}/administrator/index.php?option=com_menus&view=items&menutype=mainmenu`, { waitUntil: 'networkidle' });
		const menuLink = page.getByRole('link', { name: 'Program demo soutěže', exact: true });
		await menuLink.waitFor();
		const editUrl = new URL(await menuLink.getAttribute('href'), baseUrl);
		console.log(`RESULTS_MENU_ID=${editUrl.searchParams.get('id')}`);
	} finally {
		await browser.close();
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
