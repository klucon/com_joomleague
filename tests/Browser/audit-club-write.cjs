const fs = require('node:fs');
const path = require('node:path');
const { firefox } = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const base = 'http://joomla62.local';
const name = `Audit club ${Date.now()}`;
const output = path.resolve(__dirname, '../artifacts/rc-code-review-20261010');
const evidence = { name, checks: [], cleanup: false };

(async () => {
    fs.mkdirSync(output, { recursive: true });
    const browser = await firefox.launch({ executablePath: '/opt/playwright/firefox-1532/firefox/firefox' });
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    page.setDefaultTimeout(15000);
    page.on('pageerror', error => evidence.checks.push({ browserError: error.message }));
    async function findRow() {
        await page.goto(`${base}/administrator/index.php?option=com_joomleague&view=clubs`, { waitUntil: 'networkidle' });
        await page.locator('#filter_search').fill(name);
        await page.locator('#filter_search').press('Enter');
        await page.waitForLoadState('networkidle');
        return page.getByRole('row').filter({ hasText: name });
    }
    async function removeFixture() {
        const row = await findRow();
        if (await row.count() === 0) {
            evidence.cleanup = true;
            return;
        }
        if (await row.count() !== 1) throw Error('Ambiguous audit fixture; refusing deletion.');
        await row.getByRole('checkbox').check();
        await page.locator('#toolbar-delete button').click();
        const dialog = page.getByRole('dialog');
        await dialog.waitFor({ state: 'visible' });
        await dialog.getByRole('button', { name: /^(Yes|Ano)$/ }).click();
        await page.waitForLoadState('networkidle');
        evidence.cleanup = await (await findRow()).count() === 0;
        if (!evidence.cleanup) throw Error('Audit fixture remains after deletion.');
    }
    let authenticated = false;
    try {
        await page.goto(`${base}/administrator/`);
        await page.locator('#mod-login-username').fill('admin');
        const password = fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md', 'utf8').match(/^Password: (.+)$/m)?.[1];
        if (!password) throw Error('Local administrator credential unavailable.');
        await page.locator('#mod-login-password').fill(password);
        await page.locator('button[type=submit]').click();
        await page.waitForURL('**/administrator/index.php');
        authenticated = true;
        await page.goto(`${base}/administrator/index.php?option=com_joomleague&view=club&layout=edit`, { waitUntil: 'networkidle' });
        await page.locator('#jform_name').fill(name);
        await page.locator('#jform_short_name').fill('AUDIT');
        await page.locator('#jform_country_code').fill('CZ');
        await page.locator('#toolbar-save button').click();
        await page.waitForLoadState('networkidle');
        let row = await findRow();
        if (await row.count() !== 1) throw Error('Created club absent from filtered list.');
        evidence.checks.push('Created club appears in list');
        await row.getByRole('link', { name, exact: true }).click();
        await page.waitForLoadState('networkidle');
        evidence.editUrl = page.url();
        for (const [selector, expected] of [['#jform_name', name], ['#jform_short_name', 'AUDIT'], ['#jform_country_code', 'CZ']]) {
            if (await page.locator(selector).inputValue() !== expected) throw Error(`Saved value mismatch: ${selector}`);
        }
        evidence.checks.push('Saved name, short name and country survive reopening');
        const uuid = await page.locator('#jform_uuid').inputValue();
        if (!/^[a-f0-9-]{36}$/i.test(uuid)) throw Error('Generated UUID missing.');
        await page.locator('#jform_short_name').fill('EDITED');
        await page.locator('#toolbar-save button').click();
        await page.waitForLoadState('networkidle');
        row = await findRow();
        await row.getByRole('link', { name, exact: true }).click();
        await page.waitForLoadState('networkidle');
        if (await page.locator('#jform_short_name').inputValue() !== 'EDITED' || await page.locator('#jform_uuid').inputValue() !== uuid) throw Error('Edit or UUID stability failed.');
        evidence.checks.push('Edit persists and UUID remains stable');
        await page.screenshot({ path: path.join(output, 'club-write.png'), fullPage: true });
    } catch (error) {
        evidence.error = error.message;
        process.exitCode = 1;
    } finally {
        if (authenticated) {
            try { await removeFixture(); } catch (error) { evidence.cleanupError = error.message; process.exitCode = 1; }
        }
        await browser.close();
        fs.writeFileSync(path.join(output, 'club-write.json'), JSON.stringify(evidence, null, 2));
        console.log(JSON.stringify(evidence, null, 2));
        if (evidence.checks.some(item => typeof item === 'object')) process.exitCode = 1;
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
