const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password } = process.env;
	const moduleId = process.env.JOOMLEAGUE_LATEST_RESULTS_MODULE_ID || '147';
	if (!baseUrl || !username || !password) throw new Error('Joomla URL and credentials are required.');

	const browser = await chromium.launch({ headless: true });
	const admin = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
	const editUrl = `${baseUrl}/administrator/index.php?option=com_modules&task=module.edit&id=${moduleId}`;
	const fields = ['orientation', 'density', 'group_by', 'heading', 'show_sport', 'show_season', 'show_project', 'show_stage', 'show_round', 'show_date', 'show_time', 'show_venue', 'show_status', 'show_contest_type', 'show_images', 'show_scores', 'show_decider', 'emphasize_winner', 'show_participant_links', 'show_project_link', 'show_venue_link', 'show_empty'];
	let original = null;

	async function login() {
		await admin.goto(editUrl, { waitUntil: 'networkidle' });
		if (await admin.locator('#mod-login-username').count()) {
			await admin.locator('#mod-login-username').fill(username);
			await admin.locator('#mod-login-password').fill(password);
			await admin.locator('form#form-login button[type="submit"]').click();
			await admin.waitForLoadState('networkidle');
			await admin.goto(editUrl, { waitUntil: 'networkidle' });
		}
	}

	async function readValues() {
		const values = {};
		for (const name of fields) {
			const control = admin.locator(`#jform_params_${name}`);
			const native = await control.count() && await control.evaluate((element) => ['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName));
			values[name] = native ? await control.inputValue() : await admin.locator(`input[name="jform[params][${name}]"]:checked`).inputValue();
		}
		return values;
	}

	async function apply(values) {
		for (const [name, value] of Object.entries(values)) {
			const control = admin.locator(`#jform_params_${name}`);
			const native = await control.count() && await control.evaluate((element) => ['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName));
			if (native) {
				if (await control.evaluate((element) => element.tagName === 'SELECT')) await control.selectOption(String(value), { force: true });
				else await control.evaluate((element, nextValue) => {
					element.value = nextValue;
					element.dispatchEvent(new Event('input', { bubbles: true }));
					element.dispatchEvent(new Event('change', { bubbles: true }));
				}, String(value));
			} else {
				const input = admin.locator(`input[name="jform[params][${name}]"][value="${value}"]`);
				if (await input.count() !== 1) throw new Error(`Radio parameter ${name}=${value} is missing.`);
				await input.evaluate((element) => {
					element.checked = true;
					element.dispatchEvent(new Event('input', { bubbles: true }));
					element.dispatchEvent(new Event('change', { bubbles: true }));
				});
			}
		}
		await Promise.all([admin.waitForNavigation({ waitUntil: 'networkidle' }), admin.evaluate(() => Joomla.submitbutton('module.apply'))]);
		const saved = await readValues();
		for (const [name, value] of Object.entries(values)) {
			if (saved[name] !== String(value)) throw new Error(`Parameter ${name} was not persisted (${saved[name]} !== ${value}).`);
		}
	}

	async function verifyFrontend(expected) {
		for (const viewport of [{ width: 1440, height: 1000, name: 'desktop' }, { width: 390, height: 844, name: 'mobile' }]) {
			const page = await browser.newPage({ viewport });
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));
			page.on('response', (response) => { if (response.status() >= 500) errors.push(`${response.status()} ${response.url()}`); });
			const response = await page.goto(baseUrl, { waitUntil: 'networkidle' });
			if (!response || response.status() !== 200) throw new Error(`Frontend returned ${response?.status()} at ${viewport.name}.`);
			const module = page.locator('.mod-joomleague-latest-results');
			if (await module.count() !== 1) throw new Error(`Latest Results module missing or duplicated at ${viewport.name}.`);
			if (await module.getAttribute('data-orientation') !== expected.orientation) throw new Error(`Orientation not rendered at ${viewport.name}.`);
			if (await module.getAttribute('data-density') !== expected.density) throw new Error(`Density not rendered at ${viewport.name}.`);
			if (await module.getByRole('heading', { name: expected.heading, exact: true }).count() !== 1) throw new Error(`Custom heading missing at ${viewport.name}.`);
			if (expected.grouped && await module.locator('h4').count() < 1) throw new Error(`Grouping did not render at ${viewport.name}.`);
			if (/MOD_JOOMLEAGUE_[A-Z0-9_]+/.test(await module.innerText())) throw new Error(`Raw language key rendered at ${viewport.name}.`);
			const brokenImages = await module.locator('img').evaluateAll((images) => images.filter((image) => image.complete && image.naturalWidth === 0).map((image) => image.src));
			if (brokenImages.length) throw new Error(`Broken result images at ${viewport.name}: ${brokenImages.join(', ')}`);
			const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
			if (overflow > 1) {
				const offenders = await page.evaluate(() => [...document.querySelectorAll('body *')].filter((element) => {
					const rect = element.getBoundingClientRect();
					return rect.right > document.documentElement.clientWidth + 1 || rect.left < -1;
				}).slice(0, 10).map((element) => `${element.tagName.toLowerCase()}.${[...element.classList].join('.')} [${Math.round(element.getBoundingClientRect().left)},${Math.round(element.getBoundingClientRect().right)}]`));
				const tickerMetrics = await page.evaluate(() => [...document.querySelectorAll('.mod-joomleague-programme-ticker, .jl-ticker-viewport')].map((element) => {
					const rect = element.getBoundingClientRect();
					const style = getComputedStyle(element);
					return `${element.className} rect=${Math.round(rect.left)},${Math.round(rect.right)} width=${element.clientWidth}/${element.scrollWidth} overflow=${style.overflowX}`;
				}));
				const isolatedWidths = await page.evaluate(() => {
					const ticker = document.querySelector('.mod-joomleague-programme-ticker');
					const latest = document.querySelector('.mod-joomleague-latest-results');
					if (ticker) ticker.style.display = 'none';
					const withoutTicker = document.documentElement.scrollWidth;
					const latestOffenders = latest ? [...latest.querySelectorAll('*')].filter((element) => {
						const rect = element.getBoundingClientRect();
						return rect.right > document.documentElement.clientWidth + 1 || rect.left < -1;
					}).slice(0, 10).map((element) => `${element.tagName.toLowerCase()}.${[...element.classList].join('.')} [${Math.round(element.getBoundingClientRect().left)},${Math.round(element.getBoundingClientRect().right)}]`) : [];
					if (latest) latest.style.display = 'none';
					return `${withoutTicker}/${document.documentElement.scrollWidth}/${document.documentElement.clientWidth} ${latestOffenders.join('; ')}`;
				});
				throw new Error(`Page overflows by ${overflow}px at ${viewport.name}: ${offenders.join('; ')} | ${tickerMetrics.join('; ')} | isolated=${isolatedWidths}`);
			}
			if (errors.length) throw new Error(`Browser errors at ${viewport.name}: ${errors.join('; ')}`);
			await page.close();
		}
	}

	try {
		await login();
		for (const id of ['sport_type_id', 'season_id', 'project_id', 'stage_id', 'round_id', 'club_id', 'entry_id', 'venue_id', 'status_codes', 'days_before', 'limit', 'participant_limit', 'order', 'date_format', 'time_format', 'image_size', 'template_show_match_detail_button']) {
			if (await admin.locator(`#jform_params_${id}`).count() !== 1) throw new Error(`Latest Results parameter ${id} is missing from Joomla editor.`);
		}
		if (/MOD_JOOMLEAGUE_LATEST_RESULTS_[A-Z0-9_]+/.test(await admin.locator('main').innerText())) throw new Error('Latest Results editor contains untranslated constants.');
		original = await readValues();

		await apply({ orientation: 'horizontal', density: 'detailed', group_by: 'project', heading: 'Latest Results settings test', show_sport: '1', show_season: '1', show_project: '1', show_stage: '1', show_round: '1', show_date: '1', show_time: '1', show_venue: '1', show_status: '1', show_contest_type: '1', show_images: '1', show_scores: '1', show_decider: '1', emphasize_winner: '1', show_participant_links: '1', show_project_link: '1', show_venue_link: '1', show_empty: '1' });
		await verifyFrontend({ orientation: 'horizontal', density: 'detailed', heading: 'Latest Results settings test', grouped: true });

		await apply({ orientation: 'vertical', density: 'compact', group_by: 'none', heading: 'Compact Latest Results test', show_sport: '0', show_season: '0', show_project: '0', show_stage: '0', show_round: '0', show_date: '0', show_time: '0', show_venue: '0', show_status: '0', show_contest_type: '0', show_images: '0', show_scores: '0', show_decider: '0', emphasize_winner: '0', show_participant_links: '0', show_project_link: '0', show_venue_link: '0', show_empty: '1' });
		await verifyFrontend({ orientation: 'vertical', density: 'compact', heading: 'Compact Latest Results test', grouped: false });
	} finally {
		if (original) {
			await admin.goto(editUrl, { waitUntil: 'networkidle' });
			await apply(original);
		}
		await browser.close();
	}

	console.log('Latest Results module settings browser verification passed and original settings restored.');
})().catch((error) => { console.error(error); process.exit(1); });
