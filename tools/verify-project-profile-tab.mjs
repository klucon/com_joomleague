import { readFileSync } from 'node:fs';
import { firefox } from '/mnt/disk-a/dev/uklid/2026-09-19/office-browser-tests/node_modules/playwright-core/index.mjs';

const credentials = readFileSync('/mnt/disk-a/pswd/2026-joomla-joomla62-ai-developer.md', 'utf8');
const value = name => credentials.match(new RegExp(`^- ${name}:\\s*(.+)$`, 'm'))[1].replace(/^`|`$/g, '');
const browser = await firefox.launch({ headless: true });
try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('https://joomla62.klucon.cz/administrator/');
    await page.locator('#mod-login-username').fill(value('Username'));
    await page.locator('#mod-login-password').fill(value('Password'));
    await Promise.all([page.waitForNavigation(), page.locator('#form-login button[type=submit]').click()]);
    await page.goto('https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague&view=projects');
    const link = page.locator('a[href*="task=project.edit"][href*="id="]').first();
    const href = await link.getAttribute('href');
    if (!href) throw new Error('No project edit link found.');
    const id = process.argv[2] || new URL(href, page.url()).searchParams.get('id');
    await page.goto(`https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague&view=project&layout=edit&id=${id}&profile_check=1`);
    await page.locator('joomla-tab-element#sportprofile').waitFor({ state: 'visible' });
    const text = await page.locator('joomla-tab-element#sportprofile').innerText();
    if (/COM_JOOMLEAGUE_|Fatal error|SQLSTATE/.test(text)) throw new Error(text);
    if (!/1\.\d+\.\d+/.test(text)) throw new Error('Missing profile version.');
    if (process.argv[2]) {
        const csrfResponse = await page.request.post('https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague', {
            form: { task: 'projectprofile.migrate', project_id: id, acknowledge: '1' },
        });
        if (csrfResponse.status() !== 403) throw new Error('Migration without CSRF token was not rejected.');
        if (await page.locator('form form').count()) throw new Error('Nested HTML forms detected.');
        await page.locator('#profile-acknowledge').check();
        await Promise.all([page.waitForNavigation(), page.locator('button[form="profile-migration-form"]').click()]);
        await page.locator('joomla-tab-element#sportprofile').waitFor({ state: 'visible' });
        const updated = await page.locator('joomla-tab-element#sportprofile').innerText();
        if (!updated.includes('999.0.0') || /COM_JOOMLEAGUE_|Fatal error|SQLSTATE/.test(updated)) throw new Error(updated);
        console.log('Browser migration with acknowledgement, CSRF rejection and history OK');
    }
    await page.screenshot({ path: '/mnt/disk-a/dev/com_joomleague_6-2-0/dist/project-sport-profile.png', fullPage: true });
    console.log(`Project profile tab OK: ${page.url()}`);
    if (errors.length) throw new Error(errors.join('\n'));
    const cancel = page.locator('button[onclick*="project.cancel"], button[data-submit-task="project.cancel"]');
    if (await cancel.count()) await cancel.first().click();
} finally {
    await browser.close();
}
