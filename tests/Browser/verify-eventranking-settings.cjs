const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
(async () => {
    const base = process.env.JL_R1_BASE;
    assert.match(base, /^http:\/\/127\.0\.0\.1:\d+$/);
    assert.match(process.env.JL_R1_APP, /^jl-r1-[a-z0-9]+-(mysqli|pgsql)-app$/);
    const id = process.env.JL_EVENTRANKING_MODULE;
    assert.match(id, /^[1-9][0-9]*$/);
    const browser = await firefox.launch({executablePath: '/opt/playwright/firefox-1532/firefox/firefox', headless: true});
    const evidence = [];
    try {
        const context = await browser.newContext({viewport: {width: 1440, height: 1000}});
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(base + '/administrator/', {waitUntil: 'networkidle'});
        await page.locator('#mod-login-username').fill('admin');
        await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.locator('#form-login button[type=submit]').click()]);
        const edit = base + '/administrator/index.php?option=com_modules&task=module.edit&id=' + id;
        const save = async values => {
            await page.goto(edit, {waitUntil: 'networkidle'});
            for (const [key, value] of Object.entries(values)) {
                const field = page.locator('#jform_params_' + key);
                const tag = await field.evaluate(el => el.tagName);
                if (tag === 'SELECT') await field.selectOption(value, {force: true});
                else if (tag === 'INPUT') await field.fill(value, {force: true});
                else await page.locator('input[name="jform[params][' + key + ']"][value="' + value + '"]').evaluate(el => { el.checked = true; el.dispatchEvent(new Event('change', {bubbles: true})); });
            }
            await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.evaluate(() => Joomla.submitbutton('module.apply'))]);
            const messages = await page.locator('#system-message-container').innerText();
            assert.doesNotMatch(messages, /Invalid field|Field required|Save failed|Error/i, 'Native save rejected: ' + messages);
            await page.goto(edit, {waitUntil: 'networkidle'});
            for (const [key, value] of Object.entries(values)) {
                const field = page.locator('#jform_params_' + key);
                const actual = await field.evaluate(el => ['INPUT', 'SELECT'].includes(el.tagName) ? el.value : el.querySelector('input:checked')?.value);
                assert.equal(actual, value, 'Saved setting ' + key);
            }
            evidence.push({saved: Object.keys(values)});
        };
        const site = async width => {
            const guest = await browser.newContext({viewport: {width, height: 1000}});
            const tab = await guest.newPage();
            const response = await tab.goto(base + '/', {waitUntil: 'networkidle'});
            assert.equal(response.status(), 200);
            const module = tab.locator('.mod-joomleague-eventranking');
            assert.equal(await module.count(), 1);
            assert.doesNotMatch(await module.innerText(), /MOD_JOOMLEAGUE_|currently unavailable/);
            return {guest, tab, module};
        };
        await save({stage_id: process.env.JL_EVENTRANKING_STAGE, direction: 'asc', ties: 'dense', participant_kind: 'person', date_from: '2026-10-15', date_until: '2026-10-15', show_rank: '0', show_value: '0', link_target: '0', show_all_link: '0', show_event_name: '1', show_appearances: '1', orientation: 'horizontal', density: 'compact'});
        await save({show_images: '1', image_size: '64', highlight_target: 'person:' + process.env.JL_EVENTRANKING_PERSON});
        for (const width of [1440, 390, 320]) {
            const {guest, tab, module} = await site(width);
            assert.equal(await module.locator('.list-group-item').count(), 1);
            assert.equal(await module.locator('a,.jl-event-rank,.jl-event-value').count(), 0);
            assert.equal(await module.locator('.jl-event-heading,.jl-event-appearances').count(), 2);
            assert.equal(await module.locator('.list-group-item-primary').count(), 1);
            const image = module.locator('.jl-event-image');
            assert.equal(await image.count(), 1);
            assert.equal(await image.getAttribute('width'), '64');
            await image.scrollIntoViewIfNeeded();
            await image.evaluate(img => img.decode());
            assert(await image.evaluate(img => img.naturalWidth > 0));
            assert(await module.evaluate(el => el.scrollWidth <= el.clientWidth + 1));
            await tab.screenshot({path: path.join(process.env.JL_R1_ARTIFACTS, process.env.JL_R1_DRIVER + '-eventranking-' + width + '.png'), fullPage: true});
            evidence.push({width, rows: 1, hidden: true});
            await guest.close();
        }
        await save({direction: 'desc', participant_kind: 'any', show_rank: '1', show_value: '1', show_unit: '1', link_target: '1', show_all_link: '1', show_event_name: '0', show_appearances: '0', orientation: 'vertical', density: 'standard', show_images: '0', highlight_target: ''});
        const {guest, module} = await site(1440);
        assert.equal(await module.locator('.list-group-item').count(), 2);
        assert.equal(await module.locator('.jl-event-value').first().innerText(), '3');
        assert.equal(await module.locator('a.list-group-item').count(), 2);
        assert.equal(await module.locator('img,.list-group-item-primary').count(), 0);
        assert.equal(await module.locator('.jl-event-unit').count(), 2);
        await guest.close();
        await save({show_project_name: '0', show_unit: '0', date_from: '2026-10-16', date_until: '', show_empty: '1'});
        const empty = await site(390);
        assert.equal(await empty.module.locator('.list-group-item').count(), 0);
        assert.match(await empty.module.innerText(), /No published actor events/);
        assert.doesNotMatch(await empty.module.innerText(), /R1 project/);
        await empty.guest.close();
        await save({show_empty: '0'});
        const hiddenContext = await browser.newContext({viewport: {width: 390, height: 1000}});
        const hidden = await hiddenContext.newPage();
        assert.equal((await hidden.goto(base + '/', {waitUntil: 'networkidle'})).status(), 200);
        assert.equal(await hidden.locator('.mod-joomleague-eventranking').count(), 0);
        await hiddenContext.close();
        assert.deepEqual(errors, []);
        assert.equal(evidence.length, 8);
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS, process.env.JL_R1_DRIVER + '-eventranking-browser.json'), JSON.stringify({passed: true, evidence}, null, 2));
        console.log('PASS: native module settings persisted and changed guest output at desktop/mobile widths.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
