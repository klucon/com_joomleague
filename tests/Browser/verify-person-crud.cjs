const { chromium } = require('playwright');

(async () => {
	const baseUrl = process.env.JOOMLA_BASE_URL;
	const username = process.env.JOOMLA_USERNAME;
	const password = process.env.JOOMLA_PASSWORD;

	if (!baseUrl || !username || !password) throw new Error('JOOMLA_BASE_URL, JOOMLA_USERNAME and JOOMLA_PASSWORD are required.');

	const suffix = String(Date.now());
	const firstName = 'JoomLeague';
	const lastName = `Person ${suffix}`;
	const browser = await chromium.launch({ headless: true });

	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		await page.goto(`${baseUrl}/administrator/`, { waitUntil: 'networkidle' });
		await page.locator('#mod-login-username').fill(username);
		await page.locator('#mod-login-password').fill(password);
		await page.locator('form#form-login button[type="submit"]').click();
		await page.waitForLoadState('networkidle');

		const hideTour = page.getByRole('button', { name: 'Hide Forever' });
		if (await hideTour.isVisible().catch(() => false)) await hideTour.click();

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=person&layout=edit`, { waitUntil: 'networkidle' });
		await page.getByLabel('First name', { exact: true }).fill(firstName);
		await page.getByLabel('Last name', { exact: true }).fill(lastName);
		await page.getByLabel('Country code', { exact: true }).fill('CZ');
		await page.getByRole('tab', { name: 'Biography', exact: true }).click();
		await page.locator('#jform_birth_date').evaluate((field) => {
			field.value = '1990-02-03';
			field.dataset.altValue = '1990-02-03';
			field.dispatchEvent(new Event('change', { bubbles: true }));
		});
		await page.getByLabel('Height (cm)', { exact: true }).fill('181.5');
		await page.getByLabel('Weight (kg)', { exact: true }).fill('76.25');
		await page.getByRole('tab', { name: 'Description', exact: true }).click();
		await page.evaluate((description) => {
			const editor = window.Joomla?.editors?.instances?.jform_description;

			if (editor?.setValue) {
				editor.setValue(description);
				return;
			}

			const field = document.querySelector('#jform_description');
			field.value = description;
			field.dispatchEvent(new Event('change', { bubbles: true }));
		}, '<p>Public <strong>profile</strong>.</p><script>window.__jlPersonXss = true</script>');
		await page.getByRole('button', { name: 'Save & Close' }).click();
		await page.waitForLoadState('networkidle');

		const row = page.getByRole('row').filter({ hasText: lastName });
		const editHref = await row.getByRole('link', { name: new RegExp(lastName) }).getAttribute('href');
		const personId = new URL(editHref, baseUrl).searchParams.get('id');

		if (!personId) throw new Error('Created person ID was not found.');

		await page.goto(`${baseUrl}/index.php?option=com_joomleague&view=person&person_id=${personId}`, { waitUntil: 'networkidle' });
		await page.getByRole('heading', { name: `${firstName} ${lastName}` }).waitFor();
		await page.locator('time[datetime="1990-02-03"]').waitFor();
		await page.getByText('181.5 cm', { exact: true }).waitFor();
		await page.getByText('76.25 kg', { exact: true }).waitFor();
		if (await page.locator('script', { hasText: 'window.__jlPersonXss' }).count()) throw new Error('Unsafe person description script was rendered.');
		if (await page.evaluate(() => Boolean(window.__jlPersonXss))) throw new Error('Unsafe person description script executed.');
		await page.locator('.com-joomleague-person strong', { hasText: 'profile' }).waitFor();

		await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=persons`, { waitUntil: 'networkidle' });
		const deleteRow = page.getByRole('row').filter({ hasText: lastName });
		await deleteRow.getByRole('checkbox').check();
		await page.getByRole('button', { name: 'Delete' }).click();
		await page.getByRole('dialog').getByRole('button', { name: 'Yes', exact: true }).click();
		await page.waitForLoadState('networkidle');

		if (await page.getByRole('row').filter({ hasText: lastName }).count()) throw new Error('Temporary person was not deleted.');
	} finally {
		await browser.close();
	}

	console.log('Person create/delete workflow OK');
})();
