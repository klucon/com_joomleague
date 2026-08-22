const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;

	if (!baseUrl || !username || !password) {
		throw new Error('JOOMLA_BASE_URL, JOOMLA_USERNAME and JOOMLA_PASSWORD are required.');
	}

	const browser = await chromium.launch({ headless: true });

	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username);
		await page.locator('#mod-login-password').fill(password);
		await page.locator('form#form-login button[type="submit"]').click();
		await page.waitForLoadState('networkidle');

		for (const view of ['club', 'team']) {
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=${view}&layout=edit`, { waitUntil: 'networkidle' });

			const historyTab = page.getByRole('tab', { name: view === 'club' ? 'Club history' : 'Team history' });
			await historyTab.click();
			await page.getByText('Name history', { exact: true }).waitFor();

			const visibleAddButtons = page.locator('.group-add:visible');

			if (await visibleAddButtons.count() < 1) {
				const allAddButtons = await page.locator('.group-add').count();
				throw new Error(`${view} name history has no visible Joomla Add control (${allAddButtons} present in the page).`);
			}

			await visibleAddButtons.first().click();
			const removeGroup = page.getByRole('group', { name: 'Remove record', exact: true });
			await removeGroup.waitFor();
			const removeSwitcher = removeGroup.locator('.switcher');

			if (await removeSwitcher.count() !== 1) {
				throw new Error(`${view} name history does not expose the native Joomla removal switcher.`);
			}

			await page.getByRole('tab', { name: 'Media' }).click();
			await page.getByText('Logo history', { exact: true }).waitFor();

			if (await page.locator('body').getByText(/^COM_JOOMLEAGUE_/).count() > 0) {
				throw new Error(`${view} form exposes an untranslated JoomLeague language key.`);
			}
		}
	} finally {
		await browser.close();
	}

	console.log('Club and team history Joomla UI and removal switchers OK');
})();
