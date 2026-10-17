const fs = require('node:fs');
const path = require('node:path');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const base = 'http://joomla62.local';
const tasks = ['matchresult.save', 'matchresult.addSegment', 'matchlineup.assign', 'matchlineup.addSubstitution', 'sportcategories.save', 'sportprofiles.categories'];
(async () => {
    const browser = await firefox.launch({executablePath: '/opt/playwright/firefox-1532/firefox/firefox'});
    const observations = [];
    try {
        const context = await browser.newContext();
        const page = await context.newPage();
        await page.goto(base + '/administrator/');
        await page.locator('#mod-login-username').fill('admin');
        await page.locator('#mod-login-password').fill(fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md', 'utf8').match(/^Password: (.+)$/m)[1]);
        await page.locator('button[type=submit]').click();
        await page.waitForURL('**/administrator/index.php');
        // Missing token and missing record IDs: no valid mutation payload is sent.
        for (const task of tasks) {
            const response = await context.request.post(base + '/administrator/index.php?option=com_joomleague&task=' + task, {form: {}});
            const body = await response.text();
            const rejected = response.status() === 403 && /token/i.test(body);
            observations.push({task, status: response.status(), missingTokenRejected: rejected});
        }
        const guest = await browser.newContext();
        const response = await guest.request.get(base + '/index.php?option=com_joomleague&task=ical.download&project_id=268');
        observations.push({task: 'ical.download', identity: 'guest', status: response.status(), cacheControl: response.headers()['cache-control'], contentType: response.headers()['content-type']});
        await guest.close();
        const directory = path.resolve(__dirname, '../artifacts/rc-code-review-20261010');
        fs.mkdirSync(directory, {recursive: true});
        fs.writeFileSync(path.join(directory, 'security-http.json'), JSON.stringify(observations, null, 2));
        console.log(JSON.stringify(observations, null, 2));
        if (observations.some(item => item.missingTokenRejected === false)) process.exitCode = 1;
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error); process.exitCode = 1;});
