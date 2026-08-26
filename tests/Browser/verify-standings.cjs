const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password, JOOMLEAGUE_PROJECT_ID: projectId } = process.env;
	if (!baseUrl || !username || !password || !projectId) throw new Error('Joomla credentials and JOOMLEAGUE_PROJECT_ID are required.');
	const browser = await chromium.launch({ headless: true });

	try {
		const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
		const panelUrl = `${baseUrl}/administrator/index.php?option=com_joomleague&view=projectpanel&project_id=${projectId}`;
		await page.goto(panelUrl, { waitUntil: 'networkidle' });
		if (await page.locator('#mod-login-username').count()) {
			await page.locator('#mod-login-username').fill(username);
			await page.locator('#mod-login-password').fill(password);
			await page.locator('form#form-login button[type="submit"]').click();
			await page.waitForLoadState('networkidle');
		}
		for (const viewport of [{ name: 'desktop', width: 1440, height: 1000 }, { name: 'mobile', width: 390, height: 844 }]) {
			await page.setViewportSize(viewport);
			await page.goto(panelUrl, { waitUntil: 'networkidle' });
			await page.goto(`${baseUrl}/administrator/index.php?option=com_joomleague&view=standings&project_id=${projectId}`, { waitUntil: 'networkidle' });
			try {
				await page.locator('h1.page-title').waitFor();
			} catch (error) {
				throw new Error(`Standings did not open at ${page.url()}: ${(await page.locator('body').innerText()).slice(0, 1600)}`, { cause: error });
			}
			if (/COM_JOOMLEAGUE_[A-Z0-9_]+/.test(await page.locator('body').innerText())) throw new Error('Standings contains an untranslated language key.');
			if (viewport.name === 'desktop') {
				if (await page.locator('#toolbar-refresh').count() !== 0) throw new Error('Standings still exposes a manual recalculation action.');
				const scopeTabs = page.locator('#standingsScopes [role="tab"]');
				if (await scopeTabs.count() < 1) throw new Error('Standings has no profile-defined scope tabs.');
				for (let index = 0; index < await scopeTabs.count(); index++) {
					const scope = await scopeTabs.nth(index).getAttribute('aria-controls');
					if (!scope || await page.locator(`#${scope} table`).count() !== 1) throw new Error(`Scope ${scope ?? 'unknown'} was not published automatically.`);
				}
			}
			if (await page.evaluate(() => {
				const main = document.querySelector('main');
				return main !== null && main.scrollWidth > main.clientWidth;
			})) throw new Error(`Standings content overflows horizontally at ${viewport.name}.`);
			try {
				const close = page.locator('a#toolbar-cancel');
				const href = await close.getAttribute('href');
				if (!href) throw new Error('Close link has no destination.');
				await page.goto(new URL(href, page.url()).toString(), { waitUntil: 'networkidle' });
			} catch (error) {
				const toolbar = await page.locator('#toolbar').innerHTML().catch(() => 'missing toolbar');
				throw new Error(`Standings close action is unavailable at ${page.url()}: ${(await page.locator('body').innerText()).slice(0, 1600)} Toolbar: ${toolbar.slice(0, 2500)}`, { cause: error });
			}
			const url = new URL(page.url());
			if (url.searchParams.get('view') !== 'projectpanel' || url.searchParams.get('project_id') !== projectId) throw new Error(`Standings close returned to ${page.url()}.`);
		}
		await page.close();
	} finally {
		await browser.close();
	}

	console.log('Profile-driven standings administration OK on desktop and mobile viewports');
})();
