const { chromium } = require('playwright');

(async () => {
	const { JOOMLA_BASE_URL: baseUrl, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password } = process.env;
	const moduleId = process.env.JOOMLEAGUE_PROGRAMME_TICKER_MODULE_ID || '144';
	if (!baseUrl || !username || !password) throw new Error('Joomla URL and credentials are required.');
	const browser = await chromium.launch({ headless: true });
	const admin = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
	const editUrl = `${baseUrl}/administrator/index.php?option=com_modules&task=module.edit&id=${moduleId}`;
	const fields = ['sport_type_id','season_id','project_id','stage_id','round_id','club_id','entry_id','venue_id','content_mode','days_before','days_after','completed_limit','upcoming_limit','item_limit','participant_limit','orientation','direction','speed','pause_on_hover','loop','separator','density','heading','show_sport','show_season','show_project','show_stage','show_round','show_date','date_format','show_time','time_format','show_venue','show_status','show_contest_type','show_participants','show_images','image_size','image_style','show_result','show_decider','emphasize_winner','show_empty','show_participant_links','show_project_link','show_venue_link','template_show_match_detail_button'];
	let original;
	async function login() {
		await admin.goto(editUrl, { waitUntil: 'networkidle' });
		if (await admin.locator('#mod-login-username').count()) {
			await admin.locator('#mod-login-username').fill(username); await admin.locator('#mod-login-password').fill(password);
			await admin.locator('form#form-login button[type=submit]').click(); await admin.waitForLoadState('networkidle'); await admin.goto(editUrl, { waitUntil: 'networkidle' });
		}
	}
	async function readValues() {
		const values = {};
		for (const name of fields) {
			const control = admin.locator(`#jform_params_${name}`);
			const native = await control.count() && await control.evaluate(el => ['INPUT','TEXTAREA','SELECT'].includes(el.tagName));
			values[name] = native ? await control.inputValue() : await admin.locator(`input[name="jform[params][${name}]"]:checked`).inputValue();
		}
		return values;
	}
	async function apply(values) {
		for (const [name, value] of Object.entries(values)) {
			const control = admin.locator(`#jform_params_${name}`);
			const native = await control.count() && await control.evaluate(el => ['INPUT','TEXTAREA','SELECT'].includes(el.tagName));
			if (native) {
				if (await control.evaluate(el => el.tagName === 'SELECT')) await control.selectOption(String(value), { force: true });
				else await control.evaluate((el,v) => { el.value=v; el.dispatchEvent(new Event('input',{bubbles:true})); el.dispatchEvent(new Event('change',{bubbles:true})); }, String(value));
			} else {
				const radio = admin.locator(`input[name="jform[params][${name}]"][value="${value}"]`);
				if (await radio.count() !== 1) throw new Error(`Missing ${name}=${value}`);
				await radio.evaluate(el => { el.checked=true; el.dispatchEvent(new Event('input',{bubbles:true})); el.dispatchEvent(new Event('change',{bubbles:true})); });
			}
		}
		await Promise.all([admin.waitForNavigation({waitUntil:'networkidle'}), admin.evaluate(() => Joomla.submitbutton('module.apply'))]);
		const saved = await readValues();
		for (const [name,value] of Object.entries(values)) if (saved[name] !== String(value)) throw new Error(`${name} did not persist: ${saved[name]} != ${value}`);
	}
	async function inspect(context, expectedEnhanced) {
		const page = await context.newPage(); const errors=[];
		page.on('pageerror', e => errors.push(e.message)); page.on('response', r => { if (r.status() >= 500) errors.push(`${r.status()} ${r.url()}`); });
		const response = await page.goto(baseUrl, {waitUntil:'networkidle'}); if (!response || response.status() !== 200) throw new Error(`Frontend HTTP ${response?.status()}`);
		const root = page.locator('[data-joomleague-ticker]'); if (await root.count() !== 1) throw new Error('Ticker missing or duplicated.');
		if (await root.getAttribute('data-orientation') !== 'horizontal' || await root.getAttribute('data-direction') !== 'right' || await root.getAttribute('data-density') !== 'detailed') throw new Error('Layout attributes differ.');
		if (await root.locator('.jl-ticker-item:not([data-clone])').count() !== 3) throw new Error('Ticker did not render exactly three original items.');
		if ((await root.locator('[data-clone="1"]').count() > 0) !== expectedEnhanced) throw new Error('Clone/reduced-motion contract failed.');
		if ((await root.evaluate(el => el.classList.contains('is-enhanced'))) !== expectedEnhanced) throw new Error('Enhancement state failed.');
		if (!await root.getByRole('heading',{name:'Ticker browser test',exact:true}).count()) throw new Error('Heading missing.');
		for (const selector of ['.jl-ticker-sport','.jl-ticker-season','.jl-ticker-project','.jl-ticker-stage','.jl-ticker-round','.jl-ticker-date','.jl-ticker-time','.jl-ticker-status','.jl-ticker-contest','.jl-ticker-participant','.jl-ticker-score','.jl-ticker-detail']) if (!await root.locator(selector).count()) throw new Error(`${selector} missing.`);
		if (expectedEnhanced) { const style=await root.locator('.jl-ticker-track').evaluate(el=>{const s=getComputedStyle(el);return {name:s.animationName,duration:s.animationDuration,direction:s.animationDirection}}); if(style.name==='none'||style.duration!=='17s'||style.direction!=='reverse') throw new Error(`Animation settings failed: ${JSON.stringify(style)}`); await root.locator('.jl-ticker-viewport').hover(); if(await root.locator('.jl-ticker-track').evaluate(el=>getComputedStyle(el).animationPlayState)!=='paused') throw new Error('Hover pause failed.'); }
		if (/MOD_JOOMLEAGUE_[A-Z0-9_]+/.test(await root.innerText())) throw new Error('Raw language constant rendered.');
		if (errors.length) throw new Error(errors.join('; ')); await page.close();
	}
	try {
		await login(); for (const id of fields.concat(['status_codes'])) if (await admin.locator(`#jform_params_${id}`).count() !== 1) throw new Error(`Editor field ${id} missing.`);
		if (/MOD_JOOMLEAGUE_PROGRAMME_TICKER_[A-Z0-9_]+/.test(await admin.locator('main').innerText())) throw new Error('Editor contains untranslated constants.');
		original = await readValues();
		await apply({sport_type_id:'0',season_id:'0',project_id:'0',stage_id:'0',round_id:'0',club_id:'0',entry_id:'0',venue_id:'0',content_mode:'both',days_before:'5000',days_after:'5000',completed_limit:'3',upcoming_limit:'3',item_limit:'3',participant_limit:'4',orientation:'horizontal',direction:'right',speed:'17',pause_on_hover:'1',loop:'1',separator:'/',density:'detailed',heading:'Ticker browser test',show_sport:'1',show_season:'1',show_project:'1',show_stage:'1',show_round:'1',show_date:'1',date_format:'long',show_time:'1',time_format:'medium',show_venue:'1',show_status:'1',show_contest_type:'1',show_participants:'1',show_images:'0',image_size:'40',image_style:'circle',show_result:'1',show_decider:'1',emphasize_winner:'1',show_empty:'1',show_participant_links:'1',show_project_link:'1',show_venue_link:'1',template_show_match_detail_button:'1'});
		await inspect(await browser.newContext({viewport:{width:1440,height:900}}), true);
		await inspect(await browser.newContext({viewport:{width:390,height:844},reducedMotion:'reduce'}), false);
		const noJs = await browser.newContext({viewport:{width:390,height:844},javaScriptEnabled:false}); const page=await noJs.newPage(); await page.goto(baseUrl,{waitUntil:'domcontentloaded'}); const root=page.locator('[data-joomleague-ticker]'); if(await root.locator('.jl-ticker-item').count()!==3||await root.evaluate(el=>el.classList.contains('is-enhanced'))) throw new Error('No-JavaScript fallback failed.'); await noJs.close();
	} finally { if (original) { await admin.goto(editUrl,{waitUntil:'networkidle'}); await apply(original); } await browser.close(); }
	console.log('Programme Ticker browser verification passed; desktop, mobile, reduced motion and no-JavaScript fallback validated; original settings restored.');
})().catch(error => { console.error(error); process.exit(1); });
