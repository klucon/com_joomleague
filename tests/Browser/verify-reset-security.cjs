const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const {required} = require('../Integration/reset-browser-contract.cjs');
const {summarize, runtimeFailurePattern} = require('./audit-result.cjs');

(async () => {
    const base = process.env.JL_R1_BASE;
    const app = process.env.JL_R1_APP;
    const directory = process.env.JL_R1_ARTIFACTS;
    const driver = process.env.JL_R1_DRIVER;
    assert.match(base, /^http:\/\/127\.0\.0\.1:\d+$/);
    assert.match(app, /^jl-r1-[a-z0-9]+-(mysqli|pgsql)-app$/);
    assert(['mysqli', 'pgsql'].includes(driver));
    assert(process.env.JL_R1_PASSWORD);
    const state = () => JSON.parse(execFileSync('docker', ['exec', app, 'php', '/opt/r1-tests/reset-acceptance.php', 'web-state'], {encoding: 'utf8', timeout: 30000}));
    const seeded = JSON.parse(execFileSync('docker', ['exec', app, 'php', '/opt/r1-tests/reset-acceptance.php', 'web-seed'], {encoding: 'utf8', timeout: 60000}));
    assert.equal(seeded.seeded, true);
    const demoUrl = base + '/administrator/index.php?option=com_joomleague&view=demodata';
    const resetUrl = base + '/administrator/index.php?option=com_joomleague&task=demodata.reset';
    const phrase = 'REMOVE AND CLEAN DEMO DATA';
    const observations = [];
    const errors = [];
    const browser = await firefox.launch({executablePath: '/opt/playwright/firefox-1532/firefox/firefox', headless: true});
    const record = (id, status, expectedStatus, text = '') => {
        observations.push({id, status, expectedStatus, evidenceCount: 2, outcome: 'PASS',
            fatal: runtimeFailurePattern.test(text), errors: [...errors],
            keys: text.match(/\bCOM_JOOMLEAGUE_[A-Z0-9_]+\b/g) || []});
    };
    const login = async (username) => {
        const context = await browser.newContext({viewport: {width: 1440, height: 1000}});
        const page = await context.newPage();
        page.on('pageerror', error => errors.push(error.name));
        await page.goto(base + '/administrator/', {waitUntil: 'networkidle'});
        await page.locator('#mod-login-username').fill(username);
        await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.locator('#form-login button[type=submit]').click()]);
        assert.equal(await page.locator('#mod-login-username').count(), 0);
        const token = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        assert.match(token, /^[a-f0-9]{32}$/);
        return {context, page, token};
    };
    try {
        const admin = await login('admin');
        let response = await admin.page.goto(demoUrl, {waitUntil: 'networkidle'});
        const form = admin.page.locator('form[action*="demodata.reset"]');
        assert.equal(response.status(), 200);
        assert.equal(await form.locator('fieldset:disabled').count(), 1);
        assert(state().counts.r1_joomleague_project > 0);
        record('reset-web-disabled-form', 200, 200, await admin.page.locator('main').innerText());
        const postDenied = async (id, context, data, expectedStatus, expectedText) => {
            const before = state();
            const rejected = await context.request.post(resetUrl, {form: data, maxRedirects: 0});
            const text = await rejected.text();
            assert.equal(rejected.status(), expectedStatus, id);
            if (expectedText) assert.match(text, expectedText, id);
            assert.deepEqual(state(), before, id + ': database changed');
            record(id, rejected.status(), expectedStatus, text);
        };
        await postDenied('reset-web-disabled-post', admin.context, {[admin.token]: '1', confirmation: phrase}, 403, /reset is disabled/i);

        await admin.page.goto(base + '/administrator/index.php?option=com_config&view=component&component=com_joomleague', {waitUntil: 'networkidle'});
        const enable = admin.page.locator('input[name="jform[allow_full_data_reset]"][value="1"]');
        assert.equal(await enable.count(), 1);
        // The configuration tab may be inactive, but the native radio must still be changed through its UI.
        const tab = admin.page.locator('button[role=tab], a[role=tab]');
        for (let i = 0; i < await tab.count() && !await enable.isVisible(); i++) await tab.nth(i).click();
        await admin.page.locator('label[for="' + await enable.getAttribute('id') + '"]').click();
        await Promise.all([admin.page.waitForNavigation({waitUntil: 'networkidle'}), admin.page.locator('#toolbar-apply button').click()]);
        assert.equal(state().enabled, 1);
        record('reset-web-enable-option', 200, 200, await admin.page.locator('main').innerText());
        await admin.page.goto(demoUrl, {waitUntil: 'networkidle'});
        assert.equal(await form.locator('fieldset:disabled').count(), 0);
        admin.token = await admin.page.evaluate(() => Joomla.getOptions('csrf.token'));
        assert.match(admin.token, /^[a-f0-9]{32}$/);

        const guest = await browser.newContext();
        const beforeGuest = state();
        response = await guest.request.post(resetUrl, {form: {[admin.token]: '1', confirmation: phrase}});
        const guestText = await response.text();
        assert.equal(response.status(), 200);
        assert.match(guestText, /id="mod-login-username"/);
        assert.deepEqual(state(), beforeGuest);
        record('reset-web-guest', response.status(), 200, guestText);

        const editor = await login('r1.editor.a');
        const beforeEditor = state();
        response = await editor.page.goto(demoUrl, {waitUntil: 'networkidle'});
        assert.equal(response.status(), 403);
        assert.equal(await editor.page.locator('form[action*="demodata.reset"]').count(), 0);
        assert.deepEqual(state(), beforeEditor);
        record('reset-web-editor-view', response.status(), 403, await editor.page.locator('body').innerText());
        await postDenied('reset-web-editor-post', editor.context, {[editor.token]: '1', confirmation: phrase}, 403, /not authorised|not authorized|permission/i);

        await postDenied('reset-web-missing-token', admin.context, {confirmation: phrase}, 200, /invalid.*token/i);
        assert.notEqual(admin.token, '0'.repeat(32));
        await postDenied('reset-web-invalid-token', admin.context, {['0'.repeat(32)]: '1', confirmation: phrase}, 200, /invalid.*token/i);
        const beforeGet = state();
        response = await admin.context.request.get(resetUrl + '&' + new URLSearchParams({[admin.token]: '1', confirmation: phrase}), {maxRedirects: 0});
        const getText = await response.text();
        assert.equal(response.status(), 200);
        assert.match(getText, /invalid.*token/i);
        assert.deepEqual(state(), beforeGet);
        record('reset-web-get', response.status(), 200, getText);

        for (const [id, value, field] of [
            ['reset-web-empty-confirmation', '', 'confirmation'],
            ['reset-web-case-confirmation', phrase.toLowerCase(), 'confirmation'],
            ['reset-web-markup-confirmation', '<b>' + phrase + '</b>', 'confirmation'],
            ['reset-web-space-confirmation', ' ' + phrase + ' ', 'confirmation'],
            ['reset-web-array-confirmation', phrase, 'confirmation[]']
        ]) {
            await postDenied(id, admin.context, {[admin.token]: '1', [field]: value}, 303);
            const target = await admin.page.goto(demoUrl, {waitUntil: 'networkidle'});
            assert.equal(target.status(), 200);
            assert.match(await admin.page.locator('body').innerText(), /confirmation text is incorrect/i);
        }

        await form.locator('[name=confirmation]').fill(phrase);
        const beforeReset = state();
        assert(beforeReset.counts.r1_joomleague_project > 0 && beforeReset.project_assets > 0 && beforeReset.finder_links > 0);
        await admin.page.screenshot({path: path.join(directory, driver + '-reset-before.png'), fullPage: true});
        const success = await Promise.all([admin.page.waitForNavigation({waitUntil: 'networkidle'}), form.locator('button[type=submit]').click()]);
        assert.equal(success[0].status(), 200);
        const text = await admin.page.locator('main').innerText();
        assert.match(text, /All JoomLeague runtime data were removed/);
        const after = state();
        assert(Object.values(after.counts).every(count => count === 0));
        assert.equal(after.project_assets, 0);
        assert.equal(after.finder_links, 0);
        record('reset-web-success', 200, 200, text);
        assert.deepEqual(after.core, beforeReset.core);
        assert.deepEqual(after.profiles, beforeReset.profiles);
        const expectedParams = {...beforeReset.params, allow_full_data_reset: 0};
        assert.deepEqual(after.params, expectedParams);
        record('reset-web-preserved', 200, 200);
        assert.equal(after.enabled, 0);
        assert.equal(await form.locator('fieldset:disabled').count(), 1);
        record('reset-web-disabled-after', 200, 200, text);
        await admin.page.screenshot({path: path.join(directory, driver + '-reset-after.png'), fullPage: true});
        await postDenied('reset-web-repeat-denied', admin.context, {[admin.token]: '1', confirmation: phrase}, 403, /reset is disabled/i);
        assert(summarize(observations, required).passed);
        console.log('PASS: browser reset disabled gate, guest/editor ACL, CSRF, literal confirmation, real UI reset and data preservation.');
    } finally {
        await browser.close();
        for (const id of required) if (!observations.some(row => row.id === id)) observations.push({id, status: 0, outcome: 'FAIL', evidenceCount: 0});
        fs.writeFileSync(path.join(directory, driver + '-reset-http.json'), JSON.stringify({observations, ...summarize(observations, required)}, null, 2));
    }
})().catch(error => {console.error('FAIL: reset browser ' + error.name + ' ' + String(error.message).slice(0, 700)); process.exitCode = 1;});
