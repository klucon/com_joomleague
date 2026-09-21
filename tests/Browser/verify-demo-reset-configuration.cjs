const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password } = process.env;
	if (!baseUrl || !username || !password) throw new Error('Joomla credentials are required.');

	const browser = await chromium.launch({ headless: true });
	const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
	const configUrl = `${baseUrl}/administrator/index.php?option=com_config&view=component&component=com_joomleague`;
	const demoUrl = `${baseUrl}/administrator/index.php?option=com_joomleague&view=demodata`;

	async function login() {
		await page.goto(configUrl, { waitUntil: 'networkidle' });
		if (await page.locator('#mod-login-username').count()) {
			await page.locator('#mod-login-username').fill(username);
			await page.locator('#mod-login-password').fill(password);
			await page.locator('form#form-login button[type="submit"]').click();
			await page.waitForLoadState('networkidle');
		}
	}

	async function setResetEnabled(enabled) {
		await page.goto(configUrl, { waitUntil: 'networkidle' });
		const field = page.locator(`input[name="jform[allow_full_data_reset]"][value="${enabled ? 1 : 0}"]`);
		if (await field.count() !== 1) throw new Error('Complete data reset option is missing from JoomLeague configuration.');
		await page.locator(`label[for="${await field.getAttribute('id')}"]`).click();
		await page.locator('#toolbar-apply button').click();
		await page.waitForLoadState('networkidle');
		if (!await field.isChecked()) throw new Error(`Complete data reset option was not saved as ${enabled ? 'enabled' : 'disabled'}.`);
	}

	try {
		await login();
		await setResetEnabled(true);
		await page.goto(demoUrl, { waitUntil: 'networkidle' });
		if (await page.locator('form[action*="demodata.reset"] fieldset:disabled').count()) {
			throw new Error('Reset form stayed disabled after enabling the component option.');
		}
	} finally {
		await setResetEnabled(false);
		await page.goto(demoUrl, { waitUntil: 'networkidle' });
		if (await page.locator('form[action*="demodata.reset"] fieldset:disabled').count() !== 1) {
			throw new Error('Reset form was not locked after disabling the component option.');
		}
		await browser.close();
	}

	console.log('Demo reset component configuration browser verification passed.');
})().catch((error) => { console.error(error); process.exit(1); });
