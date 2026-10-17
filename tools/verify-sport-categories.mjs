import { readFileSync } from 'node:fs';
import { firefox } from '/mnt/disk-a/dev/uklid/2026-09-19/office-browser-tests/node_modules/playwright-core/index.mjs';

const credentials = readFileSync('/mnt/disk-a/pswd/2026-joomla-joomla62-ai-developer.md', 'utf8');
const value = name => credentials.match(new RegExp(`^- ${name}:\\s*(.+)$`, 'm'))[1].replace(/^`|`$/g, '');
const origin = process.env.JOOMLEAGUE_TEST_ORIGIN || 'https://joomla62.klucon.cz';
const categoriesUrl = `${origin}/administrator/index.php?option=com_joomleague&view=sportcategories`;
const browser = await firefox.launch({ headless: true });
const code = `browser-category-${Date.now()}`;
const fixture = process.argv[2] ? JSON.parse(process.argv[2]) : null;
let created = false;
let page;
try {
    page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(`${origin}/administrator/`);
    await page.locator('#mod-login-username').fill(value('Username'));
    await page.locator('#mod-login-password').fill(value('Password'));
    await page.locator('#form-login button[type=submit]').click();
    await page.waitForURL(url => !url.searchParams.has('task'));
    await page.goto(categoriesUrl);
    if (/COM_JOOMLEAGUE_|Fatal error|SQLSTATE/.test(await page.locator('main').innerText())) throw new Error('Category view contains untranslated keys or errors.');
    await page.locator('#category-name').fill('Browser category fixture');
    await page.locator('#category-code').fill(code);
    await page.locator('button[value="sportcategories.save"]').click();
    const row = page.locator('tr').filter({ has: page.locator('code', { hasText: code }) });
    await row.waitFor();
    created = true;
    await row.locator('th a').click();
    await page.locator('#category-uuid').waitFor();
    const uuid = await page.locator('#category-uuid').inputValue();
    if (!/^[a-f0-9-]{36}$/.test(uuid)) throw new Error('Missing generated UUID.');
    await page.locator('#category-name').fill('Browser category updated');
    await page.locator('button[value="sportcategories.save"]').click();
    await page.locator('tr').filter({ hasText: code }).locator('th a').click();
    if (await page.locator('#category-uuid').inputValue() !== uuid) throw new Error('UUID changed after edit.');
    for (const task of ['sportcategories.save', 'sportcategories.delete', 'sportprofiles.categories']) {
        const csrf = await page.request.post(`${origin}/administrator/index.php?option=com_joomleague`, { form: { task, 'jform[name]': 'No CSRF' } });
        if (csrf.status() !== 403) throw new Error(`Expected CSRF 403 for ${task}, got ${csrf.status()}`);
    }
    await page.goto(`${origin}/administrator/index.php?option=com_joomleague&view=sportprofiles`);
    if (await page.locator('form form').count()) throw new Error('Nested forms detected.');
    if (fixture) {
        await page.locator(`input[name="cid[]"][value="${fixture.profile}"]`).check();
        for (const id of [fixture.root, fixture.child]) await page.locator(`input[name="category_ids[]"][value="${id}"]`).check();
        await page.locator('button[onclick*="sportprofiles.categories"]').click();
        const profileRow = page.locator('tr').filter({ has: page.locator(`input[name="cid[]"][value="${fixture.profile}"]`) });
        if (!/Root[\s\S]*Updated/.test(await profileRow.innerText())) throw new Error('Browser category assignments were not displayed: ' + await profileRow.innerText() + ' Messages: ' + await page.locator('#system-message-container').innerText());
        await page.locator(`input[name="cid[]"][value="${fixture.profile}"]`).check();
        await page.locator('button[onclick*="sportprofiles.categories"]').click();
        const cleared = page.locator('tr').filter({ has: page.locator(`input[name="cid[]"][value="${fixture.profile}"]`) });
        if (/Root|Updated/.test(await cleared.innerText())) throw new Error('Browser category assignment removal failed.');
    }
    const categoryId = await page.locator('#filter-category option').filter({ hasText: 'Browser category updated' }).getAttribute('value');
    await page.locator('#filter-category').selectOption(categoryId);
    await page.locator('button[type=submit]').filter({ hasText: /Apply category filter|Použít filtr kategorií/ }).click();
    if (await page.locator('table tbody tr').count() !== 0) throw new Error('Unassigned category filter returned profiles.');
    await page.screenshot({ path: '/mnt/disk-a/dev/com_joomleague_6-2-0/dist/sport-profile-categories.png', fullPage: true });
    await page.goto(categoriesUrl);
    await page.screenshot({ path: '/mnt/disk-a/dev/com_joomleague_6-2-0/dist/sport-categories.png', fullPage: true });
    if (errors.length) throw new Error(errors.join('\n'));
    if (fixture) {
        await page.goto(`${categoriesUrl}&id=${fixture.root}`);
        await page.locator('#category-name').fill('Browser validated');
        await page.locator('button[value="sportcategories.save"]').click();
    }
    console.log('Browser category create/edit, immutable UUID, translations, filtering and CSRF OK');
} finally {
    if (created && page) {
        await page.goto(categoriesUrl);
        await page.locator('tr').filter({ has: page.locator('code', { hasText: code }) }).locator('button[value="sportcategories.delete"]').click();
        await page.locator('tr').filter({ has: page.locator('code', { hasText: code }) }).waitFor({ state: 'detached' });
    }
    await browser.close();
}
