const fs = require('node:fs');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
(async () => {
    const browser = await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox'});
    try {
        const page = await browser.newPage();
        await page.goto('http://joomla62.local/administrator/');
        await page.locator('#mod-login-username').fill('admin');
        await page.locator('#mod-login-password').fill(fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md','utf8').match(/^Password: (.+)$/m)[1]);
        await Promise.all([page.waitForURL('**/administrator/index.php'), page.locator('button[type=submit]').click()]);
        for (const width of [1440,390]) {
            await page.setViewportSize({width,height:1000});
            const response = await page.goto('http://joomla62.local/administrator/index.php?option=com_joomleague&view=matchparticipants&match_id=13026');
            const url = new URL(page.url());
            if (response.status() !== 200 || url.searchParams.get('view') !== 'match' || url.searchParams.get('id') !== '13026') throw Error('Wrong redirect target or HTTP status.');
            await page.locator('#match-form').waitFor();
            if (await page.locator('#jform_id').inputValue() !== '13026') throw Error('Wrong match in editor.');
            await page.screenshot({path:`tests/artifacts/club-history-repair-20261011/fixed-participants-${width}.png`,fullPage:true});
            console.log(`PASS: fixed participants -> correct match editor, width ${width}, HTTP 200.`);
        }
    } finally { await browser.close(); }
})().catch(error => {console.error(error);process.exitCode=1;});
