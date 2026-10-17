const fs = require('node:fs');
const path = require('node:path');
const { firefox } = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const base = 'http://joomla62.local';
const lastName = `Audit person ${Date.now()}`;
const output = path.resolve(__dirname, '../artifacts/rc-code-review-20261010');
const evidence = { lastName, checks: [], cleanup: false, browserErrors: [] };
(async () => {
    fs.mkdirSync(output, { recursive: true });
    const browser = await firefox.launch({ executablePath: '/opt/playwright/firefox-1532/firefox/firefox' });
    const admin = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const guest = await browser.newContext();
    const page = await admin.newPage();
    const publicPage = await guest.newPage();
    for (const p of [page, publicPage]) {
        p.setDefaultTimeout(15000);
        p.on('pageerror', e => evidence.browserErrors.push(e.message));
    }
    async function row() {
        await page.goto(`${base}/administrator/index.php?option=com_joomleague&view=persons`, { waitUntil: 'networkidle' });
        await page.locator('#filter_search').fill(lastName);
        await page.locator('#filter_search').press('Enter');
        await page.waitForLoadState('networkidle');
        return page.getByRole('row').filter({ hasText: lastName });
    }
    async function save() {
        await page.locator('#toolbar-save button').click();
        await page.waitForLoadState('networkidle');
    }
    async function tabFor(selector) {
        const tabId = await page.locator(selector).evaluate(el => el.closest('[role=tabpanel]').id);
        await page.locator(`[role=tab][aria-controls="${tabId}"]`).click();
    }
    let loggedIn = false;
    try {
        await page.goto(`${base}/administrator/`);
        await page.locator('#mod-login-username').fill('admin');
        await page.locator('#mod-login-password').fill(fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md', 'utf8').match(/^Password: (.+)$/m)[1]);
        await page.locator('button[type=submit]').click();
        await page.waitForURL('**/administrator/index.php');
        loggedIn = true;
        await page.goto(`${base}/administrator/index.php?option=com_joomleague&view=person&layout=edit`, { waitUntil: 'networkidle' });
        await page.locator('#jform_first_name').fill('Audit');
        await page.locator('#jform_last_name').fill(lastName);
        await page.locator('#jform_country_code').fill('CZ');
        await tabFor('#jform_height_cm');
        await page.locator('#jform_height_cm').fill('181.5');
        await page.locator('#jform_weight_kg').fill('76.25');
        await tabFor('#jform_description');
        await page.evaluate(() => {
            const html = '<p>Audit biography <strong>formatted marker</strong>.</p><script>window.__auditPersonXss=true</script>';
            const editor = Joomla.editors.instances.jform_description;
            if (!editor?.setValue) throw Error('Native editor API unavailable.');
            editor.setValue(html);
        });
        await save();
        const item = await row();
        if (await item.count() !== 1) throw Error('Created person absent or ambiguous.');
        const href = await item.getByRole('link', { name: new RegExp(lastName) }).getAttribute('href');
        const id = new URL(href, base).searchParams.get('id');
        if (!id) throw Error('Created person ID missing.');
        evidence.id = id;
        const url = `${base}/index.php?option=com_joomleague&view=person&person_id=${id}`;
        const response = await publicPage.goto(url, { waitUntil: 'networkidle' });
        if (response.status() !== 200) throw Error('Published person did not return HTTP 200.');
        await publicPage.getByRole('heading', { name: `Audit ${lastName}`, exact: true }).waitFor();
        await publicPage.locator('strong', { hasText: 'formatted marker' }).waitFor();
        const text = await publicPage.locator('main').innerText();
        if (!text.includes('181.5') || !text.includes('76.25')) throw Error('Saved measurements missing from public profile.');
        if (await publicPage.evaluate(() => Boolean(window.__auditPersonXss)) || await publicPage.locator('script', { hasText: '__auditPersonXss' }).count()) throw Error('Unsafe description rendered or executed.');
        evidence.checks.push('Public profile renders saved measurements and safe formatted biography without injected script');
        await page.goto(new URL(href, base).href, { waitUntil: 'networkidle' });
        await tabFor('#jform_access');
        await page.locator('#jform_access').selectOption('2');
        await save();
        const privateResponse = await publicPage.goto(url, { waitUntil: 'networkidle' });
        const privateText = await publicPage.locator('body').innerText();
        if (privateResponse.status() !== 404 || privateText.includes(lastName) || privateText.includes('formatted marker')) throw Error('Guest received private person details or wrong denial status.');
        evidence.checks.push('Changing access to Registered prevents guest retrieval with HTTP 404');
        await publicPage.screenshot({ path: path.join(output, 'person-private-denial.png'), fullPage: true });
    } catch (e) {
        evidence.error = e.message;
        process.exitCode = 1;
    } finally {
        if (loggedIn) {
            try {
                const item = await row();
                if (await item.count() > 1) throw Error('Ambiguous fixture; deletion refused.');
                if (await item.count() === 1) {
                    await item.getByRole('checkbox').check();
                    await page.locator('#toolbar-delete button').click();
                    await page.getByRole('dialog').getByRole('button', { name: /^(Yes|Ano)$/ }).click();
                    await page.waitForLoadState('networkidle');
                }
                evidence.cleanup = await (await row()).count() === 0;
                if (!evidence.cleanup) throw Error('Person fixture remains.');
            } catch (e) { evidence.cleanupError = e.message; process.exitCode = 1; }
        }
        await browser.close();
        fs.writeFileSync(path.join(output, 'person-write.json'), JSON.stringify(evidence, null, 2));
        console.log(JSON.stringify(evidence, null, 2));
        if (evidence.browserErrors.length) process.exitCode = 1;
    }
})().catch(e => { console.error(e); process.exitCode = 1; });
