const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password } = process.env;
	const moduleId = process.env.JOOMLEAGUE_PROGRAM_MODULE_ID || '133';
	if (!baseUrl || !username || !password) throw new Error('Joomla URL and credentials are required.');

	const browser = await chromium.launch({ headless: true });
	const admin = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
	const editUrl = `${baseUrl}/administrator/index.php?option=com_modules&task=module.edit&id=${moduleId}`;
	const fields = ['orientation', 'density', 'group_by', 'heading', 'show_project_name', 'show_round', 'show_status', 'show_contest_type', 'show_images', 'show_empty'];
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
			const selector = admin.locator(`#jform_params_${name}`);
			const isControl = await selector.count() && await selector.evaluate((element) => ['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName));
			if (isControl) values[name] = await selector.inputValue();
			else values[name] = await admin.locator(`input[name="jform[params][${name}]"]:checked`).inputValue();
		}
		return values;
	}

	async function setRadio(name, value) {
		const input = admin.locator(`input[name="jform[params][${name}]"][value="${value}"]`);
		if (await input.count() !== 1) throw new Error(`Radio parameter ${name}=${value} is missing.`);
		await input.evaluate((element) => {
			element.checked = true;
			element.dispatchEvent(new Event('input', { bubbles: true }));
			element.dispatchEvent(new Event('change', { bubbles: true }));
		});
	}

	async function apply(values) {
		for (const [name, value] of Object.entries(values)) {
			const selector = admin.locator(`#jform_params_${name}`);
			const isControl = await selector.count() && await selector.evaluate((element) => ['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName));
			if (isControl) {
				if (await selector.evaluate((element) => element.tagName === 'SELECT')) await selector.selectOption(String(value), { force: true });
				else await selector.evaluate((element, nextValue) => {
					element.value = nextValue;
					element.dispatchEvent(new Event('input', { bubbles: true }));
					element.dispatchEvent(new Event('change', { bubbles: true }));
				}, String(value));
			} else await setRadio(name, value);
		}
		await Promise.all([
			admin.waitForNavigation({ waitUntil: 'networkidle' }),
			admin.evaluate(() => Joomla.submitbutton('module.apply')),
		]);
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
			const module = page.locator('.mod-joomleague-program');
			if (await module.count() !== 1) throw new Error(`Programme module missing or duplicated at ${viewport.name}.`);
			if (await module.getAttribute('data-orientation') !== expected.orientation) throw new Error(`Orientation not rendered at ${viewport.name}.`);
			if (await module.getAttribute('data-density') !== expected.density) throw new Error(`Density not rendered at ${viewport.name}.`);
			if (await module.getByRole('heading', { name: expected.heading, exact: true }).count() !== 1) throw new Error(`Custom heading missing at ${viewport.name}.`);
			if (expected.grouped && await module.locator('h4').count() < 1) throw new Error(`Grouping did not render at ${viewport.name}.`);
			if (/MOD_JOOMLEAGUE_[A-Z0-9_]+/.test(await module.innerText())) throw new Error(`Raw language key rendered at ${viewport.name}.`);
			const brokenImages = await module.locator('img').evaluateAll((images) => images.filter((image) => image.complete && image.naturalWidth === 0).map((image) => image.src));
			if (brokenImages.length) throw new Error(`Broken programme images at ${viewport.name}: ${brokenImages.join(', ')}`);
			const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
			if (overflow > 1) throw new Error(`Page overflows by ${overflow}px at ${viewport.name}.`);
			if (errors.length) throw new Error(`Browser errors at ${viewport.name}: ${errors.join('; ')}`);
			await page.close();
		}
	}

	try {
		await login();
		for (const id of ['stage_id', 'round_id', 'venue_id', 'status_codes', 'days_before', 'days_after', 'participant_limit', 'sort_by', 'sort_direction', 'date_format']) {
			if (await admin.locator(`#jform_params_${id}`).count() !== 1) throw new Error(`Programme parameter ${id} is missing from Joomla editor.`);
		}
		if (/MOD_JOOMLEAGUE_PROGRAM_[A-Z0-9_]+/.test(await admin.locator('main').innerText())) throw new Error('Programme editor contains untranslated constants.');
		original = await readValues();

		await apply({ orientation: 'horizontal', density: 'detailed', group_by: 'round', heading: 'Programme settings test', show_project_name: '1', show_round: '1', show_status: '1', show_contest_type: '1', show_images: '1', show_empty: '1' });
		await verifyFrontend({ orientation: 'horizontal', density: 'detailed', heading: 'Programme settings test', grouped: true });

		await apply({ orientation: 'vertical', density: 'compact', group_by: 'none', heading: 'Compact programme test', show_project_name: '0', show_round: '0', show_status: '0', show_contest_type: '0', show_images: '0', show_empty: '1' });
		await verifyFrontend({ orientation: 'vertical', density: 'compact', heading: 'Compact programme test', grouped: false });
	} finally {
		if (original) {
			await admin.goto(editUrl, { waitUntil: 'networkidle' });
			await apply(original);
		}
		await browser.close();
	}

	console.log('Programme module settings browser verification passed and original settings restored.');
})().catch((error) => { console.error(error); process.exit(1); });
