const fs = require('node:fs');
const assert = require('node:assert/strict');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
(async()=>{
    const browser = await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox'});
    try {
        const page = await browser.newPage({viewport:{width:390,height:844}});
        const errors=[];
        page.on('pageerror',e=>errors.push(e.message));
        await page.goto('http://joomla62.local/administrator/');
        await page.locator('#mod-login-username').fill('admin');
        await page.locator('#mod-login-password').fill(fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md','utf8').match(/^Password: (.+)$/m)[1]);
        await page.locator('button[type=submit]').click();
        await page.waitForURL('**/administrator/index.php');
        await page.goto('http://joomla62.local/administrator/index.php?option=com_joomleague&view=persons');
        await page.locator('#filter_search').fill('Adamczyk');
        await page.locator('#filter_search').press('Enter');
        await page.waitForLoadState('networkidle');
        const rows=page.locator('#personList tbody tr');
        assert.ok(await rows.count()>1);
        for(const row of await rows.all()) {
            const first=row.locator('td').nth(1);
            if((await first.textContent()).trim()) {
                assert.equal(await row.locator('th span.d-md-none').isVisible(),true);
                assert.equal(await row.locator('th span.d-md-none').textContent(),await first.textContent());
            }
        }
        await page.goto('http://joomla62.local/administrator/index.php?option=com_joomleague&view=clubs');
        await page.locator('#filter_search').fill('Lechia');
        await page.locator('#filter_search').press('Enter');
        await page.waitForLoadState('networkidle');
        assert.ok((await page.locator('main').innerText()).includes('Lechia Gdańsk'));
        await page.goto('http://joomla62.local/administrator/index.php?option=com_joomleague&view=projects');
        assert.ok(await page.locator('tbody tr').count()>0);
        assert.deepEqual(errors,[]);
        console.log('PASS: original admin login preserved, imported Lechia clubs/projects visible and names distinguishable on mobile.');
    } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
